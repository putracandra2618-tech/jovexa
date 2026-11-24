<?php

namespace App\Console\Commands;

use App\Enums\UserRole;
use App\Services\AdminBootstrapService;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Validator;

#[Signature('bootstrap:admin')]
#[Description('Buat akun Admin atau Super Admin awal tanpa menyimpan password di .env')]
class BootstrapAdminCommand extends Command
{
    /**
     * Execute the console command.
     */
    public function handle(AdminBootstrapService $admins): int
    {
        $name = (string) $this->ask('Nama');
        $email = (string) $this->ask('Email');

        $validator = Validator::make(
            ['name' => $name, 'email' => $email],
            ['name' => ['required'], 'email' => ['required', 'email']],
        );

        if ($validator->fails()) {
            $this->error((string) $validator->errors()->first());

            return self::FAILURE;
        }

        $role = UserRole::from((string) $this->choice(
            'Role',
            array_map(fn (UserRole $role): string => $role->value, [UserRole::Admin, UserRole::SuperAdmin]),
            UserRole::Admin->value,
        ));

        $password = $this->secret('Password (minimal 8 karakter)');

        if (! is_string($password) || strlen($password) < 8) {
            $this->error('Password harus minimal 8 karakter.');

            return self::FAILURE;
        }

        if ($password !== $this->secret('Ulangi password')) {
            $this->error('Password dan konfirmasi tidak sama.');

            return self::FAILURE;
        }

        $user = $admins->create($name, $email, $password, $role);

        $this->info(sprintf('%s siap login: %s', $role->label(), $user->email));

        return self::SUCCESS;
    }
}
