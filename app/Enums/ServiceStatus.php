<?php

namespace App\Enums;

enum ServiceStatus: string
{
    case Draft = 'draft';
    case Published = 'published';
    case Archived = 'archived';

    /**
     * The human readable label used across the interface.
     */
    public function label(): string
    {
        return match ($this) {
            self::Draft => 'Draf',
            self::Published => 'Tayang',
            self::Archived => 'Arsip',
        };
    }

    /**
     * The Bootstrap contextual class used by status badges.
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::Draft => 'pending',
            self::Published => 'success',
            self::Archived => 'failed',
        };
    }

    /**
     * Determine whether the service is visible to customers.
     */
    public function isVisibleToCustomers(): bool
    {
        return $this === self::Published;
    }
}
