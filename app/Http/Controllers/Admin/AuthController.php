<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SiteSetting;
use App\Support\Admin\AdminModules;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View
    {
        return view('admin.auth.login', $this->guestBranding());
    }

    /**
     * @return array{siteDisplayName: string, siteLogoUrl: ?string}
     */
    private function guestBranding(): array
    {
        $fallbackName = config('app.name', 'Event Manager');

        try {
            if (Schema::hasTable('site_settings')) {
                $s = SiteSetting::query()->first();
                if ($s !== null) {
                    return [
                        'siteDisplayName' => $s->site_name !== null && trim((string) $s->site_name) !== ''
                            ? (string) $s->site_name
                            : $fallbackName,
                        'siteLogoUrl' => $s->adminBrandLogoUrl(),
                    ];
                }
            }
        } catch (\Throwable) {
            //
        }

        return [
            'siteDisplayName' => $fallbackName,
            'siteLogoUrl' => null,
        ];
    }
    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
        ]);

        if (! Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => __('These credentials do not match our records.'),
            ]);
        }

        $user = Auth::user();
        if (! $user->canAccessAdminPanel()) {
            Auth::logout();
            throw ValidationException::withMessages([
                'email' => __('These credentials do not match our records.'),
            ]);
        }

        $request->session()->regenerate();

        return redirect()->intended(
            route(AdminModules::defaultLandingRouteName($user)),
        );
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('admin.login');
    }
}
