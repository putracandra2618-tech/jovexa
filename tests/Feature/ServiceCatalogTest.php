<?php

namespace Tests\Feature;

use App\Enums\ServiceStatus;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ServiceCatalogTest extends TestCase
{
    use RefreshDatabase;

    public function test_guests_can_browse_the_catalogue(): void
    {
        Service::factory()->create(['name' => 'Pembuatan Skripsi Word']);
        Service::factory()->draft()->create(['name' => 'Jasa Rahasia Belum Tayang']);
        Service::factory()->archived()->create(['name' => 'Jasa Lama Diarsipkan']);

        $this->get(route('services.index'))
            ->assertOk()
            ->assertSee('Pembuatan Skripsi Word')
            ->assertDontSee('Jasa Rahasia Belum Tayang')
            ->assertDontSee('Jasa Lama Diarsipkan');
    }

    public function test_catalogue_renders_the_empty_state_without_published_services(): void
    {
        $this->get(route('services.index'))
            ->assertOk()
            ->assertSee('Belum ada jasa yang tayang');
    }

    public function test_guests_can_open_a_published_service_detail(): void
    {
        $service = Service::factory()->create([
            'name' => 'Jasa Coding Web',
            'price' => 1200000,
            'estimated_duration' => 10,
            'requirements' => 'Kebutuhan fitur dan referensi desain.',
        ]);

        $this->get(route('services.show', $service))
            ->assertOk()
            ->assertSee('Jasa Coding Web')
            ->assertSee('Rp1.200.000')
            ->assertSee('10 hari kerja')
            ->assertSee('Kebutuhan fitur dan referensi desain.')
            ->assertSee('Masuk untuk Memesan');
    }

    public function test_unpublished_services_are_not_reachable(): void
    {
        $this->get(route('services.show', Service::factory()->draft()->create()))->assertNotFound();
        $this->get(route('services.show', Service::factory()->archived()->create()))->assertNotFound();
        $this->get(route('services.show', 999999))->assertNotFound();
    }

    public function test_authenticated_customers_see_the_order_call_to_action(): void
    {
        $service = Service::factory()->create();

        $this->actingAs(User::factory()->customer()->create())
            ->get(route('services.show', $service))
            ->assertOk()
            ->assertSee('Pesan Jasa');
    }

    public function test_authenticated_customers_see_the_customer_navigation_in_the_public_header(): void
    {
        $this->actingAs(User::factory()->customer()->create())
            ->get(route('services.index'))
            ->assertOk()
            ->assertSee('jx-user-btn', escape: false)
            ->assertSee('Pesanan Saya');
    }

    public function test_admins_see_the_admin_dashboard_link_on_public_pages(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('services.index'))
            ->assertOk()
            ->assertSee(route('admin.dashboard'), escape: false)
            ->assertDontSee('Pesanan Saya');
    }

    public function test_guests_do_not_see_the_customer_navigation_on_public_pages(): void
    {
        $this->get(route('services.index'))
            ->assertOk()
            ->assertSee('Cara Pesan')
            ->assertDontSee('Pesanan Saya');
    }

    public function test_catalogue_paginates_published_services(): void
    {
        Service::factory()->count(11)->create();

        $this->get(route('services.index'))
            ->assertOk()
            ->assertViewHas('services', fn ($services) => $services->total() === 11 && $services->count() === 9);
    }

    public function test_catalogue_can_be_searched_by_name(): void
    {
        Service::factory()->create(['name' => 'Pembuatan Skripsi Word']);
        Service::factory()->create(['name' => 'Jasa Coding Web']);

        $this->get(route('services.index', ['q' => 'skripsi']))
            ->assertOk()
            ->assertSee('Pembuatan Skripsi Word')
            ->assertDontSee('Jasa Coding Web')
            ->assertViewHas('search', 'skripsi');
    }

    public function test_catalogue_search_falls_back_to_the_full_list_when_nothing_matches(): void
    {
        Service::factory()->create(['name' => 'Jasa Coding Web']);

        $this->get(route('services.index', ['q' => 'tidak-ada-hasilnya']))
            ->assertOk()
            ->assertSee('Jasa tidak ditemukan')
            ->assertDontSee('Jasa Coding Web');
    }

    public function test_service_detail_suggests_other_published_services(): void
    {
        $service = Service::factory()->create(['name' => 'Jasa Coding Web']);
        Service::factory()->create(['name' => 'Bantuan Tugas Kuliah']);

        $this->get(route('services.show', $service))
            ->assertOk()
            ->assertSee('Bantuan Tugas Kuliah')
            ->assertViewHas('relatedServices', fn ($related) => $related->count() === 1);
    }

    public function test_catalogue_exposes_the_live_search_hooks(): void
    {
        $service = Service::factory()->create(['name' => 'Jasa Coding Web']);

        $this->get(route('services.index'))
            ->assertOk()
            ->assertSee('data-live-search', escape: false)
            ->assertSee('data-live-search-target', escape: false)
            ->assertSee('data-service-name="'.$service->name.'"', escape: false);
    }

    public function test_service_exposes_published_scope_and_metadata(): void
    {
        $service = Service::factory()->create(['price' => 250000]);

        $this->assertSame(ServiceStatus::Published, $service->status);
        $this->assertSame(250000, $service->price);
        $this->assertTrue($service->status->isVisibleToCustomers());
        $this->assertSame(1, Service::query()->published()->count());
    }
}
