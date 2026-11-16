<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_profile_page_renders(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->get(route('admin.profile.edit'))
            ->assertOk()
            ->assertSee('Profil Saya');
    }

    public function test_admin_can_change_their_password(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->patch(route('admin.profile.update'), [
                'old_password' => 'password',
                'password' => 'password-baru-123',
                'password_confirmation' => 'password-baru-123',
            ])
            ->assertRedirect(route('admin.profile.edit'))
            ->assertSessionHasNoErrors();

        $this->assertTrue(Hash::check('password-baru-123', $admin->fresh()->password));
    }

    public function test_admin_password_change_requires_the_current_password(): void
    {
        $admin = User::factory()->admin()->create();

        $this->actingAs($admin)
            ->patch(route('admin.profile.update'), [
                'old_password' => 'password-salah',
                'password' => 'password-baru-123',
                'password_confirmation' => 'password-baru-123',
            ])
            ->assertSessionHas('error');

        $this->assertTrue(Hash::check('password', $admin->fresh()->password));
    }
}
