<?php

namespace App\Enums;

enum PaymentStatus: string
{
    case Pending = 'pending';
    case Paid = 'paid';
    case Failed = 'failed';
    case Expired = 'expired';

    /**
     * The human readable label used across the interface.
     */
    public function label(): string
    {
        return match ($this) {
            self::Pending => 'Menunggu Pembayaran',
            self::Paid => 'Berhasil',
            self::Failed => 'Gagal',
            self::Expired => 'Kedaluwarsa',
        };
    }

    /**
     * The Bootstrap contextual class used by status badges.
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::Pending => 'pending',
            self::Paid => 'success',
            self::Failed, self::Expired => 'failed',
        };
    }

    /**
     * Determine whether the order may enter the verification stage.
     */
    public function isSuccessful(): bool
    {
        return $this === self::Paid;
    }
}
