<?php

namespace App\Support;

use App\Models\Order;

class OrderNumberGenerator
{
    /**
     * Build a unique, human readable order number such as JVX-20260929-4F2A.
     */
    public function generate(): string
    {
        do {
            $candidate = 'JVX-'.now()->format('Ymd').'-'.strtoupper(bin2hex(random_bytes(2)));
        } while (Order::query()->where('order_number', $candidate)->exists());

        return $candidate;
    }
}
