<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Concerns\ManagesStaffOrders;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\UpdateOrderStatusRequest;
use App\Models\Order;
use App\Models\OrderFile;
use App\Services\OrderFileDownloader;
use App\Services\OrderWorkflow;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class AdminOrderController extends Controller
{
    use ManagesStaffOrders;

    protected string $viewPrefix = 'admin.orders';

    public function __construct(
        private readonly OrderWorkflow $workflow,
        private readonly OrderFileDownloader $downloads,
    ) {}

    /**
     * Show the incoming order queue.
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));

        $orders = Order::query()
            ->with(['service', 'user', 'worker', 'statusRecord'])
            ->when($request->filled('status'), function ($query) use ($request): void {
                $status = OrderStatus::tryFrom($request->string('status')->toString());

                if ($status !== null) {
                    $query->whereStatus($status);
                }
            })
            ->when($search !== '', fn ($query) => $query->search($search))
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.orders.index', [
            'orders' => $orders,
            'statuses' => OrderStatus::cases(),
            'search' => $search,
        ]);
    }

    /**
     * Show the order detail from the staff point of view.
     */
    public function show(Order $order): View
    {
        $order->load([
            'service',
            'user',
            'worker',
            'files.kindRecord',
            'latestPayment.statusRecord',
            'statusHistory.author',
            'statusHistory.fromStatusRecord',
            'statusHistory.toStatusRecord',
        ]);

        return view('admin.orders.show', [
            'order' => $order,
        ]);
    }

    /**
     * Orders that need a revision before delivery.
     */
    public function revision(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));

        return view('admin.orders.revision', [
            'orders' => Order::query()
                ->with(['service', 'user', 'worker', 'statusRecord'])
                ->whereStatus(OrderStatus::Revision)
                ->when($search !== '', fn ($query) => $query->search($search))
                ->latest()
                ->paginate(10)
                ->withQueryString(),
            'search' => $search,
        ]);
    }

    /**
     * Orders that already reached a final state.
     */
    public function history(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));

        return view('admin.orders.history', [
            'orders' => Order::query()
                ->with(['service', 'user', 'worker', 'statusRecord'])
                ->withStatus([OrderStatus::Delivered, OrderStatus::Cancelled])
                ->when($search !== '', fn ($query) => $query->search($search))
                ->latest()
                ->paginate(10)
                ->withQueryString(),
            'search' => $search,
        ]);
    }

    /**
     * Move an order to the next stage of the workflow.
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
     * Ask the customer for the information needed to verify the order.
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
     * Cancel an order on behalf of the customer.
     */
    public function cancel(Request $request, Order $order): RedirectResponse
    {
        return $this->cancelOrder($request, $order);
    }

    /**
     * Admins may manage every order.
     */
    protected function ensureCanManageOrder(Order $order): void
    {
        //
    }
}
