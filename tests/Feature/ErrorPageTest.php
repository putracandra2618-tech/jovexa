<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ErrorPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_unknown_urls_render_the_branded_404_page(): void
    {
        $response = $this->get('/halaman-yang-tidak-ada');

        $response->assertNotFound()
            ->assertSee('Halaman Tidak Ditemukan')
            ->assertSee('Kembali ke Beranda');
    }

    public function test_unknown_service_slugs_render_the_404_page(): void
    {
        $this->get('/jasa/jasa-tidak-ada')
            ->assertNotFound()
            ->assertSee('Halaman Tidak Ditemukan');
    }

    public function test_the_404_page_does_not_leak_a_stack_trace(): void
    {
        $this->get('/halaman-yang-tidak-ada')
            ->assertNotFound()
            ->assertDontSee('vendor/laravel/framework', escape: false);
    }

    public function test_forbidden_pages_render_the_branded_403_page(): void
    {
        $this->actingAs(User::factory()->customer()->create())
            ->get(route('admin.services.index'))
            ->assertForbidden()
            ->assertSee('Akses Ditolak');
    }
}
