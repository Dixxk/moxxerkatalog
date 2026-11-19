<?php

namespace App\Http\Controllers\Owner;

use App\Http\Controllers\Controller;
use App\Models\Owner;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;

class OwnerAuthController extends Controller
{
    public function setup(): Response|RedirectResponse
    {
        if (Owner::exists()) {
            return redirect()->route('owner.login');
        }

        return Inertia::render('Owner/Auth/Setup');
    }

    public function store(Request $request): RedirectResponse
    {
        if (Owner::exists()) {
            return redirect()->route('owner.login');
        }

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:owners,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $owner = Owner::create($validated);

        Auth::guard('owner')->login($owner);

        $request->session()->regenerate();

        return redirect()->route('owner.dashboard');
    }

    public function login(): Response|RedirectResponse
    {
        if (!Owner::exists()) {
            return redirect()->route('owner.setup');
        }

        if (Auth::guard('owner')->check()) {
            return redirect()->route('owner.dashboard');
        }

        return Inertia::render('Owner/Auth/Login');
    }

    public function authenticate(Request $request): RedirectResponse
    {
        if (!Owner::exists()) {
            return redirect()->route('owner.setup');
        }

        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (!Auth::guard('owner')->attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => 'Email atau password yang kamu masukkan salah.',
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(route('owner.dashboard'));
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::guard('owner')->logout();

        $request->session()->regenerate();
        $request->session()->regenerateToken();

        return redirect()->route('owner.login');
    }
}