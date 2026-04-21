<?php

namespace App\Http\Controllers\man\Staff;

use App\Models\Fabric;
use App\Models\Machine;
use App\Models\MachineReport;
use App\Models\ManufacturingInventoryItem;
use App\Models\SalesOrder;
use App\Models\inv\Material;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;

class KnittingYarnController extends ManufacturingStaffController
{
    /**
     * Dashboard: show pending tasks and recent activity.
     */
    public function index()
    {
        $pendingCount = Fabric::where('status', 'pending')->count();
        $recentFabrics = Fabric::with('machine')
            ->where('operator_id', $this->staff()->id)
            ->latest()
            ->take(10)
            ->get();

        return Inertia::render('Dashboard/MAN/Employee/KnittingYarn/Index', [
            'stats' => [
                'pending' => $pendingCount,
                'total_today' => Fabric::whereDate('processed_at', today())->count(),
            ],
            'recentFabrics' => $recentFabrics,
        ]);
    }

    /**
     * Show the main knitting yarn page with available yarns from production inventory
     * and assigned job orders with recipes.
     */
    public function knittingYarn()
    {
        $machines = Machine::where('type', 'knitting')
            ->where('status', 'available')
            ->get(['id', 'machine_no']);

        // Fetch only roll-based yarn items for knitting department
        $yarns = ManufacturingInventoryItem::with('material')
            ->where('department', 'knitting')
            ->where('category', 'Yarn')
            ->where('status', '!=', 'depleted')
            ->whereNotNull('total_units')
            ->where('total_units', '>', 0)
            ->where('unit_type', 'roll')
            ->get()
            ->map(function ($item) {
                $availableUnits = $item->total_units - $item->used_units;
                return [
                    'id'                 => $item->id,
                    'control_number'     => $item->control_number,
                    'material_name'      => $item->material->name,
                    'remaining_quantity' => $item->remaining_quantity,
                    'unit'               => $item->unit,
                    'total_units'        => $item->total_units,
                    'used_units'         => $item->used_units,
                    'unit_type'          => $item->unit_type,
                    'unit_weight'        => $item->unit_weight,
                    'available_units'    => $availableUnits,
                ];
            });

        // Get job orders assigned to knitting (status in_production) with recipe
        $jobOrders = SalesOrder::with('recipe')
            ->where('status', 'in_production')
            ->get()
            ->map(function ($order) {
                $recipe = $order->recipe;
                $materialsData = [];

                if ($recipe && $recipe->materials) {
                    $jsonMaterials = json_decode($recipe->materials, true);
                    if (is_array($jsonMaterials)) {
                        $materialIds = array_keys($jsonMaterials);
                        $materials = Material::whereIn('id', $materialIds)->get()->keyBy('id');
                        foreach ($jsonMaterials as $materialId => $qty) {
                            $material = $materials[$materialId] ?? null;
                            if ($material) {
                                $materialsData[] = [
                                    'name'     => $material->name,
                                    'quantity' => $qty,
                                    'unit'     => $material->unit,
                                ];
                            }
                        }
                    }
                }

                return [
                    'id'        => $order->id,
                    'jo_number' => $order->jo_number,
                    'color'     => $order->color,
                    'quantity'  => $order->quantity,
                    'yarn_type' => $order->yarn_type,
                    'design'    => $order->design,
                    'recipe'    => $recipe ? [
                        'id'        => $recipe->id,
                        'yarn_type' => $recipe->yarn_type,
                        'materials' => $materialsData,
                    ] : null,
                ];
            });

        return Inertia::render('Dashboard/MAN/Employee/KnittingYarn/KnittingYarn', [
            'machines'  => $machines,
            'yarns'     => $yarns,
            'jobOrders' => $jobOrders,
        ]);
    }

    /**
     * Record a new fabric from the knitting machine, consuming yarns from inventory.
     * roll_no has been removed — the fabric code serves as the unique identifier.
     */
    public function storeFabric(Request $request)
    {
        $validated = $request->validate([
            'machine_id'    => 'required|exists:machines,id',
            'yarn_type'     => 'required|string|max:255',
            'weight'        => 'required|numeric|min:0',
            'remarks'       => 'nullable|string',
            'yarns_used'    => 'required|array|min:1',
            'yarns_used.*.inventory_item_id' => 'required|exists:manufacturing_inventory_items,id',
            'yarns_used.*.units_used'        => 'required|integer|min:1',
        ]);

        // Pre-validate sufficient rolls for each yarn
        $insufficient = [];
        foreach ($validated['yarns_used'] as $usage) {
            $item = ManufacturingInventoryItem::find($usage['inventory_item_id']);
            if (!$item) {
                return back()->withErrors(['yarns_used' => 'Invalid yarn item selected.']);
            }
            $available = $item->total_units - $item->used_units;
            if ($available < $usage['units_used']) {
                $insufficient[] = $item->material->name . ' (only ' . $available . ' rolls left)';
            }
        }
        if (!empty($insufficient)) {
            return back()->withErrors([
                'yarns_used' => 'Insufficient rolls: ' . implode(', ', $insufficient),
            ]);
        }

        DB::beginTransaction();
        try {
            // Create the fabric record (roll_no removed)
            $fabric = Fabric::create([
                'code'                   => $this->generateCode('FABRIC', Fabric::class),
                'manufacturing_order_id' => null,
                'machine_id'             => $validated['machine_id'],
                'yarn_type'              => $validated['yarn_type'],
                'weight'                 => $validated['weight'],
                'remarks'                => $validated['remarks'],
                'operator_id'            => $this->staff()->id,
                'shift'                  => $this->getShift(),
                'processed_at'           => now(),
                'status'                 => 'pending',
            ]);

            // Consume yarns from manufacturing inventory
            foreach ($validated['yarns_used'] as $usage) {
                $item = ManufacturingInventoryItem::findOrFail($usage['inventory_item_id']);
                $consumed = $item->consumeUnits($usage['units_used']);
                if (!$consumed) {
                    throw new \Exception("Failed to consume units for {$item->material->name}");
                }
            }

            DB::commit();
            return redirect()->back()->with('message', 'Fabric recorded successfully and yarn inventory updated.');
        } catch (\Exception $e) {
            DB::rollBack();
            return back()->withErrors(['error' => 'Failed to record fabric: ' . $e->getMessage()]);
        }
    }

    /**
     * Show list of reported machines and allow reporting.
     */
    public function reports()
    {
        $machines = Machine::where('type', 'knitting')->get(['id', 'machine_no', 'status']);
        $myReports = MachineReport::where('reported_by', $this->staff()->id)
            ->with('machine')
            ->latest()
            ->get();

        return Inertia::render('Dashboard/MAN/Employee/KnittingYarn/Reports', [
            'machines'  => $machines,
            'myReports' => $myReports,
        ]);
    }

    /**
     * Report a machine issue.
     */
    public function reportMachine(Request $request)
    {
        $validated = $request->validate([
            'machine_id' => 'required|exists:machines,id',
            'issue'      => 'required|string',
        ]);

        MachineReport::create([
            'machine_id'  => $validated['machine_id'],
            'reported_by' => $this->staff()->id,
            'issue'       => $validated['issue'],
            'status'      => 'pending',
        ]);

        return redirect()->back()->with('message', 'Machine issue reported.');
    }
}