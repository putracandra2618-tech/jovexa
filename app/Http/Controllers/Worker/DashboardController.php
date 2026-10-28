<?php

namespace App\Http\Controllers\Worker;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Services\WorkerOrderService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(private readonly WorkerOrderService $workerOrders) {}

    /**
     * Show the worker dashboard and their current workload.
     */
    public function __invoke(Request $request): View
    {
        $worker = $request->user();

        $activeOrders = Order::query()
            ->activeForWorker($worker)
            ->with(['service', 'user', 'statusRecord'])
            ->latest('worker_claimed_at')
            ->take(5)
            ->get();

        $activeCount = $this->workerOrders->activeOrderCount($worker);

        return view('worker.dashboard', [
            'activeOrders' => $activeOrders,
            'activeCount' => $activeCount,
            'availableCount' => Order::query()->availableForWorkers()->count(),
            'maxActiveOrders' => WorkerOrderService::MAX_ACTIVE_ORDERS,
            'completedCount' => Order::query()
                ->assignedTo($worker)
                ->withStatus([OrderStatus::Delivered])
                ->count(),
        ]);
    }
}
