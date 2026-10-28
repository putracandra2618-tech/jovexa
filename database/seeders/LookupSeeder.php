<?php

namespace Database\Seeders;

use App\Enums\OrderStatus;
use App\Enums\PaymentStatus;
use App\Enums\ServiceStatus;
use App\Enums\UserRole;
use App\Models\Lookup\OrderFileKind;
use App\Models\Lookup\OrderStatus as OrderStatusLookup;
use App\Models\Lookup\PaymentStatus as PaymentStatusLookup;
use App\Models\Lookup\ServiceStatus as ServiceStatusLookup;
use App\Models\Role;
use Illuminate\Database\Seeder;

class LookupSeeder extends Seeder
{
    /**
     * Seed the master data every domain table references.
     */
    public function run(): void
    {
        foreach (UserRole::cases() as $role) {
            Role::ensureIdFor($role->value, $role->label());
        }

        foreach (ServiceStatus::cases() as $status) {
            ServiceStatusLookup::ensureIdFor($status->value, $status->label());
        }

        foreach (OrderStatus::cases() as $status) {
            OrderStatusLookup::ensureIdFor($status->value, $status->label());
        }

        foreach (PaymentStatus::cases() as $status) {
            PaymentStatusLookup::ensureIdFor($status->value, $status->label());
        }

        OrderFileKind::ensureIdFor('requirement', 'Persyaratan');
        OrderFileKind::ensureIdFor('result', 'Hasil');
    }
}
