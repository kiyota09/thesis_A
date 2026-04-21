<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CeoLocation extends Model {
    protected $fillable = ['user_id', 'latitude', 'longitude', 'range_radius', 'label'];

    public function user() {
        return $this->belongsTo(User::class);
    }
}