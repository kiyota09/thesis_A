<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    /**
     * The root template that is loaded on the first page visit.
     */
    protected $rootView = 'app';

    /**
     * Determine the current asset version.
     */
    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    /**
     * Define the props that are shared by default.
     */
    public function share(Request $request): array
    {
        $user = $request->user();

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $user ? array_merge($user->toArray(), [
                    // Provide empty fallbacks so the Sidebar.vue doesn't crash
                    'role' => $user->role ?? 'STAFF',
                    'page_permissions' => [],
                    'workforce_permissions' => [],
                    'granted_modules' => [],
                ]) : null,
                'client' => $this->getGuardUser('client'),
                'supplier' => $this->getGuardUser('supplier'),
            ],
            'ziggy' => fn () => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
            'flash' => [
                'message' => $request->session()->get('message'),
                'error' => $request->session()->get('error'),
            ],
        ];
    }

    /**
     * Safely retrieve the user for specific guards.
     */
    protected function getGuardUser(string $guard)
    {
        try {
            return Auth::guard($guard)->check() ? Auth::guard($guard)->user() : null;
        } catch (\Exception $e) {
            return null;
        }
    }
}