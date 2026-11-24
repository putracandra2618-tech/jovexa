<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Services\AdminBootstrapService;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    /**
     * Create the initial admin account from the environment credentials.
     */
    public function run(AdminBootstrapService $admins): void
    {
        $email = env('ADMIN_EMAIL');
        $password = env('ADMIN_PASSWORD');

        if ($email === null || $password === null) {
            $this->command?->warn('ADMIN_EMAIL atau ADMIN_PASSWORD belum diisi, admin tidak dibuat.');

            return;
        }

        $admins->create(
            name: (string) env('ADMIN_NAME', 'Admin Jovexa'),
            email: (string) $email,
            password: (string) $password,
            role: UserRole::Admin,
        );

        $this->command?->info("Admin siap login: {$email}");
    }
}
