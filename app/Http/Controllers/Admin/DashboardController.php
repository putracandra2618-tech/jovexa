<?php

namespace App\Http\Controllers\Admin;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use App\Models\Service;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Show the admin dashboard with the order pipeline overview.
     */
    public function __invoke(Request $request): View
    {
        $waitingVerification = Order::query()->whereStatus(OrderStatus::WaitingVerification)->count();
        $queued = Order::query()->whereStatus(OrderStatus::Queued)->count();
        $inProgress = Order::query()->whereStatus(OrderStatus::InProgress)->count();
        $waitingCustomer = Order::query()->whereStatus(OrderStatus::WaitingCustomer)->count();
        $revision = Order::query()->whereStatus(OrderStatus::Revision)->count();

        return view('admin.dashboard', [
            'waitingVerificationCount' => $waitingVerification,
            'queuedCount' => $queued,
            'inProgressCount' => $inProgress,
            'waitingCustomerCount' => $waitingCustomer,
            'revisionCount' => $revision,
            'newOrders' => Order::query()
                ->with(['service', 'user', 'worker', 'statusRecord'])
                ->withStatus([OrderStatus::WaitingVerification, OrderStatus::Queued, OrderStatus::WaitingCustomer, OrderStatus::InProgress, OrderStatus::Revision])
                ->latest()
                ->take(8)
                ->get(),
            'revenue' => Order::query()
                ->withStatus([OrderStatus::Completed, OrderStatus::Delivered])
                ->sum('total_price'),
            'serviceCount' => Service::query()->published()->count(),
        ]);
    }
}
