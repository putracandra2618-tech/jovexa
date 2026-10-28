<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class ServiceController extends Controller
{
    /**
     * Show every service available for customers to browse.
     */
    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));

        $services = Service::query()
            ->published()
            ->when($search !== '', fn ($query) => $query->search($search))
            ->orderBy('price')
            ->paginate(9)
            ->withQueryString();

        return view('services.index', [
            'services' => $services,
            'search' => $search,
        ]);
    }

    /**
     * Show the detail of a single service.
     */
    public function show(Service $service): View
    {
        abort_unless($service->status->isVisibleToCustomers(), 404);

        $relatedServices = Service::query()
            ->published()
            ->whereKeyNot($service->getKey())
            ->orderBy('price')
            ->limit(3)
            ->get();

        return view('services.show', [
            'service' => $service,
            'relatedServices' => $relatedServices,
        ]);
    }
}
