<?php

namespace App\Http\Controllers\Admin;

use App\Enums\ServiceStatus;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\ServiceRequest;
use App\Models\Lookup\ServiceStatus as ServiceStatusLookup;
use App\Models\Service;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ServiceController extends Controller
{
    /**
     * Show the service management table.
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));

        $services = Service::query()
            ->with('statusRecord')
            ->when($search !== '', fn ($query) => $query->search($search))
            ->when($request->filled('status'), function ($query) use ($request): void {
                $status = ServiceStatus::tryFrom($request->string('status')->toString());

                if ($status !== null) {
                    $query->whereStatus($status);
                }
            })
            ->latest()
            ->paginate(10)
            ->withQueryString();

        return view('admin.services.index', [
            'services' => $services,
            'statuses' => ServiceStatus::cases(),
            'search' => $search,
        ]);
    }

    /**
     * Show the form to create a new service.
     */
    public function create(): View
    {
        return view('admin.services.create', [
            'service' => new Service([
                'service_status_id' => ServiceStatusLookup::idFor(ServiceStatus::Draft->value),
            ]),
            'statuses' => ServiceStatus::cases(),
        ]);
    }

    /**
     * Store a newly created service.
     */
    public function store(ServiceRequest $request): RedirectResponse
    {
        $service = Service::query()->create($request->serviceAttributes());

        return redirect()
            ->route('admin.services.index')
            ->with('success', "Jasa \"{$service->name}\" berhasil ditambahkan.");
    }

    /**
     * Show the form to edit an existing service.
     */
    public function edit(Service $service): View
    {
        return view('admin.services.edit', [
            'service' => $service,
            'statuses' => ServiceStatus::cases(),
        ]);
    }

    /**
     * Update the given service.
     */
    public function update(ServiceRequest $request, Service $service): RedirectResponse
    {
        $service->update($request->serviceAttributes());

        return redirect()
            ->route('admin.services.index')
            ->with('success', "Jasa \"{$service->name}\" berhasil diperbarui.");
    }

    /**
     * Archive the service so it no longer appears in the public catalogue.
     */
    public function archive(Service $service): RedirectResponse
    {
        $service->update(['service_status_id' => ServiceStatusLookup::idFor(ServiceStatus::Archived->value)]);

        return redirect()
            ->route('admin.services.index')
            ->with('success', "Jasa \"{$service->name}\" berhasil diarsipkan.");
    }

    /**
     * Return an archived service to the catalogue.
     */
    public function publish(Service $service): RedirectResponse
    {
        $service->update(['service_status_id' => ServiceStatusLookup::idFor(ServiceStatus::Published->value)]);

        return redirect()
            ->route('admin.services.index')
            ->with('success', "Jasa \"{$service->name}\" berhasil ditayangkan kembali.");
    }
}
