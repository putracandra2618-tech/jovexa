<?php

namespace App\Http\Controllers;

use App\Enums\OrderStatus;
use App\Enums\ServiceStatus;
use App\Enums\UserRole;
use App\Http\Requests\StoreOrderRequest;
use App\Models\Lookup\OrderFileKind as OrderFileKindLookup;
use App\Models\Lookup\OrderStatus as OrderStatusLookup;
use App\Models\Order;
use App\Models\OrderFile;
use App\Models\Service;
use App\Services\OrderFileDownloader;
use App\Services\OrderWorkflow;
use App\Support\OrderNumberGenerator;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\View\View;

class OrderController extends Controller
{
    public function __construct(
        private readonly OrderNumberGenerator $orderNumbers,
        private readonly OrderWorkflow $workflow,
        private readonly OrderFileDownloader $downloads,
    ) {}

    /**
     * List every order the signed in customer placed.
     */
    public function index(Request $request): View
    {
        $orders = $request->user()
            ->orders()
            ->with(['service', 'statusRecord'])
            ->latest()
            ->paginate(10);

        return view('orders.index', [
            'orders' => $orders,
        ]);
    }

    /**
     * Show the order form for the given service.
     */
    public function create(Service $service): View
    {
        abort_unless($service->status === ServiceStatus::Published, 404);

        return view('orders.create', [
            'service' => $service,
        ]);
    }

    /**
     * Store a new order placed by the customer.
     */
    public function store(StoreOrderRequest $request, Service $service): RedirectResponse
    {
        abort_unless($service->status === ServiceStatus::Published, 404);

        $order = DB::transaction(function () use ($request, $service): Order {
            $order = $request->user()->orders()->create([
                'service_id' => $service->getKey(),
                'order_number' => $this->orderNumbers->generate(),
                'description' => $request->validated('description'),
                'order_status_id' => OrderStatusLookup::idFor(OrderStatus::PendingPayment->value),
                'total_price' => $service->price,
            ]);

            $this->storeRequirements($order, $request);

            $this->workflow->recordInitialStatus($order, $request->user());

            return $order;
        });

        return redirect()
            ->route('customer.orders.show', $order)
            ->with('success', 'Pesanan dibuat. Selesaikan pembayaran untuk memulai proses.');
    }

    /**
     * Show the order detail from the customer point of view.
     */
    public function show(Request $request, Order $order): View
    {
        $this->authorizeOrderOwner($request, $order);

        $order->load([
            'service',
            'files.kindRecord',
            'latestPayment.statusRecord',
            'statusHistory.author',
            'statusHistory.fromStatusRecord',
            'statusHistory.toStatusRecord',
        ]);

        return view('orders.show', [
            'order' => $order,
        ]);
    }

    /**
     * Download a file attached to the order.
     *
     * Results only become readable once the work has been delivered, while
     * requirement files stay available to their owner for the whole run.
     */
    public function download(Request $request, Order $order, OrderFile $file)
    {
        $this->authorizeOrderOwner($request, $order);

        abort_unless($file->order_id === $order->getKey(), 404);

        if ($file->isResult()) {
            abort_unless($order->canDownloadResults(), 403);
        }

        return $this->downloads->download($file);
    }

    /**
     * Let the customer add more requirement files while the order is still
     * waiting on the admin/worker.
     */
    public function addRequirements(Request $request, Order $order): RedirectResponse
    {
        $this->authorizeOrderOwner($request, $order);

        abort_unless(
            $order->status === OrderStatus::WaitingCustomer,
            403,
            'File tambahan hanya bisa diunggah saat tim Jovexa meminta informasi.'
        );

        $request->validate([
            'requirements' => ['required', 'array', 'max:5'],
            'requirements.*' => ['file', 'max:10240', 'mimes:'.implode(',', OrderFile::ALLOWED_EXTENSIONS)],
        ], [
            'requirements.required' => 'Pilih minimal satu file.',
            'requirements.*.max' => 'Setiap file maksimal 10 MB.',
            'requirements.*.mimes' => 'Format lampiran tidak didukung. Gunakan dokumen, gambar, atau arsip.',
        ]);

        $this->storeRequirements($order, $request);

        $this->workflow->transitionTo(
            $order,
            $order->worker_id === null ? OrderStatus::WaitingVerification : OrderStatus::InProgress,
            $request->user(),
            'Customer menambahkan informasi pendukung.',
        );

        return back()->with('success', 'File pendukung berhasil diunggah.');
    }

    /**
     * Cancel an order that has not been delivered yet.
     */
    public function destroy(Request $request, Order $order): RedirectResponse
    {
        $this->authorizeOrderOwner($request, $order);

        abort_unless(
            $order->status->canTransitionTo(OrderStatus::Cancelled),
            403,
            'Pesanan yang sudah dikirim tidak bisa dibatalkan.'
        );

        $this->workflow->transitionTo(
            $order,
            OrderStatus::Cancelled,
            $request->user(),
            'Pesanan dibatalkan oleh customer.',
        );

        return redirect()
            ->route('customer.orders.index')
            ->with('success', 'Pesanan dibatalkan.');
    }

    /**
     * Persist the requirement files uploaded with the order.
     */
    private function storeRequirements(Order $order, Request $request): void
    {
        foreach ($request->file('requirements', []) as $file) {
            $path = $file->store("orders/{$order->getKey()}/requirements", 'local');

            $order->files()->create([
                'file_name' => Str::limit($file->getClientOriginalName(), 255, ''),
                'file_path' => $path,
                'file_type' => $file->getClientMimeType(),
                'file_size' => $file->getSize(),
                'order_file_kind_id' => OrderFileKindLookup::idFor(OrderFile::KIND_REQUIREMENT),
                'uploaded_by' => $request->user()->getKey(),
            ]);
        }
    }

    /**
     * Ensure the signed in customer owns the order.
     */
    private function authorizeOrderOwner(Request $request, Order $order): void
    {
        abort_unless(
            $order->user_id === $request->user()?->getKey()
                && $request->user()->hasRole(UserRole::Customer),
            403,
        );
    }
}
