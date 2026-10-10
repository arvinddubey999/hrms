<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Designation extends Model
{
    protected $fillable = [
        'name',
        'permissions',
    ];

    protected $casts = [
        'permissions' => 'array',
    ];

    public function setNameAttribute($value): void
    {
        $this->attributes['name'] = !empty($value) ? Str::title(mb_strtolower(trim($value))) : $value;
    }
}
