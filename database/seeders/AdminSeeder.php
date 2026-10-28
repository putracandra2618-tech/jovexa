<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class AdminSeeder extends Seeder
{
    /**
     * Create the initial admin account from the environment credentials.
     */
    public function run(): void
    {
        $email = env('ADMIN_EMAIL');
        $password = env('ADMIN_PASSWORD');

        if ($email === null || $password === null) {
            $this->command?->warn('ADMIN_EMAIL atau ADMIN_PASSWORD belum diisi, admin tidak dibuat.');

            return;
        }

        $admin = User::query()->firstOrNew(['email' => $email]);

        $admin->forceFill([
            'name' => env('ADMIN_NAME', 'Admin Jovexa'),
            'password' => $password,
            'email_verified_at' => $admin->email_verified_at ?? now(),
            'role_id' => Role::ensureIdFor(UserRole::Admin->value, UserRole::Admin->label()),
        ])->save();

        $this->command?->info("Admin siap login: {$admin->email}");
    }
}
