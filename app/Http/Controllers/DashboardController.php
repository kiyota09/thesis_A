<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use App\Models\logistics\Driver;
use Inertia\Inertia;

class DashboardController extends Controller
{
    public function index(Request $request)
    {
        $user = Auth::user();
        $role = strtoupper($user->role);
        $position = strtolower($user->position);

        // Redirect drivers and conductors to their dedicated portals
        if ($role === 'LOG' && $position === 'staff') {
            // Check directly in the drivers/conductors tables
            $isDriver = DB::table('drivers')->where('user_id', $user->id)->exists();
            $isConductor = DB::table('conductors')->where('user_id', $user->id)->exists();

            // Fallback to log_role column if it exists
            if (!$isDriver && !$isConductor && property_exists($user, 'log_role')) {
                if ($user->log_role === 'driver') $isDriver = true;
                if ($user->log_role === 'conductor') $isConductor = true;
            }

            // Auto-create driver record if user has log_role = 'driver' but no driver record
            if (!$isDriver && !$isConductor && property_exists($user, 'log_role') && $user->log_role === 'driver') {
                Driver::create([
                    'user_id' => $user->id,
                    'license_number' => 'DRV-' . strtoupper(uniqid()),
                    'is_available' => true,
                ]);
                $isDriver = true;
            }

            if ($isDriver) {
                return redirect()->route('logistics.driver.portal');
            }
            if ($isConductor) {
                return redirect()->route('logistics.conductor.portal');
            }
        }

        // Trainees
        if ($position === 'trainee') {
            return Inertia::render('Dashboard/TRAINEE/index', [
                'user' => $user,
                'stats' => [
                    'progress' => 45,
                    'assigned_modules' => 5,
                    'days_remaining' => 12,
                ],
            ]);
        }

        // CEO
        if ($role === 'CEO') {
            return redirect()->route('ceo.dashboard');
        }

        // Map role to route name
        $routeMap = [
            'HRM' => 'hrm.dashboard',
            'CRM' => 'crm.dashboard',
            'SCM'  => 'scm.' . ($position === 'manager' ? 'manager.dashboard' : 'employee.dashboard'),
            'FIN'  => 'fin.' . ($position === 'manager' ? 'manager.dashboard' : 'employee.dashboard'),
            'MAN'  => 'man.' . ($position === 'manager' ? 'manager.dashboard' : 'employee.dashboard'),
            'INV'  => 'inv.' . ($position === 'manager' ? 'manager.dashboard' : 'employee.dashboard'),
            'ORD'  => 'ord.' . ($position === 'manager' ? 'manager.dashboard' : 'employee.dashboard'),
            'WAR'  => 'war.' . ($position === 'manager' ? 'manager.dashboard' : 'employee.dashboard'),
            'ECO'  => 'eco.' . ($position === 'manager' ? 'manager.dashboard' : 'employee.dashboard'),
            'PRO'  => 'pro.' . ($position === 'manager' ? 'manager.dashboard' : 'employee.dashboard'),
            'PROJ' => 'proj.' . ($position === 'manager' ? 'manager.dashboard' : 'employee.dashboard'),
            'IT'   => 'it.' . ($position === 'manager' ? 'manager.dashboard' : 'employee.dashboard'),
        ];

        if (isset($routeMap[$role])) {
            return redirect()->route($routeMap[$role]);
        }

        // Fallback
        return Inertia::render('Dashboard', [
            'stats' => [
                'total_tasks' => 0,
                'pending_tasks' => 0,
                'completed_tasks' => 0,
            ],
            'user' => $user,
        ]);
    }
}