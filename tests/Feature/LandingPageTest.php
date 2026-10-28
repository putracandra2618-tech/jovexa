<?php

namespace Tests\Feature;

use App\Models\Service;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_can_view_the_landing_page(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee(config('app.name'))
            ->assertSee('Jasa digital');
    }

    public function test_landing_page_renders_the_public_navigation_and_footer(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('navbar-public', escape: false)
            ->assertSee('footer-public', escape: false);
    }

    public function test_public_navigation_uses_a_side_drawer_and_drops_the_services_link(): void
    {
        $this->get(route('home'))
            ->assertOk()
            ->assertSee('data-bs-target="#publicNavDrawer"', escape: false)
            ->assertSee('offcanvas-start jx-drawer', escape: false)
            ->assertSee('Cara Pesan', escape: false)
            ->assertDontSee('>Layanan<', escape: false);
    }

    public function test_landing_page_shows_the_flashed_success_message(): void
    {
        $this->withSession(['success' => 'Pesanan berhasil dibuat.'])
            ->get(route('home'))
            ->assertOk()
            ->assertSee('Pesanan berhasil dibuat.')
            ->assertSee('alert-custom-success', escape: false);
    }

    public function test_landing_page_lists_published_services(): void
    {
        Service::factory()->published()->create(['name' => 'Bantuan Skripsi']);

        $this->get(route('home'))
            ->assertOk()
            ->assertSee('Bantuan Skripsi');
    }
}
