<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Company extends Model
{
    protected $fillable = [
        'name',
        'location',
        'logo',
        'latitude',
        'longitude',
        'salary_calculation_days',
        'pt_enabled',
        'pt_threshold',
        'pt_amount',
        'code_prefix',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'pt_enabled' => 'boolean',
        'pt_threshold' => 'float',
        'pt_amount' => 'float',
    ];

    public function setNameAttribute($value): void
    {
        $this->attributes['name'] = !empty($value) ? Str::title(mb_strtolower(trim($value))) : $value;
    }

    public function employees()
    {
        return $this->hasMany(User::class);
    }
}
