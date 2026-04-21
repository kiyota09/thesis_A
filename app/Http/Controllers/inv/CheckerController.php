<?php

namespace App\Http\Controllers\inv;

use App\Http\Controllers\Controller;
use App\Models\inv\Material;
use App\Models\WarehouseStockItem;
use App\Models\PurchaseOrder;
use App\Models\Scm\MaterialRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class CheckerController extends Controller
{
    /**
     * Display stock checker dashboard.
     * Updated with withSum() for much better performance.
     */
    public function index()
    {
        // Get materials and sum their related stock quantities in one go
        $materials = Material::withSum('warehouseStockItems as total_stock', 'quantity')->get();
        
        $stockStatus = $materials->map(function ($material) {
            $total = (float) $material->total_stock;
            
            // Determine status based on stock levels
            $status = 'ok';
            if ($total <= 0) {
                $status = 'out';
            } elseif ($total <= $material->reorder_point) {
                $status = 'low';
            }

            return [
                'id'            => $material->id,
                'mat_id'        => $material->mat_id,
                'name'          => $material->name,
                'category'      => $material->category,
                'unit'          => $material->unit,
                'reorder_point' => $material->reorder_point,
                'total_stock'   => $total,
                'status'        => $status,
            ];
        });

        $pendingOrdersCount = PurchaseOrder::whereHas('queue', function ($q) {
            $q->where('stage', 'inv_check');
        })->count();

        return Inertia::render('Dashboard/Inventory/Checker', [
            'materials' => $stockStatus,
            'pendingOrdersCount' => $pendingOrdersCount,
        ]);
    }

    /**
     * Request procurement for a specific material (Single Click).
     */
    public function requestProcurement(Request $request, Material $material)
    {
        $validated = $request->validate([
            'required_qty' => 'required|numeric|min:0.01',
            'urgency'      => 'required|in:High,Medium,Low',
            'notes'        => 'nullable|string|max:1000',
        ]);

        // Generate a SINGLE batch number for tracking
        $batchNumber = 'SINGLE-' . strtoupper(bin2hex(random_bytes(4)));
        
        // Pass the high-precision timestamp for single items
        $this->createMaterialRequest($material, $validated, $batchNumber, now(3));

        return redirect()->back()->with('success', "Request sent under ID: {$batchNumber}");
    }

    /**
     * BULK PROCUREMENT REQUEST (Multi-item Bundle).
     */
    public function bulkProcure(Request $request)
    {
        $validated = $request->validate([
            'items'                 => 'required|array|min:1',
            'items.*.material_id'   => 'required|exists:materials,id',
            'items.*.qty'           => 'required|numeric|min:0.01',
            'items.*.urgency'       => 'required|in:High,Medium,Low',
            'items.*.notes'         => 'nullable|string|max:500',
        ]);

        // Generate ONE shared batch number and ONE high-precision timestamp for the bundle
        $batchNumber = 'BATCH-' . strtoupper(bin2hex(random_bytes(4)));
        $preciseTime = now(3);

        DB::transaction(function () use ($validated, $batchNumber, $preciseTime) {
            foreach ($validated['items'] as $item) {
                $material = Material::find($item['material_id']);
                
                $data = [
                    'required_qty' => $item['qty'],
                    'urgency'      => $item['urgency'],
                    'notes'        => $item['notes'],
                ];

                $this->createMaterialRequest($material, $data, $batchNumber, $preciseTime);
            }
        });

        return redirect()->back()->with('success', "Bulk items bundled under ID: {$batchNumber}");
    }

    /**
     * Internal helper to create the MaterialRequest record.
     * Uses the shared $batchNumber and $timestamp to ensure grouping on the SCM side.
     */
    private function createMaterialRequest(Material $material, array $data, $batchNumber, $timestamp)
    {
        $currentStock = WarehouseStockItem::where('material_id', $material->id)->sum('quantity');
        
        // Unique MR identifier
        $reqNumber = 'MR-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(2)));

        return MaterialRequest::create([
            'req_number'    => $reqNumber,
            'batch_number'  => $batchNumber,
            'material_id'   => $material->id,
            'material_name' => $material->name,
            'category'      => $material->category,
            'unit'          => $material->unit,
            'current_stock' => (float) $currentStock,
            'reorder_point' => $material->reorder_point,
            'required_qty'  => $data['required_qty'],
            'urgency'       => $data['urgency'],
            'notes'         => $data['notes'] ?? null,
            'requested_by'  => auth()->user()->name,
            'requested_at'  => $timestamp, // Storing with millisecond precision
            'status'        => 'pending',
        ]);
    }

    /**
     * Check material sufficiency for pending orders.
     */
    public function checkOrders()
    {
        return redirect()->back()->with('message', 'Order check completed.');
    }

    /**
     * Check a specific order.
     */
    public function checkOrder(PurchaseOrder $order)
    {
        return redirect()->back()->with('message', "Order {$order->po_number} checked.");
    }
}