<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Holiday extends Model
{
    protected $fillable = ['name', 'date', 'description', 'company_ids'];

    protected $casts = [
        'date' => 'date',
        'company_ids' => 'array',
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
}
