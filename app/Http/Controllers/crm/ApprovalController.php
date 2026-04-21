<?php

namespace App\Http\Controllers\crm;

use App\Http\Controllers\Controller;
use App\Models\Client;
use App\Models\CrmMeeting;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use App\Traits\HasPagePermissions;

class ApprovalController extends Controller
{
    use HasPagePermissions;

    public function index()
    {
        $pendingClients = Client::where('status', 'pending')->with('meetings')->get();

        $permissions = $this->getPagePermissionsForModule('CRM');
        
        // For the Approval page, we need to check if the 'approval' page has edit permission
        // or if user is CEO (already handled in trait)
        $approvalPermission = $permissions['approval'] ?? 'view';
        
        // Create a structured permissions object for the frontend
        $frontendPermissions = [
            'approvals' => $approvalPermission, // This matches what the Vue component expects
            'module' => 'CRM',
        ];

        return Inertia::render('Dashboard/CRM/Approval', [
            'pendingClients' => $pendingClients,
            'permissions' => $frontendPermissions,
        ]);
    }

    public function addNote(Request $request, $id)
    {
        // Check permission for approval page
        $permissions = $this->getPagePermissionsForModule('CRM');
        $canEdit = ($permissions['approval'] ?? 'view') === 'edit';
        
        if (!$canEdit) {
            return back()->with('error', 'You do not have permission to add notes.');
        }
        
        $request->validate(['note' => 'required|string']);

        $client = Client::findOrFail($id);
        $existing = $client->notes ? $client->notes . "\n" : '';
        $client->update(['notes' => $existing . $request->note]);

        return back()->with('message', 'Note added.');
    }

    public function scheduleMeeting(Request $request, $id)
    {
        // Check permission for approval page
        $permissions = $this->getPagePermissionsForModule('CRM');
        $canEdit = ($permissions['approval'] ?? 'view') === 'edit';
        
        if (!$canEdit) {
            return back()->with('error', 'You do not have permission to schedule meetings.');
        }
        
        $validated = $request->validate([
            'scheduled_at' => 'required|date',
            'meeting_type' => 'required|string',
            'location'     => 'nullable|string',
            'notes'        => 'nullable|string',
        ]);

        CrmMeeting::create([
            'client_id'    => $id,
            'scheduled_at' => $validated['scheduled_at'],
            'meeting_type' => $validated['meeting_type'],
            'location'     => $validated['location'],
            'notes'        => $validated['notes'],
            'created_by'   => Auth::id(),
            'status'       => 'scheduled',
        ]);

        return back()->with('message', 'Meeting scheduled.');
    }

    public function updateMeetingStatus(Request $request, $meetingId)
    {
        // Check permission for approval page
        $permissions = $this->getPagePermissionsForModule('CRM');
        $canEdit = ($permissions['approval'] ?? 'view') === 'edit';
        
        if (!$canEdit) {
            return back()->with('error', 'You do not have permission to update meeting status.');
        }
        
        $meeting = CrmMeeting::findOrFail($meetingId);
        $meeting->update(['status' => $request->status]);
        return back()->with('message', 'Meeting status updated.');
    }

    public function accept($id)
    {
        // Check permission for approval page
        $permissions = $this->getPagePermissionsForModule('CRM');
        $canEdit = ($permissions['approval'] ?? 'view') === 'edit';
        
        if (!$canEdit) {
            return back()->with('error', 'You do not have permission to accept clients.');
        }
        
        $client = Client::findOrFail($id);
        $client->update(['status' => 'active']);
        return back()->with('message', 'Client approved and now active.');
    }

    public function reject(Request $request, $id)
    {
        // Check permission for approval page
        $permissions = $this->getPagePermissionsForModule('CRM');
        $canEdit = ($permissions['approval'] ?? 'view') === 'edit';
        
        if (!$canEdit) {
            return back()->with('error', 'You do not have permission to reject clients.');
        }
        
        $request->validate(['reason' => 'required|string']);
        $client = Client::findOrFail($id);
        $client->update(['status' => 'rejected', 'rejection_reason' => $request->reason]);
        return back()->with('message', 'Client rejected.');
    }
}