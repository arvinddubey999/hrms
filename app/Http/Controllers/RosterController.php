<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\Department;
use App\Models\User;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class RosterController extends Controller
{
    protected function getScopedRosterQuery(Request $request, ?User $user)
    {
        $employeeId = $request->get('employee_id');
        $departmentId = $request->get('department_id');
        $companyId = $request->get('company_id');

        $isSuper = $user && $user->isSuperAdmin();
        $accessible = $user ? $user->accessibleCompanyIds() : [];

        $query = User::query()->where('status', 'active');

        // Scoping by user's assigned companies
        if (!$isSuper) {
            if (!empty($accessible)) {
                $query->whereIn('company_id', $accessible);
            } else {
                $query->where('company_id', $user ? $user->company_id : 0);
            }
        }

        // Apply selected company filter
        if ($companyId) {
            if ($isSuper || in_array((int)$companyId, $accessible, true)) {
                $query->where('company_id', $companyId);
            }
        }

        if ($employeeId) {
            $query->where('id', $employeeId);
        }
        if ($departmentId) {
            $query->where('department_id', $departmentId);
        }

        return $query;
    }

    public function index(Request $request, AttendanceService $attendance)
    {
        $month = (int) $request->get('m', now()->month);
        $year = (int) $request->get('y', now()->year);
        $fromDate = $request->get('from_date');
        $toDate = $request->get('to_date');
        $employeeId = $request->get('employee_id');
        $departmentId = $request->get('department_id');
        $companyId = $request->get('company_id');

        $user = auth()->user();
        if ($user && !$user->hasPermission('roster.view') && !$user->hasPermission('attendance.view')) {
            return back()->with('error', 'You do not have permission to view Monthly Roster.');
        }

        $isSuper = $user && $user->isSuperAdmin();
        $accessible = $user ? $user->accessibleCompanyIds() : [];

        // Companies dropdown list: ONLY accessible companies for non-superadmin!
        $companiesQuery = Company::orderBy('name');
        if (!$isSuper) {
            $companiesQuery->whereIn('id', !empty($accessible) ? $accessible : [0]);
        }
        $companies = $companiesQuery->get();

        // If only 1 company is assigned and no company_id in request, default to that company
        if (!$isSuper && $companies->count() === 1 && empty($companyId)) {
            $companyId = $companies->first()->id;
            $request->merge(['company_id' => $companyId]);
        }

        $query = $this->getScopedRosterQuery($request, $user);
        $employees = $query->with(['category', 'shift'])->orderBy('first_name')->get();

        // Employees dropdown list for filters: also scoped!
        $allEmployeesQuery = User::where('status', 'active');
        if (!$isSuper) {
            $allEmployeesQuery->whereIn('company_id', !empty($accessible) ? $accessible : [0]);
        }
        if ($companyId) {
            $allEmployeesQuery->where('company_id', $companyId);
        }
        if ($departmentId) {
            $allEmployeesQuery->where('department_id', $departmentId);
        }
        $allEmployees = $allEmployeesQuery->orderBy('first_name')->get();

        if ($fromDate && $toDate) {
            $startDate = Carbon::parse($fromDate);
            $endDate = Carbon::parse($toDate);
            $dateRange = [];
            $curr = $startDate->copy();
            while ($curr->lte($endDate)) {
                $dateRange[] = $curr->copy();
                $curr->addDay();
            }
            $days = count($dateRange);
        } else {
            $startDate = Carbon::create($year, $month, 1);
            $days = $startDate->daysInMonth;
            $dateRange = [];
            for ($d = 1; $d <= $days; $d++) {
                $dateRange[] = Carbon::create($year, $month, $d);
            }
            $fromDate = $startDate->toDateString();
            $toDate = Carbon::create($year, $month, $days)->toDateString();
        }

        return view('roster.index', [
            'employees' => $employees,
            'month' => $month,
            'year' => $year,
            'days' => $days,
            'dateRange' => $dateRange,
            'fromDate' => $fromDate,
            'toDate' => $toDate,
            'attendance' => $attendance,
            'employeeId' => $employeeId,
            'departmentId' => $departmentId,
            'companyId' => $companyId,
            'allEmployees' => $allEmployees,
            'departments' => Department::orderBy('name')->get(),
            'companies' => $companies,
        ]);
    }

    public function exportExcel(Request $request, AttendanceService $attendance)
    {
        $month = (int) $request->get('m', now()->month);
        $year = (int) $request->get('y', now()->year);
        $fromDate = $request->get('from_date');
        $toDate = $request->get('to_date');

        $user = auth()->user();
        $query = $this->getScopedRosterQuery($request, $user);
        $employees = $query->with(['category', 'shift'])->orderBy('first_name')->get();

        if ($fromDate && $toDate) {
            $startDate = Carbon::parse($fromDate);
            $endDate = Carbon::parse($toDate);
            $dateRange = [];
            $curr = $startDate->copy();
            while ($curr->lte($endDate)) {
                $dateRange[] = $curr->copy();
                $curr->addDay();
            }
        } else {
            $startDate = Carbon::create($year, $month, 1);
            $days = $startDate->daysInMonth;
            $dateRange = [];
            for ($d = 1; $d <= $days; $d++) {
                $dateRange[] = Carbon::create($year, $month, $d);
            }
        }

        $filename = "monthly-roster-{$year}-{$month}.csv";
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($employees, $dateRange, $attendance, $year, $month) {
            $out = fopen('php://output', 'w');
            $headerRow = ['Employee Code', 'Employee Name', 'Category', 'Department', 'Shift Time'];
            foreach ($dateRange as $dt) {
                $headerRow[] = $dt->format('d/m (D)');
            }
            $headerRow = array_merge($headerRow, ['P', 'A', 'H', 'WO', 'WO-P', 'H-P', 'TOTAL PAYABLE']);
            fputcsv($out, $headerRow);

            foreach ($employees as $emp) {
                $shiftTime = $emp->shift ? date('h:i A', strtotime($emp->shift->start_time)) . ' - ' . date('h:i A', strtotime($emp->shift->end_time)) : 'Not Assigned';
                $row = [
                    $emp->employee_code ?: '-',
                    $emp->displayName(),
                    $emp->category?->name ?? '-',
                    $emp->department ?: '-',
                    $shiftTime
                ];

                $sum = $attendance->monthSummary($emp, $year, $month, $dateRange);
                $rowsByDate = collect($sum['rows'])->keyBy('date');

                foreach ($dateRange as $date) {
                    $dateStr = $date->toDateString();
                    $dayData = $rowsByDate->get($dateStr);
                    $st = $dayData['status'] ?? '-';
                    $ins = $dayData['ins'] ?? collect();
                    $outs = $dayData['outs'] ?? collect();

                    $stCode = match($st) {
                        'present' => 'P',
                        'late' => 'P(L)',
                        'wop' => 'WO-P',
                        'hop' => 'H-P',
                        'absent' => 'A',
                        'leave' => 'L',
                        'week_off' => 'WO',
                        'holiday' => 'H',
                        default => '-'
                    };

                    $times = '';
                    $firstIn = $ins->first();
                    $lastOut = $outs->last();
                    if ($firstIn) {
                        $times .= ' IN:' . $firstIn->punched_at->format('h:i A');
                    }
                    if ($lastOut) {
                        $times .= ' OUT:' . $lastOut->punched_at->format('h:i A');
                    }

                    $row[] = $stCode . ($times ? " ({$times})" : '');
                }

                // Append summary totals requested by client
                $row[] = $sum['present'] ?? 0;
                $row[] = $sum['absent'] ?? 0;
                $row[] = $sum['holiday'] ?? 0;
                $row[] = $sum['weekOff'] ?? 0;
                $row[] = $sum['wop'] ?? 0;
                $row[] = $sum['hop'] ?? 0;
                $row[] = $sum['payable'] ?? 0;

                fputcsv($out, $row);
            }
            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function exportPdf(Request $request, AttendanceService $attendance)
    {
        $month = (int) $request->get('m', now()->month);
        $year = (int) $request->get('y', now()->year);
        $fromDate = $request->get('from_date');
        $toDate = $request->get('to_date');

        $user = auth()->user();
        $query = $this->getScopedRosterQuery($request, $user);
        $employees = $query->with(['category', 'shift'])->orderBy('first_name')->get();

        if ($fromDate && $toDate) {
            $startDate = Carbon::parse($fromDate);
            $endDate = Carbon::parse($toDate);
            $dateRange = [];
            $curr = $startDate->copy();
            while ($curr->lte($endDate)) {
                $dateRange[] = $curr->copy();
                $curr->addDay();
            }
            $days = count($dateRange);
        } else {
            $startDate = Carbon::create($year, $month, 1);
            $days = $startDate->daysInMonth;
            $dateRange = [];
            for ($d = 1; $d <= $days; $d++) {
                $dateRange[] = Carbon::create($year, $month, $d);
            }
        }

        return view('roster.pdf', compact('employees', 'month', 'year', 'days', 'dateRange', 'fromDate', 'toDate', 'attendance'));
    }
}
