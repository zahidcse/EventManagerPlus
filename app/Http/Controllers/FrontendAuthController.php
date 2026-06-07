<?php

namespace App\Http\Controllers;

use App\Models\SiteSetting;
use App\Models\User;
use App\Support\PublicFrontendTheme;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;
use Illuminate\Validation\Rules\Password;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class FrontendAuthController extends Controller
{
    /**
     * @return array<string, mixed>
     */
    private function siteContext(): array
    {
        if (!Schema::hasTable('site_settings')) {
            $setting = new SiteSetting([
                'site_name' => config('app.name', 'Event Manager'),
                'frontend_theme' => 'default',
            ]);
        } else {
            $setting = SiteSetting::instance();
        }

        $extras = PublicFrontendTheme::publicPageExtras();

        return [
            'siteSetting' => $setting,
            'siteName' => $setting->site_name ?: config('app.name', 'Event Manager'),
            'siteLogoUrl' => PublicFrontendTheme::resolvePublicLogoUrl($setting),
            'contactEmail' => $extras['contactEmail'],
            'contactPhone' => $extras['contactPhone'],
            'heroImageUrl' => $extras['heroImageUrl'],
        ];
    }

    private function safeRedirectPath(?string $raw): ?string
    {
        if ($raw === null || $raw === '') {
            return null;
        }

        $path = urldecode(trim($raw));
        if ($path === '' || !str_starts_with($path, '/') || str_starts_with($path, '//')) {
            return null;
        }

        if (preg_match('/[^\x20-\x7E]/', $path) !== 0) {
            return null;
        }

        return $path;
    }

    protected function redirectAfterLogin(Request $request, ?string $redirectPath): RedirectResponse
    {
        $target = $this->safeRedirectPath($redirectPath);

        return $target !== null
            ? redirect()->to($target)
            : redirect()->intended(route('home'));
    }

    public function showLogin(Request $request): View
    {
        return view(
            PublicFrontendTheme::isClassicFamily()
            ? 'public.classic.auth.login'
            : 'public.auth.login',
            array_merge($this->siteContext(), [
                'authRedirectPath' => $this->safeRedirectPath($request->query('redirect')),
            ])
        );
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required', 'string'],
            'redirect' => ['nullable', 'string'],
        ]);

        $redirectPath = $this->safeRedirectPath($credentials['redirect'] ?? null);
        unset($credentials['redirect']);

        if (!Auth::attempt($credentials, $request->boolean('remember'))) {
            throw ValidationException::withMessages([
                'email' => __('These credentials do not match our records.'),
            ]);
        }

        /** @var User $user */
        $user = $request->user();
        if ($user->is_admin || $user->is_organizer) {
            Auth::logout();

            throw ValidationException::withMessages([
                'email' => __('Use the admin sign-in page for staff and organizer accounts.'),
            ]);
        }

        $request->session()->regenerate();

        return $this->redirectAfterLogin($request, $redirectPath);
    }

    public function showRegister(Request $request): View
    {
        return view(
            PublicFrontendTheme::isClassicFamily()
            ? 'public.classic.auth.register'
            : 'public.auth.register',
            array_merge($this->siteContext(), [
                'authRedirectPath' => $this->safeRedirectPath($request->query('redirect')),
            ])
        );
    }

    public function register(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::defaults()],
            'redirect' => ['nullable', 'string'],
        ]);

        $redirectPath = $this->safeRedirectPath($data['redirect'] ?? null);
        unset($data['redirect']);

        /** @var User $user */
        $user = User::query()->create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => $data['password'],
        ]);

        Auth::login($user);
        $request->session()->regenerate();

        return $this->redirectAfterLogin($request, $redirectPath);
    }

    public function logout(Request $request): RedirectResponse
    {
        Auth::logout();

        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('home');
    }
}
