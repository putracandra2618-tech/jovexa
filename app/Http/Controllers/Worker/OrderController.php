<?php

namespace App\Http\Controllers\Worker;

use App\Enums\OrderStatus;
use App\Http\Controllers\Concerns\ManagesStaffOrders;
use App\Http\Controllers\Controller;
use App\Http\Requests\Worker\UpdateOrderStatusRequest;
use App\Models\Order;
use App\Models\OrderFile;
use App\Models\User;
use App\Services\OrderFileDownloader;
use App\Services\OrderWorkflow;
use App\Services\WorkerOrderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class OrderController extends Controller
{
    use ManagesStaffOrders;

    protected string $viewPrefix = 'worker.orders';

    public function __construct(
        private readonly OrderWorkflow $workflow,
        private readonly OrderFileDownloader $downloads,
        private readonly WorkerOrderService $workerOrders,
    ) {}

    /**
     * Orders currently assigned to the worker.
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $worker = $request->user();

        $orders = Order::query()
            ->assignedTo($worker)
            ->with(['service', 'user', 'statusRecord'])
            ->when($request->filled('status'), function ($query) use ($request): void {
                $status = OrderStatus::tryFrom($request->string('status')->toString());

                if ($status !== null) {
                    $query->whereStatus($status);
                }
            })
            ->when($search !== '', fn ($query) => $query->search($search))
            ->latest('worker_claimed_at')
            ->paginate(10)
            ->withQueryString();

        return view('worker.orders.index', [
            'orders' => $orders,
            'statuses' => OrderStatus::cases(),
            'search' => $search,
        ]);
    }

    /**
     * Orders waiting to be claimed.
     */
    public function queue(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $worker = $request->user();

        $orders = Order::query()
            ->availableForWorkers()
            ->with(['service', 'user', 'statusRecord'])
            ->when($search !== '', fn ($query) => $query->search($search))
            ->oldest()
            ->paginate(10)
            ->withQueryString();

        return view('worker.orders.queue', [
            'orders' => $orders,
            'search' => $search,
            'isOnline' => $worker->is_online,
            'activeCount' => $this->workerOrders->activeOrderCount($worker),
            'maxActiveOrders' => WorkerOrderService::MAX_ACTIVE_ORDERS,
        ]);
    }

    /**
     * Orders the worker has completed or cancelled.
     */
    public function history(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));

        return view('worker.orders.history', [
            'orders' => Order::query()
                ->assignedTo($request->user())
                ->with(['service', 'user', 'statusRecord'])
                ->withStatus([OrderStatus::Delivered, OrderStatus::Cancelled])
                ->when($search !== '', fn ($query) => $query->search($search))
                ->latest()
                ->paginate(10)
                ->withQueryString(),
            'search' => $search,
        ]);
    }

    /**
     * Show an order that belongs to the worker.
     */
    public function show(Order $order): View
    {
        $this->ensureCanManageOrder($order);

        $order->load([
            'service',
            'user',
            'files.kindRecord',
            'latestPayment.statusRecord',
            'statusHistory.author',
            'statusHistory.fromStatusRecord',
            'statusHistory.toStatusRecord',
        ]);

        return view('worker.orders.show', [
            'order' => $order,
        ]);
    }

    /**
     * Claim an order from the shared queue.
     */
    public function claim(Request $request, Order $order): RedirectResponse
    {
        $this->workerOrders->claim($order, $request->user());

        return redirect()
            ->route('worker.orders.show', $order)
            ->with('success', 'Pesanan berhasil diambil. Kerjakan sesuai kebutuhan customer.');
    }

    /**
     * Update the status of an order the worker owns.
     */
    public function updateStatus(UpdateOrderStatusRequest $request, Order $order): RedirectResponse
    {
        return $this->transitionStatus(
            $order,
            $request->targetStatus(),
            $request->validated('note'),
            $request->user(),
        );
    }

    /**
     * Ask the customer for more information.
     */
    public function requestInformation(Request $request, Order $order): RedirectResponse
    {
        return $this->handleInformationRequest($request, $order);
    }

    /**
     * Download a file attached to the order.
     */
    public function download(Order $order, OrderFile $file): BinaryFileResponse
    {
        return $this->downloadOrderFile($order, $file);
    }

    /**
     * Show the form to deliver the finished work.
     */
    public function deliver(Order $order): View
    {
        return $this->showDeliverForm($order);
    }

    /**
     * Attach a result file to an order.
     */
    public function storeResult(Request $request, Order $order): RedirectResponse
    {
        return $this->storeOrderResult($request, $order);
    }

    /**
     * Send the finished work to the customer.
     */
    public function sendToCustomer(Request $request, Order $order): RedirectResponse
    {
        return $this->sendOrderToCustomer($request, $order);
    }

    /**
     * Workers may only manage orders assigned to them.
     */
    protected function ensureCanManageOrder(Order $order): void
    {
        $user = request()->user();

        abort_unless($user instanceof User && $order->isAssignedTo($user), 403);
    }
}
