<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\Company;
use App\Models\Department;
use App\Models\Holiday;
use App\Models\Setting;
use Carbon\Carbon;
use Illuminate\Http\Request;

class HolidayController extends Controller
{
    public function index(Request $request)
    {
        $u = $request->user();
        if (!$u || (!$u->hasPermission('holiday.view') && !$u->hasPermission('settings.view') && !$u->isSuperAdmin())) {
            return back()->with('error', 'You do not have permission to view Holidays.');
        }

        $year = (int) $request->get('year', now()->year);
        $companyId = $request->get('company_id');

        $isSuper = $u->isSuperAdmin();
        $accessible = $u->accessibleCompanyIds();

        $companiesQuery = Company::orderBy('name');
        if (!$isSuper) {
            $companiesQuery->whereIn('id', !empty($accessible) ? $accessible : [0]);
        }
        $companies = $companiesQuery->get();

        $query = Holiday::query()
            ->whereYear('date', $year)
            ->orderBy('date', 'asc');

        if ($companyId) {
            $query->where(function ($q) use ($companyId) {
                $q->whereJsonContains('company_ids', (int)$companyId)
                  ->orWhereJsonContains('company_ids', (string)$companyId)
                  ->orWhereNull('company_ids')
                  ->orWhere('company_ids', '[]')
                  ->orWhere('company_ids', '');
            });
        } elseif (!$isSuper && !empty($accessible)) {
            $query->where(function ($q) use ($accessible) {
                foreach ($accessible as $cId) {
                    $q->orWhereJsonContains('company_ids', (int)$cId)
                      ->orWhereJsonContains('company_ids', (string)$cId);
                }
                $q->orWhereNull('company_ids')
                  ->orWhere('company_ids', '[]')
                  ->orWhere('company_ids', '');
            });
        }

        $holidays = $query->get();
        $today = Carbon::today();

        $upcomingCount = $holidays->filter(fn($h) => $h->date->gte($today))->count();
        $pastCount = $holidays->filter(fn($h) => $h->date->lt($today))->count();

        $canManage = $isSuper || $u->hasPermission('settings.manage') || $u->hasPermission('settings.view');

        return view('holidays.index', [
            'holidays' => $holidays,
            'year' => $year,
            'companyId' => $companyId,
            'companies' => $companies,
            'departments' => Department::orderBy('name')->get(),
            'categories' => Category::orderBy('name')->get(),
            'setting' => Setting::current(),
            'upcomingCount' => $upcomingCount,
            'pastCount' => $pastCount,
            'totalCount' => $holidays->count(),
            'canManage' => $canManage,
            'nav' => 'holidays',
        ]);
    }
}

