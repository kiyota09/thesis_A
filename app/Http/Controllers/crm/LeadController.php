<?php

namespace App\Http\Controllers\crm;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\CreditAccount;
use App\Models\CrmApproval;
use App\Models\CrmLead;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;
use App\Traits\HasPagePermissions;

class LeadController extends Controller
{
    use HasPagePermissions;

    public function index()
    {
        $leads = CrmLead::with([
            'notes.user',
            'interviews.user',
            'approvalFiles.user',
            'assignedStaff'
        ])
            ->whereNotIn('status', ['Converted', 'Archived', 'Lost'])
            ->orderBy('created_at', 'asc')
            ->get();

        $permissions = $this->getPagePermissionsForModule('CRM');

        return Inertia::render('Dashboard/CRM/Lead', [
            'leads' => $leads,
            'permissions' => $permissions,
        ]);
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'company_name' => 'required|string|max:255|min:2',
            'contact_person' => 'required|string|max:255',
            'email' => 'required|email|max:255|unique:crm_leads,email',
            'phone' => 'required|string|max:20|regex:/^[0-9+\-\s()]{10,20}$/',
        ], [
            'email.unique' => 'This email is already registered as a lead.',
            'phone.regex' => 'Please enter a valid phone number (10-20 digits).',
            'company_name.min' => 'Company name must be at least 2 characters.',
        ]);

        try {
            CrmLead::create([
                'company_name' => $validated['company_name'],
                'contact_person' => $validated['contact_person'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'status' => 'Inquiry',
                'assigned_staff_id' => Auth::id(),
                'interest_fabric' => null, // Set to null since column is now nullable
                'estimated_value' => 0,    // Set default value
            ]);

            return back()->with('message', 'New lead created successfully.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to create lead: ' . $e->getMessage()]);
        }
    }

    public function updateStatus(Request $request, $id)
    {
        $request->validate([
            'status' => 'required|string|in:Inquiry,Negotiation,Approval Sent,Closed-Won',
        ]);

        $lead = CrmLead::findOrFail($id);
        $lead->update(['status' => $request->status]);

        return back()->with('message', 'Lead status updated.');
    }

    public function destroy($id)
    {
        try {
            $lead = CrmLead::findOrFail($id);
            
            // Delete associated files
            foreach ($lead->approvalFiles as $file) {
                if ($file->file_path && Storage::disk('public')->exists($file->file_path)) {
                    Storage::disk('public')->delete($file->file_path);
                }
            }
            
            $lead->delete();

            return back()->with('message', 'Lead deleted successfully.');
        } catch (\Exception $e) {
            return back()->withErrors(['error' => 'Failed to delete lead: ' . $e->getMessage()]);
        }
    }

    public function convertToClient(Request $request)
    {
        $validated = $request->validate([
            'lead_id' => 'required|exists:crm_leads,id',
            'company_name' => 'required|string|max:255',
            'business_type' => 'required|string|in:wholesaler,retailer,manufacturer',
            'tin_number' => 'required|string|unique:clients,tin_number',
            'contact_person' => 'required|string|max:255',
            'email' => 'required|email|unique:clients,email',
            'phone' => 'required|string|max:20',
            'company_address' => 'required|string',
            'password' => 'required|string|min:8',
        ]);

        try {
            DB::beginTransaction();

            $client = Client::create([
                'company_name' => $validated['company_name'],
                'business_type' => $validated['business_type'],
                'tin_number' => $validated['tin_number'],
                'contact_person' => $validated['contact_person'],
                'email' => $validated['email'],
                'phone' => $validated['phone'],
                'company_address' => $validated['company_address'],
                'password' => bcrypt($validated['password']),
                'status' => 'active',
            ]);

            CreditAccount::create([
                'client_id' => $client->id,
                'outstanding_balance' => 0.00,
                'is_good_payer' => 1,
            ]);

            $lead = CrmLead::find($validated['lead_id']);
            $lead->update(['status' => 'Converted']);

            DB::commit();
            return back()->with('message', 'Lead converted to client successfully.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Conversion failed: ' . $e->getMessage()]);
        }
    }

    public function addNote(Request $request, $id)
    {
        $user = Auth::user();
        $validated = $request->validate([
            'note' => 'required|string|max:2000',
        ]);

        $lead = CrmLead::findOrFail($id);

        if ($user->position === 'manager' || $user->role === 'CEO') {
            $lead->notes()->create([
                'user_id' => $user->id,
                'note' => $validated['note'],
            ]);
            return back()->with('message', 'Note added successfully.');
        }

        CrmApproval::create([
            'user_id' => $user->id,
            'action' => 'add_note',
            'data' => [
                'lead_id' => $lead->id,
                'note' => $validated['note'],
            ],
            'status' => 'pending',
        ]);

        return back()->with('message', 'Note submitted for approval.');
    }

    public function scheduleInterview(Request $request, $id)
    {
        $user = Auth::user();
        $validated = $request->validate([
            'scheduled_at' => 'required|date',
            'location' => 'nullable|string|max:255',
            'notes' => 'nullable|string',
        ]);

        $lead = CrmLead::findOrFail($id);

        if ($user->position === 'manager' || $user->role === 'CEO') {
            $lead->interviews()->create([
                'user_id' => $user->id,
                'scheduled_at' => $validated['scheduled_at'],
                'location' => $validated['location'] ?? null,
                'notes' => $validated['notes'] ?? null,
            ]);
            return back()->with('message', 'Interview scheduled successfully.');
        }

        CrmApproval::create([
            'user_id' => $user->id,
            'action' => 'schedule_interview',
            'data' => array_merge(['lead_id' => $lead->id], $validated),
            'status' => 'pending',
        ]);

        return back()->with('message', 'Interview request submitted for approval.');
    }

    public function uploadApprovalFile(Request $request, $id)
    {
        $user = Auth::user();
        $validated = $request->validate([
            'file' => 'required|file|mimes:jpg,jpeg,png,pdf|max:5120',
        ]);

        $lead = CrmLead::findOrFail($id);

        if ($user->position === 'manager' || $user->role === 'CEO') {
            $path = $request->file('file')->store('lead_approval_files', 'public');
            $lead->approvalFiles()->create([
                'file_path' => $path,
                'original_name' => $request->file('file')->getClientOriginalName(),
                'uploaded_by' => $user->id,
            ]);
            return back()->with('message', 'File uploaded successfully.');
        }

        $path = $request->file('file')->store('temp_approval_files', 'public');
        CrmApproval::create([
            'user_id' => $user->id,
            'action' => 'upload_approval_file',
            'data' => [
                'lead_id' => $lead->id,
                'file_path' => $path,
                'original_name' => $request->file('file')->getClientOriginalName(),
            ],
            'status' => 'pending',
        ]);

        return back()->with('message', 'File upload submitted for approval.');
    }

    public function acceptLead($id)
    {
        $user = Auth::user();
        $lead = CrmLead::findOrFail($id);

        if ($user->position === 'manager' || $user->role === 'CEO') {
            $lead->update(['status' => 'Closed-Won']);
            return back()->with('message', 'Lead accepted and moved to Closed-Won.');
        }

        CrmApproval::create([
            'user_id' => $user->id,
            'action' => 'accept_lead',
            'data' => ['lead_id' => $lead->id],
            'status' => 'pending',
        ]);

        return back()->with('message', 'Accept request submitted for approval.');
    }

    public function rejectLead(Request $request, $id)
    {
        $user = Auth::user();
        $validated = $request->validate([
            'reject_reason' => 'required|string|max:500',
        ]);

        $lead = CrmLead::findOrFail($id);

        if ($user->position === 'manager' || $user->role === 'CEO') {
            $lead->update(['status' => 'Lost', 'lost_reason' => $validated['reject_reason']]);
            return back()->with('message', 'Lead rejected and marked as Lost.');
        }

        CrmApproval::create([
            'user_id' => $user->id,
            'action' => 'reject_lead',
            'data' => [
                'lead_id' => $lead->id,
                'lost_reason' => $validated['reject_reason'],
            ],
            'status' => 'pending',
        ]);

        return back()->with('message', 'Rejection request submitted for approval.');
    }
}