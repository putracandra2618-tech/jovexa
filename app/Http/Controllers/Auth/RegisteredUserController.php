<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\Role;
use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class RegisteredUserController extends Controller
{
    /**
     * Show the registration form.
     */
    public function create(): View
    {
        return view('auth.register');
    }

    /**
     * Register a new customer account.
     */
    public function store(RegisterRequest $request): RedirectResponse
    {
        $user = new User;
        $user->forceFill([
            ...$request->validated(),
            'role_id' => Role::ensureIdFor($request->role()->value, $request->role()->label()),
        ])->save();

        event(new Registered($user));

        Auth::login($user);

        $request->session()->regenerate();

        return redirect()->route($user->role->homeRoute());
    }
}
