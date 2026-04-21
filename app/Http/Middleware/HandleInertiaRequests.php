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
     *
     * @var string
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
     *
     * @return array<string, mixed>
     */
    public function share(Request $request): array
    {
        $user = $request->user();
        $userData = null;

        if ($user) {
            // Safe check for Page Permissions (HRM, CRM, etc.)
            $pagePermissions = class_exists(\App\Models\PagePermission::class) 
                ? \App\Models\PagePermission::where('user_id', $user->id)->get()->toArray() 
                : [];

            // FIXED: Changed 'permission_key' to 'page' to match your database column
            $workforcePermissions = class_exists(\App\Models\WorkforcePermission::class)
                ? \App\Models\WorkforcePermission::where('user_id', $user->id)->pluck('page')->toArray()
                : [];

            // Safe check for Granted Modules (Secretary/General Manager)
            $grantedModules = class_exists(\App\Models\UserModuleAccess::class)
                ? \App\Models\UserModuleAccess::where('user_id', $user->id)->pluck('module')->toArray()
                : [];

            // Merge everything into a single user object for the Sidebar.vue
            $userData = array_merge($user->toArray(), [
                'granted_modules'       => $grantedModules,
                'page_permissions'      => $pagePermissions,
                'workforce_permissions' => $workforcePermissions,
                'role'                  => $user->role ?? 'STAFF',
            ]);
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user'     => $userData,
                'client'   => $this->getGuardUser('client'),
                'supplier' => $this->getGuardUser('supplier'),
            ],
            // Ziggy setup for route() helper in Vue
            'ziggy' => fn () => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
            // Flash notifications
            'flash' => [
                'message' => $request->session()->get('message'),
                'error'   => $request->session()->get('error'),
            ],
        ];
    }

    /**
     * Safely retrieve the user for B2B guards (client/supplier)
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