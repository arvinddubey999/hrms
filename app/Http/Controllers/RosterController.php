<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class RosterController extends Controller
{
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

        $query = User::query()->where('status', 'active');

        if ($user && $user->company_id && !$user->isAdmin()) {
            $query->where('company_id', $user->company_id);
        } elseif ($companyId) {
            $query->where('company_id', $companyId);
        }

        if ($employeeId) {
            $query->where('id', $employeeId);
        }
        if ($departmentId) {
            $query->where('department_id', $departmentId);
        }

        $employees = $query->orderBy('first_name')->get();

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
            'allEmployees' => User::where('status', 'active')->orderBy('first_name')->get(),
            'departments' => \App\Models\Department::orderBy('name')->get(),
            'companies' => \App\Models\Company::orderBy('name')->get(),
        ]);
    }

    public function exportExcel(Request $request, AttendanceService $attendance)
    {
        $month = (int) $request->get('m', now()->month);
        $year = (int) $request->get('y', now()->year);
        $fromDate = $request->get('from_date');
        $toDate = $request->get('to_date');

        $user = auth()->user();
        $query = User::query()->where('status', 'active');
        if ($user && $user->company_id && !$user->isAdmin()) {
            $query->where('company_id', $user->company_id);
        }
        $employees = $query->orderBy('first_name')->get();

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

        $callback = function () use ($employees, $dateRange, $attendance) {
            $out = fopen('php://output', 'w');
            $headerRow = ['Employee Code', 'Employee Name', 'Department'];
            foreach ($dateRange as $dt) {
                $headerRow[] = $dt->format('d/m (D)');
            }
            fputcsv($out, $headerRow);

            foreach ($employees as $emp) {
                $row = [$emp->employee_code, $emp->displayName(), $emp->department ?: '-'];
                foreach ($dateRange as $date) {
                    $st = $attendance->dayStatus($emp, $date);
                    $ins = \App\Models\AttendancePunch::where('user_id', $emp->id)->whereDate('work_date', $date)->where('type', 'in')->orderBy('punched_at')->first();
                    $outs = \App\Models\AttendancePunch::where('user_id', $emp->id)->whereDate('work_date', $date)->where('type', 'out')->orderByDesc('punched_at')->first();

                    $stCode = match($st) {
                        'present' => 'P',
                        'late' => 'P(L)',
                        'wop' => 'WOP',
                        'hop' => 'HOP',
                        'absent' => 'A',
                        'leave' => 'L',
                        'week_off' => 'WO',
                        'holiday' => 'H',
                        default => '-'
                    };

                    $times = '';
                    if ($ins) {
                        $times .= ' IN:' . $ins->punched_at->format('H:i');
                    }
                    if ($outs) {
                        $times .= ' OUT:' . $outs->punched_at->format('H:i');
                    }

                    $row[] = $stCode . ($times ? " ({$times})" : '');
                }
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
        $employees = User::query()->where('status', 'active')->orderBy('first_name')->get();
        $days = Carbon::create($year, $month, 1)->daysInMonth;

        return view('roster.pdf', compact('employees', 'month', 'year', 'days', 'attendance'));
    }
}
