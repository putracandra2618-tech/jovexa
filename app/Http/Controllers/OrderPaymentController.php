<?php

namespace App\Http\Controllers;

use App\Enums\UserRole;
use App\Models\Order;
use App\Services\PaymentService;
use App\Services\TripayClient;
use App\Services\TripayException;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class OrderPaymentController extends Controller
{
    public function __construct(
        private readonly PaymentService $payments,
        private readonly TripayClient $tripay,
    ) {}

    /**
     * Show the checkout screen for an unpaid order.
     */
    public function create(Request $request, Order $order): View
    {
        $this->authorizeOrderOwner($request, $order);

        abort_if(! $order->isPayable(), 404);

        return view('orders.pay', [
            'order' => $order->load(['service', 'latestPayment.statusRecord']),
            'channels' => $this->tripay->availableChannels(),
            'gatewayReady' => $this->tripay->isConfigured(),
            'channelsAreGuess' => $this->tripay->channelsAreFallback(),
        ]);
    }

    /**
     * Send the customer to the payment gateway.
     */
    public function store(Request $request, Order $order): RedirectResponse
    {
        $this->authorizeOrderOwner($request, $order);

        abort_if(! $order->isPayable(), 404);

        $validated = $request->validate([
            'payment_method' => ['required', 'string', 'max:32'],
        ], [
            'payment_method.required' => 'Pilih metode pembayaran terlebih dahulu.',
        ]);

        if (! $this->tripay->isConfigured()) {
            return back()->with(
                'warning',
                'Kredensial Tripay belum dikonfigurasi, jadi pesanan belum bisa dibayar lewat gateway.'
            );
        }

        try {
            $checkoutUrl = $this->payments->initiate($order, $validated['payment_method']);
        } catch (TripayException $exception) {
            return back()->withErrors(['payment' => $exception->getMessage()]);
        } catch (ValidationException $exception) {
            return back()->withErrors($exception->errors())->withInput();
        }

        if (blank($checkoutUrl)) {
            return back()->with(
                'warning',
                'Gateway tidak mengembalikan URL pembayaran. Coba beberapa saat lagi.'
            );
        }

        return redirect()->away($checkoutUrl);
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
