<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_can_update_their_name_without_a_password(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)
            ->patch(route('customer.profile.update'), [
                'name' => 'Nama Baru',
                'email' => $customer->email,
            ])
            ->assertRedirect(route('customer.profile.edit'))
            ->assertSessionHasNoErrors();

        $this->assertSame('Nama Baru', $customer->fresh()->name);
    }

    public function test_changing_the_email_requires_the_current_password(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)
            ->patch(route('customer.profile.update'), [
                'name' => $customer->name,
                'email' => 'email-baru@example.test',
            ])
            ->assertSessionHasErrors('old_password');

        $this->assertSame($customer->email, $customer->fresh()->email);
    }

    public function test_changing_the_email_rejects_a_wrong_current_password(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)
            ->patch(route('customer.profile.update'), [
                'name' => $customer->name,
                'email' => 'email-baru@example.test',
                'old_password' => 'password-salah',
            ])
            ->assertSessionHasErrors('old_password');

        $this->assertSame($customer->email, $customer->fresh()->email);
    }

    public function test_customer_can_change_the_email_with_the_current_password(): void
    {
        $customer = User::factory()->customer()->create();

        $this->actingAs($customer)
            ->patch(route('customer.profile.update'), [
                'name' => $customer->name,
                'email' => 'email-baru@example.test',
                'old_password' => 'password',
            ])
            ->assertRedirect(route('customer.profile.edit'))
            ->assertSessionHasNoErrors();

        $this->assertSame('email-baru@example.test', $customer->fresh()->email);
    }
}
