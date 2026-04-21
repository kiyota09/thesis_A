<?php

namespace App\Models;

use App\Models\inv\Material;
use Illuminate\Database\Eloquent\Model;

class WarehouseStockItem extends Model
{
    protected $fillable = [
        'control_number',
        'warehouse_id',
        'section_id',
        'shelf_id',
        'material_id',
        'quantity',
        'unit',
        'received_at',
        'received_by',
        'purchase_order_id',
        'status'
    ];

    protected $casts = [
        'received_at' => 'datetime'
    ];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function section()
    {
        return $this->belongsTo(WarehouseSection::class, 'section_id');
    }

    public function shelf()
    {
        return $this->belongsTo(WarehouseShelf::class);
    }

    public function material()
    {
        return $this->belongsTo(Material::class);
    }

    public function receivedBy()
    {
        return $this->belongsTo(User::class, 'received_by');
    }
}