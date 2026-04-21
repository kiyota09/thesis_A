<?php

namespace App\Http\Middleware;

use App\Models\PagePermission;
use App\Models\WorkforcePermission;
use App\Models\CrmPagePermission;
use App\Models\UserModuleAccess;
use App\Models\CrmClientAssignment;
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
        $pagePermissionsList = [];
        $assignedClientIds = [];

        if ($user) {
            // 1. Critical for Sidebar: Raw list of page permissions
            $pagePermissionsList = PagePermission::where('user_id', $user->id)
                ->get(['module', 'page', 'permission_level'])
                ->toArray();

            // 2. Grouped permissions for granular checks
            $permissionsGrouped = PagePermission::where('user_id', $user->id)
                ->get()
                ->groupBy('module')
                ->map(fn ($perms) => $perms->pluck('page')->toArray());

            // 3. FIXED: Workforce permissions now returns an array of strings (keys)
            $workforcePermissions = WorkforcePermission::where('user_id', $user->id)
                ->pluck('permission_key') // Ensure your column name is 'permission_key' or 'page'
                ->toArray();

            // 4. CRM Specific Permissions
            $crmPagePermissions = [];
            if (in_array($user->role, ['CRM', 'CEO'])) {
                $crmPagePermissions = CrmPagePermission::where('user_id', $user->id)
                    ->pluck('page')
                    ->toArray();
            }

            // 5. Module Access (Secretary/GM)
            $grantedModules = UserModuleAccess::where('user_id', $user->id)
                ->pluck('module')
                ->toArray();

            // 6. CRM Client Assignments
            if ($user->role === 'CRM' && $user->position === 'staff') {
                $assignedClientIds = CrmClientAssignment::where('staff_id', $user->id)
                    ->pluck('client_id')
                    ->toArray();
            }

            // Merge everything into the user object
            $userData = array_merge($user->toArray(), [
                'permissions'           => $permissionsGrouped,
                'workforce_permissions' => $workforcePermissions,
                'crmPagePermissions'    => $crmPagePermissions,
                'granted_modules'       => $grantedModules,
                'page_permissions'      => $pagePermissionsList,
            ]);
        }

        return [
            ...parent::share($request),
            'auth' => [
                'user' => $userData,
                'page_permissions' => $pagePermissionsList,
                'assigned_client_ids' => $assignedClientIds,
                'client' => $this->getGuardUser('client'),
                'supplier' => $this->getGuardUser('supplier'),
            ],
            'ziggy' => fn () => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
            'flash' => [
                'message' => fn () => $request->session()->get('message'),
                'error' => fn () => $request->session()->get('error'),
            ],
        ];
    }

    protected function getGuardUser(string $guard)
    {
        try {
            return Auth::guard($guard)->check() ? Auth::guard($guard)->user() : null;
        } catch (\InvalidArgumentException $e) {
            return null;
        }
    }
}