<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    protected $fillable = ['name', 'date', 'description', 'company_ids', 'department_ids', 'category_ids'];

    protected $casts = [
        'date' => 'date',
        'company_ids' => 'array',
        'department_ids' => 'array',
        'category_ids' => 'array',
    ];

    public function isApplicableToCompany(?int $companyId): bool
    {
        if (empty($this->company_ids)) {
            return true; // Applicable to all if empty
        }
        if (!$companyId) {
            return true;
        }
        return in_array($companyId, $this->company_ids) || in_array((string)$companyId, $this->company_ids);
    }

    public function isApplicableToDepartment(?int $deptId): bool
    {
        if (empty($this->department_ids)) {
            return true; // Applicable to all if empty
        }
        if (!$deptId) {
            return true;
        }
        return in_array($deptId, $this->department_ids) || in_array((string)$deptId, $this->department_ids);
    }

    public function isApplicableToCategory(?int $catId): bool
    {
        if (empty($this->category_ids)) {
            return true; // Applicable to all if empty
        }
        if (!$catId) {
            return true;
        }
        return in_array($catId, $this->category_ids) || in_array((string)$catId, $this->category_ids);
    }
}
