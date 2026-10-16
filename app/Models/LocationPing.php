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
        'accuracy' => 'float',
        'points' => 'integer',
        'battery' => 'integer',
        'stayed_minutes' => 'integer',
        'speed' => 'float',
    ];

    protected $appends = [
        'google_maps_url',
        'formatted_time',
    ];

    public function getGoogleMapsUrlAttribute(): string
    {
        return "https://www.google.com/maps?q={$this->lat},{$this->lng}";
    }

    public function getFormattedTimeAttribute(): string
    {
        if (!empty($this->time_range)) {
            return $this->time_range;
        }
        return $this->pinged_at ? $this->pinged_at->format('h:i A') : '';
    }

    public function getBatteryFormattedAttribute(): string
    {
        return ($this->battery !== null) ? "{$this->battery}%" : '100%';
    }

    public function getAccuracyFormattedAttribute(): string
    {
        if ($this->accuracy === null) return '±5m';
        return "±" . (is_numeric($this->accuracy) ? round($this->accuracy, 1) : $this->accuracy) . "m";
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
