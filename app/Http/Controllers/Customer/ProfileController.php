<?php

namespace App\Http\Controllers\Customer;

use App\Http\Controllers\Controller;
use App\Http\Requests\Customer\ProfileUpdateRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;
use Illuminate\Support\Facades\Hash;

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

        // Update name and email
        $user->name = $validated['name'];
        $user->email = $validated['email'];

        // Update password if provided
        if (isset($validated['password'])) {
            if (!Hash::check($validated['old_password'], $user->password)) {
                return back()->with('error', 'Password lama salah.');
            }
            $user->password = Hash::make($validated['password']);
        }

        $user->save();

        return redirect()
            ->route('customer.profile.edit')
            ->with('success', 'Profil berhasil diperbarui.');
    }
}