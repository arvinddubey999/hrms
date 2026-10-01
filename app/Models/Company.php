<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Company extends Model
{
    protected $fillable = ['name', 'logo', 'latitude', 'longitude'];

    public function employees()
    {
        return $this->hasMany(User::class);
    }
}
