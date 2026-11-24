<?php

namespace App\Services;

use App\Enums\UserRole;
use App\Models\Role;
use App\Models\User;

class AdminBootstrapService
{
    /**
     * Create or update a privileged account using explicit credentials.
     */
    public function create(string $name, string $email, string $password, UserRole $role): User
    {
        $user = User::query()->firstOrNew(['email' => $email]);

        $user->forceFill([
            'name' => $name,
            'password' => $password,
            'email_verified_at' => $user->email_verified_at ?? now(),
            'role_id' => Role::ensureIdFor($role->value, $role->label()),
        ])->save();

        return $user;
    }
}
