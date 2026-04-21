<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class CheckModuleAccess
{
    public function handle(Request $request, Closure $next, string $module): Response
    {
        // ─── DEV BYPASS ──────────────────────────────────────────────────────────
        // Remove or comment this out before deploying to production.
        if (app()->environment('local')) {
            return $next($request);
        }
        // ─────────────────────────────────────────────────────────────────────────

        $user = Auth::user();

        if (! $user) {
            abort(403, 'Unauthorized');
        }

        // ── CEO and Admin have access to everything ───────────────────────────
        if (in_array($user->role, ['CEO', 'admin'])) {
            return $next($request);
        }

        // ── Secretary or General Manager: check granted_modules ───────────────
        if (in_array($user->position, ['secretary', 'general_manager'])) {
            $granted = $user->moduleAccess->pluck('module')->toArray();
            if (in_array($module, $granted)) {
                return $next($request);
            }
            abort(403, "You don't have access to the {$module} module.");
        }

        // ── Manufacturing Supervisor ──────────────────────────────────────────
        // Supervisors always have implicit access to the MAN module because
        // their entire role revolves around it. For any OTHER module they
        // must still have an explicit grant.
        if ($user->is_manufacturing_supervisor) {
            if ($module === 'MAN') {
                return $next($request);
            }

            // Non-MAN modules require an explicit grant
            $granted = $user->moduleAccess->pluck('module')->toArray();
            if (in_array($module, $granted)) {
                return $next($request);
            }

            abort(403, "You don't have access to the {$module} module.");
        }

        // ── Regular manager: role must match module name ──────────────────────
        if ($user->role === $module && $user->position === 'manager') {
            return $next($request);
        }

        // ── Staff: role must match module name ────────────────────────────────
        // e.g. a MAN staff member has role = 'MAN' and position = 'staff'
        if ($user->role === $module && $user->position === 'staff') {
            return $next($request);
        }

        abort(403, "You don't have access to the {$module} module.");
    }
}