<?php

namespace App\Http\Middleware;

use App\Models\PagePermission;
use App\Models\WorkforcePermission;
use App\Models\CrmPagePermission;
use App\Models\UserModuleAccess;
use App\Models\CrmClientAssignment; // added for assigned client IDs
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
        // Get the authenticated user for the default guard (web)
        $user = $request->user();

        // If a user is logged in, fetch their page permissions grouped by module
        $userData = null;
        $pagePermissionsList = []; // raw list for sidebar
        $assignedClientIds = [];   // for CRM staff
        if ($user) {
            // Fetch raw page permissions (used by sidebar and middleware)
            $pagePermissionsList = PagePermission::where('user_id', $user->id)
                ->get(['module', 'page', 'permission_level'])
                ->toArray();

            // Group by module for legacy usage
            $permissionsGrouped = PagePermission::where('user_id', $user->id)
                ->get()
                ->groupBy('module')
                ->map(fn ($perms) => $perms->pluck('page'));

            // Fetch workforce permissions (for Workforce Management module)
            $workforcePermissions = WorkforcePermission::where('user_id', $user->id)->get();

            // Fetch CRM page permissions (for CRM module – available to CRM and CEO)
            $crmPagePermissions = [];
            if (in_array($user->role, ['CRM', 'CEO'])) {
                $crmPagePermissions = CrmPagePermission::where('user_id', $user->id)->pluck('page')->toArray();
            }

            // Fetch granted modules for secretary / general manager (from user_module_access)
            $grantedModules = UserModuleAccess::where('user_id', $user->id)->pluck('module')->toArray();

            // Fetch assigned client IDs for CRM staff
            if ($user->role === 'CRM' && $user->position === 'staff') {
                $assignedClientIds = CrmClientAssignment::where('staff_id', $user->id)
                    ->pluck('client_id')
                    ->toArray();
            }

            // Merge the original user attributes with the permissions arrays
            $userData = array_merge($user->toArray(), [
                'permissions'            => $permissionsGrouped,
                'workforce_permissions'  => $workforcePermissions,
                'crmPagePermissions'     => $crmPagePermissions,
                'granted_modules'        => $grantedModules,
                'page_permissions'       => $pagePermissionsList, // critical for sidebar
            ]);
        }

        return [
            ...parent::share($request),
            'auth' => [
                // Enhanced user object containing permissions
                'user' => $userData,

                // Top-level page permissions (fallback for sidebar)
                'page_permissions' => $pagePermissionsList,

                // Assigned client IDs for CRM staff
                'assigned_client_ids' => $assignedClientIds,

                // B2B Clients - Safely retrieved if guard exists
                'client' => $this->getGuardUser('client'),

                // Supplier Guard - Safely retrieved if guard exists
                'supplier' => $this->getGuardUser('supplier'),
            ],
            'ziggy' => fn () => [
                ...(new Ziggy)->toArray(),
                'location' => $request->url(),
            ],
            // Flash messages for login/registration alerts
            'flash' => [
                'message' => fn () => $request->session()->get('message'),
            ],
        ];
    }

    /**
     * Safely get the user for a specific guard to prevent
     * InvalidArgumentException if the guard is not yet defined.
     */
    protected function getGuardUser(string $guard)
    {
        try {
            return Auth::guard($guard)->user();
        } catch (\InvalidArgumentException $e) {
            return null;
        }
    }
}