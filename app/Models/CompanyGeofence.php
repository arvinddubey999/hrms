<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CompanyGeofence extends Model
{
    protected $fillable = [
        'company_id',
        'assigned_companies',
        'name',
        'address',
        'latitude',
        'longitude',
        'radius',
        'category',
        'assigned_categories',
        'assigned_departments',
        'assigned_employees',
        'status',
    ];

    protected $casts = [
        'latitude' => 'float',
        'longitude' => 'float',
        'radius' => 'integer',
        'assigned_companies' => 'array',
        'assigned_categories' => 'array',
        'assigned_departments' => 'array',
        'assigned_employees' => 'array',
    ];

    public function company()
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Determine if this geofence applies to the given user.
     */
    public function appliesToUser(?User $user): bool
    {
        if (!$user) {
            return false;
        }

        // Super Admin can punch at any office geofence
        if ($user->isSuperAdmin()) {
            return true;
        }

        // 1. If explicit employees are assigned, check if user is in that list
        if (!empty($this->assigned_employees)) {
            $empIds = array_map('strval', $this->assigned_employees);
            if (in_array((string) $user->id, $empIds, true)) {
                return true;
            }
            // If explicit employees are assigned and user is not one of them, reject
            return false;
        }

        // 2. Company filter check
        if (!empty($this->assigned_companies)) {
            $compIds = array_map('strval', $this->assigned_companies);
            if ($user->company_id && !in_array((string) $user->company_id, $compIds, true)) {
                return false;
            }
        } elseif ($this->company_id) {
            if ($user->company_id && (int) $user->company_id !== (int) $this->company_id) {
                return false;
            }
        }

        // 3. Department filter check
        if (!empty($this->assigned_departments)) {
            $deptIds = array_map('strval', $this->assigned_departments);
            if (!$user->department_id || !in_array((string) $user->department_id, $deptIds, true)) {
                return false;
            }
        }

        // 4. Category filter check (legacy)
        if (!empty($this->assigned_categories)) {
            $catIds = array_map('strval', $this->assigned_categories);
            if (!$user->category_id || !in_array((string) $user->category_id, $catIds, true)) {
                return false;
            }
        }

        return true;
    }
}
