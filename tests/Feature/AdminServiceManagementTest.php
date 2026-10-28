<?php

namespace Tests\Feature;

use App\Enums\ServiceStatus;
use App\Models\Service;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AdminServiceManagementTest extends TestCase
{
    use RefreshDatabase;

    /**
     * @return array<string, mixed>
     */
    private function payload(array $overrides = []): array
    {
        return [
            'name' => 'Pembuatan Skripsi Word',
            'slug' => '',
            'description' => 'Menyusun skripsi dari nol sampai siap sidang beserta daftar pustaka.',
            'price' => 750000,
            'estimated_duration' => 7,
            'requirements' => 'Topik yang sudah disetujui.',
            'status' => ServiceStatus::Published->value,
            ...$overrides,
        ];
    }

    public function test_admin_can_open_the_service_management_screen(): void
    {
        $service = Service::factory()->create(['name' => 'Jasa Coding Web']);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.services.index'))
            ->assertOk()
            ->assertSee('Kelola Jasa')
            ->assertSee('Jasa Coding Web');
    }

    public function test_service_list_can_be_searched_by_name_or_slug(): void
    {
        $target = Service::factory()->create([
            'name' => 'Pembuatan Skripsi Word',
            'slug' => 'pembuatan-skripsi-word',
        ]);
        $other = Service::factory()->create(['name' => 'Jasa Coding Web']);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.services.index', ['q' => 'pembuatan-skripsi']))
            ->assertOk()
            ->assertSee($target->name)
            ->assertDontSee($other->name)
            ->assertViewHas('search', 'pembuatan-skripsi');
    }

    public function test_service_list_search_reports_when_nothing_matches(): void
    {
        Service::factory()->create(['name' => 'Jasa Coding Web']);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.services.index', ['q' => 'tidak-ada-hasilnya']))
            ->assertOk()
            ->assertSee('Jasa tidak ditemukan')
            ->assertDontSee('Belum ada jasa');
    }

    public function test_service_list_search_narrows_within_the_active_status_filter(): void
    {
        $draft = Service::factory()->draft()->create(['name' => 'Jasa Draft']);
        $published = Service::factory()->create(['name' => 'Jasa Tayang']);

        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.services.index', ['q' => 'Jasa', 'status' => ServiceStatus::Draft->value]))
            ->assertOk()
            ->assertSee($draft->name)
            ->assertDontSee($published->name);
    }

    public function test_service_list_topbar_search_targets_the_service_list(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->get(route('admin.services.index'))
            ->assertOk()
            ->assertSee('action="'.route('admin.services.index').'" data-admin-search', escape: false)
            ->assertSee('placeholder="Cari nama, slug, atau deskripsi jasa"', escape: false);
    }

    public function test_admin_can_create_a_service_and_the_slug_is_generated(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.services.store'), $this->payload())
            ->assertSessionHas('success')
            ->assertRedirect(route('admin.services.index'));

        $service = Service::query()->sole();

        $this->assertSame('pembuatan-skripsi-word', $service->slug);
        $this->assertSame(ServiceStatus::Published, $service->status);
        $this->assertSame(750000, $service->price);
    }

    public function test_admin_can_create_a_draft_service(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.services.store'), $this->payload([
                'name' => 'Jasa Internal',
                'status' => ServiceStatus::Draft->value,
            ]));

        $this->assertSame(ServiceStatus::Draft, Service::query()->sole()->status);
    }

    public function test_creating_a_service_validates_the_payload(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.services.store'), $this->payload([
                'name' => '',
                'slug' => 'Slug Tidak Valid',
                'description' => 'Pendek',
                'price' => 10,
                'status' => 'tidak-ada',
            ]))
            ->assertSessionHasErrors(['name', 'slug', 'description', 'price', 'status']);

        $this->assertSame(0, Service::query()->count());
    }

    public function test_creating_a_service_rejects_a_duplicate_slug(): void
    {
        Service::factory()->create(['slug' => 'jasa-coding-web']);

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.services.store'), $this->payload(['slug' => 'jasa-coding-web']))
            ->assertSessionHasErrors('slug');
    }

    public function test_creating_a_service_with_a_duplicate_name_reports_the_generated_slug_as_taken(): void
    {
        Service::factory()->create([
            'name' => 'Jasa Coding Web',
            'slug' => 'jasa-coding-web',
        ]);

        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.services.store'), $this->payload([
                'name' => 'Jasa Coding Web',
                'slug' => '',
            ]))
            ->assertSessionHasErrors('slug');

        $this->assertSame(1, Service::query()->count());
    }

    public function test_a_name_without_latin_characters_still_gets_a_usable_slug(): void
    {
        $this->actingAs(User::factory()->admin()->create())
            ->post(route('admin.services.store'), $this->payload([
                'name' => '日本語のロゴ',
                'slug' => '',
            ]))
            ->assertRedirect(route('admin.services.index'));

        $service = Service::query()->sole();

        $this->assertNotSame('', $service->slug);
        $this->assertMatchesRegularExpression('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $service->slug);
    }

    public function test_admin_can_update_a_service(): void
    {
        $service = Service::factory()->draft()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->put(route('admin.services.update', $service), $this->payload([
                'name' => 'Jasa Coding Web',
                'slug' => 'jasa-coding-web',
                'status' => ServiceStatus::Published->value,
            ]))
            ->assertSessionHas('success')
            ->assertRedirect(route('admin.services.index'));

        $service->refresh();

        $this->assertSame('Jasa Coding Web', $service->name);
        $this->assertSame(ServiceStatus::Published, $service->status);
    }

    public function test_updating_a_service_keeps_its_own_slug_available(): void
    {
        $service = Service::factory()->create(['slug' => 'jasa-coding-web']);

        $this->actingAs(User::factory()->admin()->create())
            ->put(route('admin.services.update', $service), $this->payload(['slug' => 'jasa-coding-web']))
            ->assertSessionHasNoErrors();

        $this->assertSame('jasa-coding-web', $service->fresh()->slug);
    }

    public function test_admin_can_archive_and_republish_a_service(): void
    {
        $service = Service::factory()->create();

        $this->actingAs(User::factory()->admin()->create())
            ->patch(route('admin.services.archive', $service))
            ->assertSessionHas('success');

        $this->assertSame(ServiceStatus::Archived, $service->fresh()->status);

        $this->actingAs(User::factory()->admin()->create())
            ->patch(route('admin.services.publish', $service))
            ->assertSessionHas('success');

        $this->assertSame(ServiceStatus::Published, $service->fresh()->status);
    }

    public function test_archived_services_disappear_from_the_catalogue(): void
    {
        $service = Service::factory()->create(['name' => 'Jasa Coding Web']);

        $this->actingAs(User::factory()->admin()->create())
            ->patch(route('admin.services.archive', $service));

        $this->flushSession();

        $this->get(route('services.index'))
            ->assertOk()
            ->assertDontSee('Jasa Coding Web')
            ->assertViewHas('services', fn ($services) => $services->count() === 0);

        $this->get(route('services.show', $service))->assertNotFound();
    }

    public function test_customers_and_guests_cannot_manage_services(): void
    {
        $service = Service::factory()->create();

        $this->get(route('admin.services.index'))->assertRedirect(route('login'));
        $this->get(route('admin.services.create'))->assertRedirect(route('login'));

        $this->actingAs(User::factory()->customer()->create())
            ->get(route('admin.services.index'))
            ->assertForbidden();

        $this->actingAs(User::factory()->customer()->create())
            ->post(route('admin.services.store'), $this->payload())
            ->assertForbidden();

        $this->actingAs(User::factory()->customer()->create())
            ->put(route('admin.services.update', $service), $this->payload())
            ->assertForbidden();

        $this->actingAs(User::factory()->customer()->create())
            ->patch(route('admin.services.archive', $service))
            ->assertForbidden();

        $this->assertSame(ServiceStatus::Published, $service->fresh()->status);
    }
}
