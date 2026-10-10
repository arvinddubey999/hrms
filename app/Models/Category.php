<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Category extends Model
{
    protected $guarded = [];

    public function setNameAttribute($value): void
    {
        $this->attributes['name'] = !empty($value) ? Str::title(mb_strtolower(trim($value))) : $value;
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
