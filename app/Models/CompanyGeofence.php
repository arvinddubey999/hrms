<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyGeofence extends Model
{
    protected $fillable = [
        'company_id',
        'name',
        'address',
        'latitude',
        'longitude',
        'radius',
        'category',
        'assigned_categories',
        'assigned_employees',
        'status',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'radius' => 'integer',
        'assigned_categories' => 'array',
        'assigned_employees' => 'array',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }
}
