<?php

namespace Database\Seeders;

use App\Enums\UserRole;
use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class SuperAdminSeeder extends Seeder
{
    /**
     * Create the initial super admin account from the environment credentials.
     */
    public function run(): void
    {
        $email = env('SUPER_ADMIN_EMAIL');
        $password = env('SUPER_ADMIN_PASSWORD');

        if ($email === null || $password === null) {
            $this->command?->warn('SUPER_ADMIN_EMAIL atau SUPER_ADMIN_PASSWORD belum diisi, super admin tidak dibuat.');

            return;
        }

        $superAdmin = User::query()->firstOrNew(['email' => $email]);

        $superAdmin->forceFill([
            'name' => env('SUPER_ADMIN_NAME', 'Putra Candra'),
            'password' => $password,
            'email_verified_at' => $superAdmin->email_verified_at ?? now(),
            'role_id' => Role::ensureIdFor(UserRole::SuperAdmin->value, UserRole::SuperAdmin->label()),
        ])->save();

        $this->command?->info("Super admin siap login: {$superAdmin->email}");
    }
}
