<?php

namespace App\Http\Controllers\crm;

use App\Http\Controllers\Controller;
use App\Models\Applicant;
use App\Models\Interview;
use App\Models\User;
use App\Models\PagePermission;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;
use App\Traits\HasPagePermissions;

class InterviewController extends Controller
{
    use HasPagePermissions;

    public function index()
    {
        // Get applicants assigned to CRM for interview
        $applicants = Applicant::with('interview')
            ->where('status', 'Interview')
            ->where('assigned_module', 'CRM')
            ->where('archived', false)
            ->get()
            ->map(fn ($a) => [
                'id' => $a->id,
                'name' => $a->first_name.' '.$a->last_name,
                'email' => $a->email,
                'phone' => $a->phone_number,
                'position_applied' => $a->position_applied,
                'scheduled_at' => $a->interview?->scheduled_at,
                'interview_type' => $a->interview?->interview_type,
                'notes' => $a->interview?->notes,
            ]);

        $user = auth()->user();
        
        // FORCE EDIT PERMISSION FOR MANAGER AND CEO
        if ($user && ($user->role === 'CEO' || $user->role === 'manager')) {
            $permissions = [
                'dashboard' => 'edit',
                'leads' => 'edit',
                'interviews' => 'edit',
                'trainees' => 'edit',
                'approvals' => 'edit',
                'customer_profiles' => 'edit',
                'investigation' => 'edit',
                'access_control' => 'edit',
            ];
        } else {
            // Get permissions from trait for other roles
            $permissions = $this->getPagePermissionsForModule('CRM');
        }

        return Inertia::render('Dashboard/CRM/Interview', [
            'applicants' => $applicants,
            'permissions' => $permissions,
        ]);
    }

    // Rest of your methods remain the same...
    public function schedule(Request $request, $id)
    {
        $user = auth()->user();
        $canEdit = ($user && ($user->role === 'CEO' || $user->role === 'manager'));
        
        if (!$canEdit) {
            return back()->with('error', 'You do not have permission to schedule interviews.');
        }
        
        $request->validate([
            'scheduled_at' => 'required|date',
            'interview_type' => 'required|string',
            'duration' => 'nullable|integer',
            'interviewer' => 'nullable|string',
            'location' => 'nullable|string',
            'notes' => 'nullable|string',
        ]);

        $applicant = Applicant::findOrFail($id);
        Interview::updateOrCreate(
            ['applicant_id' => $applicant->id],
            $request->only('scheduled_at', 'interview_type', 'duration', 'interviewer', 'location', 'notes')
        );

        return back()->with('message', 'Interview scheduled successfully.');
    }

    public function pass(Request $request, $id)
    {
        $user = auth()->user();
        $canEdit = ($user && ($user->role === 'CEO' || $user->role === 'manager'));
        
        if (!$canEdit) {
            return back()->with('error', 'You do not have permission to mark applicants as passed.');
        }
        
        $applicant = Applicant::findOrFail($id);
        
        $existingUser = User::where('email', $applicant->email)->first();
        if ($existingUser) {
            return back()->with('error', 'A user with this email already exists in the system.');
        }
        
        $user = User::create([
            'name' => $applicant->first_name.' '.$applicant->last_name,
            'email' => $applicant->email,
            'password' => Hash::make('password'),
            'role' => 'CRM',
            'position' => 'trainee',
            'is_active' => true,
            'join_date' => now(),
            'employee_id' => $this->generateEmployeeId(),
        ]);

        $applicant->update(['status' => 'Passed', 'archived' => true]);

        return back()->with('message', 'Applicant passed interview and became trainee. Password: password');
    }

    public function fail(Request $request, $id)
    {
        $user = auth()->user();
        $canEdit = ($user && ($user->role === 'CEO' || $user->role === 'manager'));
        
        if (!$canEdit) {
            return back()->with('error', 'You do not have permission to mark applicants as failed.');
        }
        
        $request->validate(['reason' => 'required|string|min:5']);
        $applicant = Applicant::findOrFail($id);
        $applicant->update([
            'status' => 'Failed Interview', 
            'archived' => true, 
            'rejection_reason' => $request->reason
        ]);
        
        return back()->with('message', 'Applicant failed interview and has been archived.');
    }

    public function passToOtherModule(Request $request, $id)
    {
        $user = auth()->user();
        $canEdit = ($user && ($user->role === 'CEO' || $user->role === 'manager'));
        
        if (!$canEdit) {
            return back()->with('error', 'You do not have permission to pass applicants to other modules.');
        }
        
        $request->validate(['module' => 'required|in:HRM,ECO,SCM,MAN,PROJ,FIN,LOG,IT']);
        $applicant = Applicant::findOrFail($id);
        $applicant->update([
            'assigned_module' => $request->module, 
            'status' => 'Interview',
            'archived' => false,
        ]);
        
        if ($applicant->interview) {
            $applicant->interview->delete();
        }
        
        return back()->with('message', "Applicant has been passed to {$request->module} department for interview.");
    }

    private function generateEmployeeId()
    {
        $year = now()->year;
        $last = User::where('employee_id', 'like', "MONTI-{$year}-CRM-%")
            ->orderBy('employee_id', 'desc')
            ->first();
        $num = $last ? (int) substr($last->employee_id, -4) + 1 : 1;
        return sprintf('MONTI-%s-CRM-%04d', $year, $num);
    }
}