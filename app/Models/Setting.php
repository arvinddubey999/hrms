<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $guarded = [];

    protected $casts = [
        'office_lat' => 'float',
        'office_lng' => 'float',
    ];

    public static function current(): self
    {
        return static::query()->first() ?? static::query()->create([
            'company_name' => 'RRV SOFTECH PRIVATE LIMITED',
            'company_address' => '400, 212F, Shipra Path, SFS Mansarovar, Jaipur, Rajasthan 302020',
            'office_lat' => 26.8581,
            'office_lng' => 75.7642,
            'geofence_radius_m' => 200,
        ]);
    }
}
