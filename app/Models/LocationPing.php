<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LocationPing extends Model
{
    protected $guarded = [];

    protected $casts = [
        'pinged_at' => 'datetime',
        'inside_geofence' => 'boolean',
        'lat' => 'float',
        'lng' => 'float',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
