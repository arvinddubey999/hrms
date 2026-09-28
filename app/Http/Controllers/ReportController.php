<?php

namespace App\Http\Controllers;

use App\Models\AttendancePunch;
use App\Models\User;
use App\Services\AttendanceService;
use Carbon\Carbon;
use Illuminate\Http\Request;

class ReportController extends Controller
{
    public function index()
    {
        return view('reports.index');
    }

    public function generate(Request $request, AttendanceService $attendance)
    {
        $type = $request->get('type', 'daywise');
        $from = Carbon::parse($request->get('from', now()->startOfMonth()));
        $to = Carbon::parse($request->get('to', now()));
        $employees = User::query()->where('status', 'active')->orderBy('first_name')->get();

        $filename = "{$type}-{$from->toDateString()}-{$to->toDateString()}.csv";
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($employees, $from, $to, $attendance, $type) {
            $out = fopen('php://output', 'w');
            if ($type === 'overall') {
                fputcsv($out, ['Employee', 'Present', 'Absent', 'Leave', 'Week Off', 'Late']);
                foreach ($employees as $user) {
                    $sum = $attendance->monthSummary($user, $from->year, $from->month);
                    fputcsv($out, [$user->displayName(), $sum['present'], $sum['absent'], $sum['leave'], $sum['weekOff'], $sum['late']]);
                }
            } else {
                fputcsv($out, ['Date', 'Employee', 'Status', 'IN', 'IN Loc', 'OUT', 'OUT Loc', 'Hours']);
                foreach ($employees as $user) {
                    $cursor = $from->copy();
                    while ($cursor->lte($to)) {
                        $sum = $attendance->monthSummary($user, $cursor->year, $cursor->month);
                        $row = collect($sum['rows'])->firstWhere('date', $cursor->toDateString());
                        if ($row) {
                            $in = $row['ins']->first();
                            $outPunch = $row['outs']->last();
                            fputcsv($out, [
                                $cursor->toDateString(),
                                $user->displayName(),
                                $row['status'],
                                $in?->punched_at?->format('h:i A'),
                                $in?->location_text,
                                $outPunch?->punched_at?->format('h:i A'),
                                $outPunch?->location_text,
                                $row['hours'],
                            ]);
                        }
                        $cursor->addDay();
                    }
                }
            }
            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }

    public function tracker(Request $request, AttendanceService $attendance)
    {
        $from = Carbon::parse($request->get('from', now()->startOfMonth()));
        $employees = User::query()->where('status', 'active')->with('category')->orderBy('department')->orderBy('first_name')->get();

        return view('reports.tracker', compact('employees', 'from', 'attendance'));
    }
}
