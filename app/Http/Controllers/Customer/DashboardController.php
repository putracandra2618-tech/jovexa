<?php

namespace App\Http\Controllers\Customer;

use App\Enums\OrderStatus;
use App\Http\Controllers\Controller;
use App\Models\Order;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    /**
     * Show the customer dashboard with their order overview.
     */
    public function __invoke(Request $request): View
    {
        $orders = $request->user()->orders()->with(['service', 'statusRecord'])->latest()->get();

        return view('customer.dashboard', [
            'activeCount' => $orders->filter(fn (Order $order): bool => in_array(
                $order->status,
                [OrderStatus::WaitingVerification, OrderStatus::WaitingCustomer, OrderStatus::InProgress, OrderStatus::Revision],
                strict: true,
            ))->count(),
            'pendingPaymentCount' => $orders->filter(fn (Order $order): bool => $order->status === OrderStatus::PendingPayment)->count(),
            'deliveredCount' => $orders->filter(fn (Order $order): bool => $order->status === OrderStatus::Delivered)->count(),
            'recentOrders' => $orders->take(5),
        ]);
    }
}
