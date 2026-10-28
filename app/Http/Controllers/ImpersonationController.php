<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\UserImpersonation;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

class ImpersonationController extends Controller
{
    /**
     * Start impersonating an admin, worker, or customer account.
     */
    public function store(Request $request, User $user): RedirectResponse
    {
        $superAdmin = $request->user();

        abort_unless($superAdmin?->isSuperAdmin(), 403);

        if ($request->session()->has('impersonator_id')) {
            return back()->with('error', 'Selesaikan sesi login-sebagai yang sedang berjalan terlebih dahulu.');
        }

        if ($user->is($superAdmin) || $user->isSuperAdmin()) {
            return back()->with('error', 'Akun Super Admin tidak dapat dimasuki dari fitur ini.');
        }

        $audit = UserImpersonation::query()->create([
            'impersonator_id' => $superAdmin->getKey(),
            'impersonated_id' => $user->getKey(),
            'impersonator_name' => $superAdmin->name,
            'impersonator_email' => $superAdmin->email,
            'impersonated_name' => $user->name,
            'impersonated_email' => $user->email,
            'ip_address' => $request->ip(),
            'user_agent' => Str::limit((string) $request->userAgent(), 1000, ''),
            'started_at' => now(),
        ]);

        Auth::login($user);

        $request->session()->put([
            'impersonator_id' => $superAdmin->getKey(),
            'impersonation_id' => $audit->getKey(),
        ]);

        $request->session()->regenerate();

        return redirect()
            ->route($user->role->homeRoute())
            ->with('success', "Anda sekarang masuk sebagai {$user->name}.");
    }

    /**
     * Return to the original super admin account.
     */
    public function destroy(Request $request): RedirectResponse
    {
        $impersonatorId = $request->session()->get('impersonator_id');

        abort_unless($impersonatorId !== null, 403);

        $superAdmin = User::query()->find($impersonatorId);

        if ($superAdmin === null || ! $superAdmin->isSuperAdmin()) {
            Auth::guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()
                ->route('login')
                ->with('error', 'Akun Super Admin asal tidak lagi valid. Silakan masuk kembali.');
        }

        $impersonationId = $request->session()->get('impersonation_id');

        if ($impersonationId !== null) {
            UserImpersonation::query()
                ->whereKey($impersonationId)
                ->whereNull('ended_at')
                ->update(['ended_at' => now()]);
        }

        Auth::login($superAdmin);

        $request->session()->forget(['impersonator_id', 'impersonation_id']);
        $request->session()->regenerate();

        return redirect()
            ->route('admin.dashboard')
            ->with('success', 'Kembali ke akun Super Admin.');
    }
}
