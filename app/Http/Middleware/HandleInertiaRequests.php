<?php

namespace App\Http\Middleware;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Middleware;
use Tighten\Ziggy\Ziggy;

class HandleInertiaRequests extends Middleware
{
    protected $rootView = 'app';

    public function version(Request $request): ?string
    {
        return parent::version($request);
    }

    public function share(Request $request): array
    {
        $user = $request->user();
        $userData = null;

        if ($user) {
            // Safe check for Page Permissions
            $pagePermissions = class_exists(\App\Models\PagePermission::class) 
                ? \App\Models\PagePermission::where('user_id', $user->id)->get()->toArray() 
                : [];

            // Safe check for Workforce (Key for Sidebar)
            $workforcePermissions = class_exists(\App\Models\WorkforcePermission::class)
                ? \App\Models\WorkforcePermission::where('user_id', $user->id)->pluck('permission_key')->toArray()
                : [];

            // Safe check for Granted Modules (Secretary/GM)
            $grantedModules = class_exists(\App\Models\UserModuleAccess::class)
                ? \App\Models\UserModuleAccess::where('user_id', $user->id)->pluck('module')->toArray()
                : [];

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
            // FIXED: Using a safer Ziggy call
            'ziggy' => fn () => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
            'flash' => [
                'message' => $request->session()->get('message'),
                'error'   => $request->session()->get('error'),
            ],
        ];
    }

    protected function getGuardUser(string $guard)
    {
        try {
            return Auth::guard($guard)->check() ? Auth::guard($guard)->user() : null;
        } catch (\Exception $e) {
            return null;
        }
    }
}