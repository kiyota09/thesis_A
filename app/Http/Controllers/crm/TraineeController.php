<?php

namespace App\Http\Controllers\crm;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\TraineeGrade;
use App\Models\PagePermission;
use Illuminate\Http\Request;
use Inertia\Inertia;
use App\Traits\HasPagePermissions;

class TraineeController extends Controller
{
    use HasPagePermissions;

    public function index()
    {
        $trainees = User::where('position', 'trainee')
            ->where('role', 'CRM')
            ->with('traineeGrade')
            ->get()
            ->map(fn ($t) => [
                'id' => $t->id,
                'name' => $t->name,
                'email' => $t->email,
                'join_date' => $t->join_date,
                'grade_percentage' => $t->traineeGrade?->total_percentage ?? 0,
            ]);

        $permissions = $this->getPagePermissionsForModule('CRM');

        return Inertia::render('Dashboard/CRM/Trainee', [
            'trainees' => $trainees,
            'permissions' => $permissions,
        ]);
    }

    public function grade(Request $request, $id)
    {
        $validated = $request->validate([
            'skills_performance' => 'required|integer|min:1|max:5',
            'behaviour' => 'required|integer|min:1|max:5',
            'technicals' => 'required|integer|min:1|max:5',
            'safety_awareness' => 'required|integer|min:1|max:5',
            'productivity' => 'required|integer|min:1|max:5',
        ]);

        $total = array_sum($validated);
        $percentage = ($total / 25) * 100;

        TraineeGrade::updateOrCreate(
            ['user_id' => $id],
            array_merge($validated, ['total_percentage' => $percentage])
        );

        return back()->with('message', 'Grade saved.');
    }

    public function pass($id)
    {
        $trainee = User::findOrFail($id);
        $grade = $trainee->traineeGrade;
        if (!$grade || $grade->total_percentage < 80) {
            return back()->with('error', 'Grade must be at least 80% to pass.');
        }
        $trainee->update(['position' => 'staff']);
        
        // Assign default page permissions for CRM staff
        $this->assignDefaultStaffPagePermissions($trainee);
        
        return back()->with('message', 'Trainee promoted to staff.');
    }

    public function fail(Request $request, $id)
    {
        $request->validate(['reason' => 'required|string']);
        $trainee = User::findOrFail($id);
        $trainee->update(['is_active' => false, 'position' => 'staff']);
        return back()->with('message', 'Trainee failed and archived.');
    }

    /**
     * Assign default page permissions for a staff member based on their module.
     * Grants 'dashboard' with 'view' permission if none exist for that module.
     *
     * @param User $user
     * @return void
     */
    protected function assignDefaultStaffPagePermissions(User $user): void
    {
        // Only assign if user is a staff member
        if ($user->position !== 'staff') {
            return;
        }

        $module = $user->role;
        $coreModules = ['HRM', 'CRM', 'MAN', 'LOG'];
        
        // Only assign for core modules that have defined pages
        if (!in_array($module, $coreModules)) {
            return;
        }

        // Check if the user already has any page permissions for this module
        $existing = PagePermission::where('user_id', $user->id)
            ->where('module', $module)
            ->exists();

        if (!$existing) {
            // Grant default: dashboard with view permission
            $pagePerm = new PagePermission();
            $pagePerm->user_id = $user->id;
            $pagePerm->module = $module;
            $pagePerm->page = 'dashboard';
            $pagePerm->permission_level = 'view';
            $pagePerm->save();
        }
    }
}