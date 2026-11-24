<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Services\AdminBootstrapService;
use Illuminate\Database\Seeder;

class SuperAdminSeeder extends Seeder
{
    /**
     * Create the initial super admin account from the environment credentials.
     */
    public function run(AdminBootstrapService $admins): void
    {
        $email = env('SUPER_ADMIN_EMAIL');
        $password = env('SUPER_ADMIN_PASSWORD');

        if ($email === null || $password === null) {
            $this->command?->warn('SUPER_ADMIN_EMAIL atau SUPER_ADMIN_PASSWORD belum diisi, super admin tidak dibuat.');

            return;
        }

        $admins->create(
            name: (string) env('SUPER_ADMIN_NAME', 'Putra Candra'),
            email: (string) $email,
            password: (string) $password,
            role: UserRole::SuperAdmin,
        );

        $this->command?->info("Super admin siap login: {$email}");
    }
}
