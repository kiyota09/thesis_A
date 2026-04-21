<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Fabric extends Model
{
    use HasFactory;

    /**
     * roll_no has been intentionally removed.
     * The auto-generated `code` column (e.g. FABRIC-2026-00001) is the
     * unique fabric identifier produced by ManufacturingStaffController::generateCode().
     */
    protected $fillable = [
        'code',
        'manufacturing_order_id',
        'machine_id',
        'yarn_type',
        'weight',
        'remarks',
        'operator_id',
        'shift',
        'processed_at',
        'status',
    ];

    protected $casts = [
        'weight'       => 'decimal:2',
        'processed_at' => 'datetime',
    ];

    // ─── Relationships ────────────────────────────────────────────────────────

    public function machine()
    {
        return $this->belongsTo(Machine::class);
    }

    public function operator()
    {
        return $this->belongsTo(User::class, 'operator_id');
    }

    public function manufacturingOrder()
    {
        return $this->belongsTo(ManufacturingOrder::class);
    }
}