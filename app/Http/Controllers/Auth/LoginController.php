<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class LoginController extends Controller
{
    public function show(): View
    {
        return view('auth.login');
    }

    public function store(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => __('auth.failed'),
            ]);
        }

        $request->session()->regenerate();

        $user = $request->user();
        $intended = $request->session()->pull('url.intended');
        $intended = is_string($intended) ? $intended : null;

        // Super admin: only honour admin-area intended URLs.
        if ($user->isSuperAdmin()) {
            if ($intended !== null && str_contains($intended, '/admin')) {
                return redirect()->to($intended);
            }

            return redirect()->route('admin.dashboard');
        }

        // Client without active subscription: pay first.
        if (! $user->hasActiveSubscription()) {
            return redirect()->route('crm.payment-required');
        }

        // Client: only honour CRM-area intended URLs — never admin URLs
        // (a stale /admin intended URL used to send clients to a 403 page).
        if ($intended !== null && str_contains($intended, '/crm')) {
            return redirect()->to($intended);
        }

        return redirect()->route('crm.dashboard');
    }

    public function destroy(Request $request): RedirectResponse
    {
        Auth::guard('web')->logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login');
    }
}
