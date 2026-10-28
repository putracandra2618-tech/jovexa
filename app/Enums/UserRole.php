<?php

namespace App\Enums;

enum UserRole: string
{
    case Customer = 'customer';
    case Worker = 'worker';
    case Admin = 'admin';
    case SuperAdmin = 'super_admin';

    /**
     * The human readable label used across the interface.
     */
    public function label(): string
    {
        return match ($this) {
            self::Customer => 'Customer',
            self::Worker => 'Worker',
            self::Admin => 'Admin',
            self::SuperAdmin => 'Super Admin',
        };
    }

    /**
     * The home route a user with this role should land on after logging in.
     */
    public function homeRoute(): string
    {
        return match ($this) {
            self::Customer => 'customer.dashboard',
            self::Worker => 'worker.dashboard',
            self::Admin, self::SuperAdmin => 'admin.dashboard',
        };
    }

    /**
     * The badge variant class used in the admin table.
     */
    public function badgeClass(): string
    {
        return match ($this) {
            self::SuperAdmin, self::Admin => 'success',
            self::Worker, self::Customer => 'pending',
        };
    }
}
