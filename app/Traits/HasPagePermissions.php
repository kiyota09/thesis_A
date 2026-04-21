<?php

namespace App\Traits;

use App\Models\PagePermission;

trait HasPagePermissions
{
    /**
     * Get page permissions for the current user for a specific module.
     * Returns an array of [page => permission_level] (view/edit).
     */
    protected function getPagePermissionsForModule(string $module): array
    {
        $user = auth()->user();

        if (!$user) {
            return [];
        }
        
        // CEO bypass: full edit on all pages
        if ($user->role === 'CEO') {
            $pages = array_keys(config('module_pages.' . strtolower($module), []));
            return array_fill_keys($pages, 'edit');
        }
        
        // For managers and other roles, get from database
        $permissions = PagePermission::where('user_id', $user->id)
            ->where('module', $module)
            ->get()
            ->pluck('permission_level', 'page')
            ->toArray();
        
        // Debug: Log what we found
        \Log::info("Page permissions for user {$user->id} ({$user->role}) module {$module}:", $permissions);
        
        // For CRM module, ensure interviews permission exists
        if ($module === 'CRM' && !isset($permissions['interviews'])) {
            // Check if there's an 'interview' (singular) permission to map
            if (isset($permissions['interview'])) {
                $permissions['interviews'] = $permissions['interview'];
            } else {
                $permissions['interviews'] = 'view';
            }
        }

        return $permissions;
    }
}