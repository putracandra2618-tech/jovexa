<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\UserImpersonation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ImpersonationTest extends TestCase
{
    use RefreshDatabase;

    public function test_super_admin_can_impersonate_a_customer_and_the_action_is_audited(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $customer = User::factory()->customer()->create([
            'name' => 'Diana Putri',
            'email' => 'diana@mail.test',
        ]);

        $response = $this->actingAs($superAdmin)
            ->post(route('admin.users.impersonate', $customer));

        $response->assertRedirect(route('customer.dashboard'));
        $this->assertAuthenticatedAs($customer);
        $this->assertSame($superAdmin->getKey(), session('impersonator_id'));

        $audit = UserImpersonation::query()->sole();

        $this->assertSame($superAdmin->getKey(), $audit->impersonator_id);
        $this->assertSame($customer->getKey(), $audit->impersonated_id);
        $this->assertSame('Diana Putri', $audit->impersonated_name);
        $this->assertSame('diana@mail.test', $audit->impersonated_email);
        $this->assertNotNull($audit->started_at);
        $this->assertNull($audit->ended_at);
        $this->assertSame(session('impersonation_id'), $audit->getKey());
    }

    public function test_impersonating_a_worker_redirects_to_the_worker_dashboard(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $worker = User::factory()->worker()->create();

        $this->actingAs($superAdmin)
            ->post(route('admin.users.impersonate', $worker))
            ->assertRedirect(route('worker.dashboard'));

        $this->assertAuthenticatedAs($worker);
    }

    public function test_regular_admin_cannot_impersonate_users(): void
    {
        $admin = User::factory()->admin()->create();
        $customer = User::factory()->customer()->create();

        $this->actingAs($admin)
            ->post(route('admin.users.impersonate', $customer))
            ->assertForbidden();

        $this->assertDatabaseCount('user_impersonations', 0);
    }

    public function test_super_admin_cannot_impersonate_another_super_admin(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $otherSuperAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)
            ->from(route('admin.users.index'))
            ->post(route('admin.users.impersonate', $otherSuperAdmin))
            ->assertRedirect(route('admin.users.index'))
            ->assertSessionHas('error');

        $this->assertAuthenticatedAs($superAdmin);
        $this->assertDatabaseCount('user_impersonations', 0);
    }

    public function test_super_admin_cannot_impersonate_themselves(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();

        $this->actingAs($superAdmin)
            ->post(route('admin.users.impersonate', $superAdmin))
            ->assertSessionHas('error');

        $this->assertAuthenticatedAs($superAdmin);
        $this->assertDatabaseCount('user_impersonations', 0);
    }

    public function test_nested_impersonation_is_rejected(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $customer = User::factory()->customer()->create();
        $worker = User::factory()->worker()->create();

        $this->actingAs($superAdmin)
            ->withSession(['impersonator_id' => $superAdmin->getKey()])
            ->post(route('admin.users.impersonate', $worker))
            ->assertSessionHas('error');

        $this->assertAuthenticatedAs($superAdmin);
        $this->assertDatabaseCount('user_impersonations', 0);
    }

    public function test_super_admin_can_return_to_their_own_account_and_the_audit_is_closed(): void
    {
        $superAdmin = User::factory()->superAdmin()->create();
        $customer = User::factory()->customer()->create();

        $this->actingAs($superAdmin)
            ->post(route('admin.users.impersonate', $customer));

        $audit = UserImpersonation::query()->sole();

        $this->post(route('impersonation.stop'))
            ->assertRedirect(route('admin.dashboard'))
            ->assertSessionHas('success');

        $this->assertAuthenticatedAs($superAdmin);
        $this->assertNull(session('impersonator_id'));
        $this->assertNull(session('impersonation_id'));
        $this->assertNotNull($audit->fresh()->ended_at);
    }

    public function test_stopping_without_an_active_impersonation_is_forbidden(): void
    {
        $this->actingAs(User::factory()->customer()->create())
            ->post(route('impersonation.stop'))
            ->assertForbidden();
    }
}
