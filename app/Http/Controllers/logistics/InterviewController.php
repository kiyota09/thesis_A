<?php

namespace App\Http\Controllers\logistics;

use App\Http\Controllers\Controller;
use App\Models\Applicant;
use App\Models\Interview;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Inertia\Inertia;

class InterviewController extends Controller
{
    public function index()
    {
        $applicants = Applicant::with('interview')
            ->where('status', 'Interview')
            ->where('assigned_module', 'LOG')
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
                'profile_photo' => $a->image ? asset('storage/'.$a->image) : null,
                'first_name' => $a->first_name,
                'middle_name' => $a->middle_name,
                'last_name' => $a->last_name,
                'date_of_birth' => $a->date_of_birth,
                'place_of_birth' => $a->place_of_birth,
                'sex' => $a->sex,
                'civil_status' => $a->civil_status,
                'age' => $a->age,
                'religion' => $a->religion,
                'citizenship' => $a->citizenship,
                'height' => $a->height,
                'weight' => $a->weight,
                'languages' => $a->languages,
                'special_skills' => $a->special_skills,
                'street_address' => $a->street_address,
                'city' => $a->city,
                'state_province' => $a->state_province,
                'postal_zip_code' => $a->postal_zip_code,
                'phone_number' => $a->phone_number,
                'emergency_name' => $a->emergency_name,
                'emergency_relationship' => $a->emergency_relationship,
                'emergency_phone' => $a->emergency_phone,
                'emergency_address' => $a->emergency_address,
                'sss_number' => $a->sss_number,
                'sss_file_url' => $a->sss_file ? asset('storage/'.$a->sss_file) : null,
                'philhealth_number' => $a->philhealth_number,
                'philhealth_file_url' => $a->philhealth_file ? asset('storage/'.$a->philhealth_file) : null,
                'pagibig_number' => $a->pagibig_number,
                'pagibig_file_url' => $a->pagibig_file ? asset('storage/'.$a->pagibig_file) : null,
                'spouse_name' => $a->spouse_name,
                'spouse_occupation' => $a->spouse_occupation,
                'spouse_address' => $a->spouse_address,
                'number_of_children' => $a->number_of_children,
                'children' => $a->children ? json_decode($a->children, true) : null,
                'mother_name' => $a->mother_name,
                'mother_address' => $a->mother_address,
                'father_name' => $a->father_name,
                'father_address' => $a->father_address,
                'elementary_school' => $a->elementary_school,
                'elementary_year' => $a->elementary_year,
                'high_school' => $a->high_school,
                'high_year' => $a->high_year,
                'college' => $a->college,
                'college_year' => $a->college_year,
                'vocational' => $a->vocational,
                'vocational_year' => $a->vocational_year,
                'employment_records' => $a->employment_records ? json_decode($a->employment_records, true) : null,
                'previous_employment_company' => $a->previous_employment_company,
                'previous_employment_when' => $a->previous_employment_when,
                'previous_employment_position' => $a->previous_employment_position,
                'previous_employment_department' => $a->previous_employment_department,
                'machine_operation' => $a->machine_operation,
                'referred_by' => $a->referred_by,
                'referred_by_address' => $a->referred_by_address,
                'related_employees' => $a->related_employees ? json_decode($a->related_employees, true) : null,
            ]);

        // Any user who can access the Logistics module has full permission to manage interviews
        $permissions = ['interview' => 'edit'];

        return Inertia::render('Dashboard/Logistics/Interview', [
            'applicants' => $applicants,
            'permissions' => $permissions,
        ]);
    }

    public function schedule(Request $request, $id)
    {
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

        return back()->with('message', 'Interview scheduled.');
    }

    public function pass(Request $request, $id)
    {
        $applicant = Applicant::findOrFail($id);
        $user = User::create([
            'name' => $applicant->first_name.' '.$applicant->last_name,
            'email' => $applicant->email,
            'password' => Hash::make('password'),
            'role' => 'LOG',
            'position' => 'trainee',
            'is_active' => true,
            'join_date' => now(),
            'employee_id' => $this->generateEmployeeId(),
        ]);

        $applicant->update(['status' => 'Passed', 'archived' => false]);

        return back()->with('message', 'Applicant passed interview and became trainee.');
    }

    public function fail(Request $request, $id)
    {
        $request->validate(['reason' => 'required|string']);
        $applicant = Applicant::findOrFail($id);
        $applicant->update(['status' => 'Failed Interview', 'archived' => true, 'rejection_reason' => $request->reason]);
        return back()->with('message', 'Applicant failed interview.');
    }

    public function passToOtherModule(Request $request, $id)
    {
        $request->validate(['module' => 'required|in:HRM,CRM,ECO,SCM,MAN,PROJ,FIN,IT']);
        $applicant = Applicant::findOrFail($id);
        $applicant->update(['assigned_module' => $request->module, 'status' => 'Interview']);
        return back()->with('message', "Applicant passed to {$request->module}.");
    }

    private function generateEmployeeId()
    {
        $year = now()->year;
        $last = User::where('employee_id', 'like', "MONTI-{$year}-LOG-%")->orderBy('employee_id', 'desc')->first();
        $num = $last ? (int) substr($last->employee_id, -4) + 1 : 1;
        return sprintf('MONTI-%s-LOG-%04d', $year, $num);
    }
}