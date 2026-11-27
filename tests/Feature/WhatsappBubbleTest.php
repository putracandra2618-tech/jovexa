<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WhatsappBubbleTest extends TestCase
{
    use RefreshDatabase;

    public function test_customer_pages_show_the_whatsapp_bubble_when_a_number_is_configured(): void
    {
        config(['jovexa.contact.whatsapp' => '0812-3456-7890']);

        $this->actingAs(User::factory()->customer()->create())
            ->get(route('customer.dashboard'))
            ->assertOk()
            ->assertSee('jx-wa-bubble', escape: false)
            ->assertSee('https://wa.me/6281234567890', escape: false)
            ->assertSee('aria-label="Hubungi CS Jovexa lewat WhatsApp"', escape: false)
            ->assertSee('target="_blank"', escape: false)
            ->assertSee('rel="noopener"', escape: false);
    }

    public function test_customer_pages_hide_the_whatsapp_bubble_without_a_number(): void
    {
        config(['jovexa.contact.whatsapp' => null]);

        $this->actingAs(User::factory()->customer()->create())
            ->get(route('customer.dashboard'))
            ->assertOk()
            ->assertDontSee('jx-wa-bubble');
    }
}
