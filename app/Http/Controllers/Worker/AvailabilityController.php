<?php

namespace App\Http\Controllers\Worker;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AvailabilityController extends Controller
{
    /**
     * Toggle whether the worker is available to claim new orders.
     */
    public function __invoke(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'is_online' => ['required', 'boolean'],
        ]);

        $request->user()->forceFill([
            'is_online' => (bool) $validated['is_online'],
        ])->save();

        return back()->with(
            'success',
            $validated['is_online']
                ? 'Status online aktif. Pesanan baru bisa kamu ambil.'
                : 'Status offline aktif. Kamu tidak akan menerima pesanan baru.',
        );
    }
}
