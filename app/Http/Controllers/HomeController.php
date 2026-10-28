<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Contracts\View\View;

class HomeController extends Controller
{
    /**
     * Show the public landing page.
     */
    public function __invoke(): View
    {
        return view('home', [
            'featuredServices' => Service::query()
                ->published()
                ->orderBy('price')
                ->limit(config('jovexa.featured_services_limit'))
                ->get(),
            'serviceCount' => Service::query()->published()->count(),
        ]);
    }
}
