<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class ProfileController extends Controller
{
    /**
     * Show the form for editing the customer's profile.
     */
    public function edit(): View
    {
        return view('customer.profile.edit', [
            'user' => auth()->user(),
        ]);
    }

    /**
     * Update the customer's profile.
     */
    public function update(ProfileUpdateRequest $request): RedirectResponse
    {
        $user = auth()->user();
        $validated = $request->validated();

        $emailChanged = strcasecmp((string) $validated['email'], (string) $user->email) !== 0;
        $passwordChanged = isset($validated['password']);

        if (($emailChanged || $passwordChanged)
            && ! Hash::check((string) ($validated['old_password'] ?? ''), $user->password)) {
            throw ValidationException::withMessages([
                'old_password' => 'Password lama salah.',
            ]);
        }

        $user->name = $validated['name'];
        $user->email = $validated['email'];

        if ($passwordChanged) {
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return redirect()
            ->route('customer.profile.edit')
            ->with('success', 'Profil berhasil diperbarui.');
    }
}
