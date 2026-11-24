<?php

namespace Tests\Feature;

use App\Enums\UserRole;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class BootstrapAdminCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_an_admin_with_a_hidden_password(): void
    {
        $this->artisan('bootstrap:admin')
            ->expectsQuestion('Nama', 'Admin Jovexa')
            ->expectsQuestion('Email', 'admin@jovexa.test')
            ->expectsQuestion('Role', UserRole::Admin->value)
            ->expectsQuestion('Password (minimal 8 karakter)', 'rahasia123')
            ->expectsQuestion('Ulangi password', 'rahasia123')
            ->expectsOutputToContain('Admin siap login: admin@jovexa.test')
            ->assertSuccessful();

        $admin = User::query()->where('email', 'admin@jovexa.test')->firstOrFail();

        $this->assertTrue($admin->hasRole(UserRole::Admin));
        $this->assertTrue(Hash::check('rahasia123', $admin->password));
        $this->assertNotNull($admin->email_verified_at);
    }

    public function test_it_creates_a_super_admin(): void
    {
        $this->artisan('bootstrap:admin')
            ->expectsQuestion('Nama', 'Putra Candra')
            ->expectsQuestion('Email', 'superadmin@jovexa.test')
            ->expectsQuestion('Role', UserRole::SuperAdmin->value)
            ->expectsQuestion('Password (minimal 8 karakter)', 'rahasia123')
            ->expectsQuestion('Ulangi password', 'rahasia123')
            ->assertSuccessful();

        $superAdmin = User::query()->where('email', 'superadmin@jovexa.test')->firstOrFail();

        $this->assertTrue($superAdmin->hasRole(UserRole::SuperAdmin));
    }

    public function test_it_rejects_a_short_password_without_creating_a_user(): void
    {
        $this->artisan('bootstrap:admin')
            ->expectsQuestion('Nama', 'Admin Jovexa')
            ->expectsQuestion('Email', 'admin@jovexa.test')
            ->expectsQuestion('Role', UserRole::Admin->value)
            ->expectsQuestion('Password (minimal 8 karakter)', 'pendek')
            ->expectsOutput('Password harus minimal 8 karakter.')
            ->assertFailed();

        $this->assertDatabaseCount('users', 0);
    }

    public function test_it_rejects_a_mismatched_confirmation(): void
    {
        $this->artisan('bootstrap:admin')
            ->expectsQuestion('Nama', 'Admin Jovexa')
            ->expectsQuestion('Email', 'admin@jovexa.test')
            ->expectsQuestion('Role', UserRole::Admin->value)
            ->expectsQuestion('Password (minimal 8 karakter)', 'rahasia123')
            ->expectsQuestion('Ulangi password', 'berbeda123')
            ->expectsOutput('Password dan konfirmasi tidak sama.')
            ->assertFailed();

        $this->assertDatabaseCount('users', 0);
    }
}
