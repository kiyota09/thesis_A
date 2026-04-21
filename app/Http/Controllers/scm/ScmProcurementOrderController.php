<?php

namespace App\Http\Controllers\scm;

use App\Http\Controllers\Controller;
use App\Models\Scm\MaterialRequest;
use Inertia\Inertia;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class ScmProcurementOrderController extends Controller
{
   public function index()
{
    $requests = MaterialRequest::where('status', 'pending')
        ->orderBy('created_at', 'desc')
        ->get()
        ->map(fn($r) => [
            'id' => $r->id,
            'req_number' => $r->req_number,
            'batch_number' => $r->batch_number, // <--- CRITICAL FIX
            'material_name' => $r->material_name,
            'required_qty' => $r->required_qty,
            'unit' => $r->unit,
            'urgency' => $r->urgency,
            'requested_by' => $r->requested_by,
            'created_at' => $r->created_at->toDateTimeString(),
        ]);

    return Inertia::render('Dashboard/SCM/ProcurementOrder', [
        'procurementRequests' => $requests
    ]);
}

    public function sendToProcurementModule(MaterialRequest $materialRequest)
    {
        if ($materialRequest->status !== 'pending') {
            return redirect()->back()->withErrors(['error' => 'Request already processed.']);
        }

        $materialRequest->update([
            'status' => 'forwarded',
            'forwarded_at' => now(),
            'forwarded_by' => auth()->id(),
        ]);

        return redirect()->back()->with('success', 'Procurement request sent to PRO module.');
    }

    public function sendBundle(Request $request)
    {
        $validated = $request->validate([
            'batch_number' => 'required|string',
        ]);

        // Start a transaction to ensure either ALL update or NONE update
        DB::beginTransaction();

        try {
            // 1. Find all items belonging to this batch
            $items = MaterialRequest::where('batch_number', $validated['batch_number'])
                ->where('status', 'pending')
                ->get();

            if ($items->isEmpty()) {
                // Fallback: If no batch found, try to find by req_number (for old data)
                $items = MaterialRequest::where('req_number', $validated['batch_number'])
                    ->where('status', 'pending')
                    ->get();
            }

            if ($items->isEmpty()) {
                return redirect()->back()->withErrors(['error' => 'Batch not found or already processed.']);
            }

            // 2. Update the status for all items in the batch
            foreach ($items as $item) {
                $item->update([
                    'status' => 'forwarded', // Or whatever status your PRO module looks for
                    'forwarded_at' => now(),
                ]);
            }

            DB::commit();
            return redirect()->back()->with('success', 'Batch successfully forwarded to Procurement.');

        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->withErrors(['error' => 'Database Error: ' . $e->getMessage()]);
        }
    }
}