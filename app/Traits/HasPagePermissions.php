<?php

namespace App\Traits;

use App\Models\PagePermission;

trait HasPagePermissions
{
    /**
     * Get page permissions for the current user for a specific module.
     * Returns an array of [page => permission_level] (view/edit).
     * CEO gets full edit on all pages.
     */
    protected function getPagePermissionsForModule(string $module): array
    {
        $user = auth()->user();

        // CEO bypass: full edit on all pages
        if ($user && $user->role === 'CEO') {
            $pages = array_keys(config('module_pages.' . strtolower($module), []));
            return array_fill_keys($pages, 'edit');
        }

        if (!$user) {
            return [];
        }

        $permissions = PagePermission::where('user_id', $user->id)
            ->where('module', $module)
            ->get()
            ->pluck('permission_level', 'page')
            ->toArray();

        return $permissions;
    }
}