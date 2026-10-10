<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Detailed Attendance Report - {{ $companyName }}</title>
    <style>
        body { font-family: 'Segoe UI', Arial, sans-serif; background: #f8fafc; color: #1e293b; margin: 0; padding: 20px; font-size: 11px; }
        .report-header { text-align: center; position: relative; margin-bottom: 24px; padding-bottom: 12px; border-bottom: 2px solid #e2e8f0; }
        .report-header h1 { margin: 0; font-size: 20px; font-weight: 800; color: #0f172a; text-transform: uppercase; letter-spacing: 0.5px; }
        .report-header .sub-right { position: absolute; right: 0; top: 0; text-align: right; }
        .report-header .sub-right .title { font-style: italic; font-size: 13px; font-weight: 600; color: #475569; }
        .report-header .sub-right .date { font-size: 12px; color: #64748b; font-weight: 500; }

        .emp-card { background: #fff; border: 1px solid #cbd5e1; border-radius: 8px; margin-bottom: 24px; overflow: hidden; box-shadow: 0 1px 3px rgba(0,0,0,0.05); page-break-inside: avoid; }
        .emp-head { background: #f1f5f9; padding: 8px 14px; display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid #cbd5e1; font-weight: 700; font-size: 12px; }
        .emp-head .left { color: #0f172a; }
        .emp-head .right { color: #475569; }

        .summary-table { width: 100%; border-collapse: collapse; text-align: center; }
        .summary-table th { background: #334155; color: #fff; padding: 5px 8px; font-weight: 700; font-size: 10px; border-right: 1px solid #475569; }
        .summary-table th:last-child { border-right: 0; }
        .summary-table td { background: #f8fafc; padding: 6px 8px; font-weight: 700; color: #0f172a; font-size: 11px; border-right: 1px solid #e2e8f0; }
        .summary-table td:last-child { border-right: 0; }

        .matrix-table { width: 100%; border-collapse: collapse; text-align: center; margin-top: 4px; font-size: 10px; }
        .matrix-table th, .matrix-table td { border: 1px solid #cbd5e1; padding: 4px 2px; }
        .matrix-table th { background: #f8fafc; color: #334155; font-weight: 700; }
        .matrix-table td.label-col { background: #f1f5f9; font-weight: 700; color: #334155; text-align: left; padding-left: 8px; width: 80px; }

        /* Status Colors matching screenshot */
        .st-P { background: #dcfce7; color: #166534; font-weight: 700; }
        .st-PL { background: #fef9c3; color: #854d0e; font-weight: 700; }
        .st-WO { background: #f1f5f9; color: #475569; font-weight: 700; }
        .st-A { background: #ffe4e6; color: #9f1239; font-weight: 700; }
        .st-H { background: #dbeafe; color: #1e40af; font-weight: 700; }
        .st-WOP { background: #e0e7ff; color: #3730a3; font-weight: 700; }
        .st-HOP { background: #f3e8ff; color: #6b21a8; font-weight: 700; }

        @media print {
            body { padding: 0; background: #fff; }
            .no-print { display: none !important; }
            .emp-card { box-shadow: none; border-color: #94a3b8; }
        }
    </style>
</head>
<body>

    <div class="no-print" style="margin-bottom:16px;display:flex;justify-content:space-between;align-items:center;background:#fff;padding:12px 18px;border-radius:10px;border:1px solid #e2e8f0">
        <a href="{{ route('reports.index') }}" style="color:#2563eb;text-decoration:none;font-weight:700">← Back to Reports</a>
        <div>
            <button onclick="window.print()" style="background:#0f172a;color:#fff;border:0;padding:8px 16px;border-radius:6px;font-weight:700;cursor:pointer">🖨️ Print / Save PDF</button>
        </div>
    </div>

    <div class="report-header">
        <h1>{{ strtoupper($companyName) }}</h1>
        <div class="sub-right">
            <div class="title">Detailed Attendance Report</div>
            <div class="date">{{ $from->format('F, Y') }}</div>
        </div>
    </div>

    @foreach($employees as $emp)
        @php
            $monthDays = $from->daysInMonth;
            $monthSummary = $attendance->monthSummary($emp, $from->year, $from->month);

            $pCount = 0; $hCount = 0; $woCount = 0; $hdCount = 0; $aCount = 0;
            $totalSecs = 0; $shortSecs = 0; $otSecs = 0;

            $dailyData = [];
            for ($d = 1; $d <= $monthDays; $d++) {
                $cDate = \Carbon\Carbon::create($from->year, $from->month, $d);
                $dStr = $cDate->toDateString();
                $st = $attendance->dayStatus($emp, $cDate);

                $punchIn = \App\Models\AttendancePunch::where('user_id', $emp->id)->whereDate('work_date', $dStr)->where('type', 'in')->orderBy('punched_at')->first();
                $punchOut = \App\Models\AttendancePunch::where('user_id', $emp->id)->whereDate('work_date', $dStr)->where('type', 'out')->orderByDesc('punched_at')->first();

                $inTime = $punchIn ? \Carbon\Carbon::parse($punchIn->punched_at)->format('H:i') : '-';
                $outTime = $punchOut ? \Carbon\Carbon::parse($punchOut->punched_at)->format('H:i') : '-';

                $shiftStart = $emp->shift ? \Carbon\Carbon::parse($emp->shift->start_time)->format('H:i') : '09:45';
                $shiftEnd = $emp->shift ? \Carbon\Carbon::parse($emp->shift->end_time)->format('H:i') : '20:30';

                $workingStr = '-';
                $shortStr = '-';
                $otStr = '-';
                $stLabel = 'A';
                $stClass = 'st-A';

                if ($st === 'present' || $st === 'late') {
                    $stLabel = $st === 'late' ? 'P(L)' : 'P';
                    $stClass = $st === 'late' ? 'st-PL' : 'st-P';
                    $pCount++;

                    if ($punchIn && $punchOut) {
                        $secs = \Carbon\Carbon::parse($punchIn->punched_at)->diffInSeconds(\Carbon\Carbon::parse($punchOut->punched_at));
                        $totalSecs += $secs;
                        $hours = floor($secs / 3600);
                        $mins = floor(($secs % 3600) / 60);
                        $workingStr = sprintf('%02d:%02d', $hours, $mins);
                    }
                } elseif ($st === 'leave') {
                    $stLabel = 'HD';
                    $stClass = 'st-PL';
                    $hdCount++;
                } elseif ($cDate->format('l') === ($emp->week_off_day ?: 'Sunday')) {
                    if ($punchIn) {
                        $stLabel = 'WOP';
                        $stClass = 'st-WOP';
                        $pCount++;
                    } else {
                        $stLabel = 'WO';
                        $stClass = 'st-WO';
                        $woCount++;
                    }
                } elseif ($st === 'holiday') {
                    if ($punchIn) {
                        $stLabel = 'HOP';
                        $stClass = 'st-HOP';
                        $pCount++;
                    } else {
                        $stLabel = 'H';
                        $stClass = 'st-H';
                        $hCount++;
                    }
                } else {
                    $stLabel = 'A';
                    $stClass = 'st-A';
                    $aCount++;
                }

                $dailyData[$d] = [
                    'in' => $inTime,
                    'out' => $outTime,
                    'shift_from' => $shiftStart,
                    'shift_to' => $shiftEnd,
                    'working' => $workingStr,
                    'short' => $shortStr,
                    'ot' => $otStr,
                    'status_label' => $stLabel,
                    'status_class' => $stClass,
                ];
            }

            $paidDays = $pCount + $hCount + $woCount;
            $workHrsStr = sprintf('%02d:%02d', floor($totalSecs / 3600), floor(($totalSecs % 3600) / 60));
        @endphp

        <div class="emp-card">
            <div class="emp-head">
                <div class="left">EmpCode: {{ $emp->employee_code ?: 'N/A' }} | Name: {{ strtoupper($emp->displayName()) }}</div>
                <div class="right">Dept: {{ $emp->department ?: ($emp->departmentObj->name ?? 'N/A') }} | Desig: {{ $emp->designation ?: 'N/A' }}</div>
            </div>

            <!-- Summary Bar Table -->
            <table class="summary-table">
                <thead>
                    <tr>
                        <th>Present</th>
                        <th>Holiday</th>
                        <th>WeekOff</th>
                        <th>Halfday</th>
                        <th>Absent</th>
                        <th>PaidDay</th>
                        <th>WorkHrs</th>
                        <th>ShortHrs</th>
                        <th>OT Hrs</th>
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td>{{ $pCount }}</td>
                        <td>{{ $hCount }}</td>
                        <td>{{ $woCount }}</td>
                        <td>{{ $hdCount }}</td>
                        <td>{{ $aCount }}</td>
                        <td>{{ $paidDays }}</td>
                        <td>{{ $workHrsStr }}</td>
                        <td>00:00</td>
                        <td>00:00</td>
                    </tr>
                </tbody>
            </table>

            <!-- 31 Day Matrix Table -->
            <table class="matrix-table">
                <thead>
                    <tr>
                        <th class="label-col">Label</th>
                        @for($d = 1; $d <= $monthDays; $d++)
                            <th>{{ $d }}</th>
                        @endfor
                    </tr>
                </thead>
                <tbody>
                    <tr>
                        <td class="label-col">IN Time</td>
                        @for($d = 1; $d <= $monthDays; $d++)
                            <td>{{ $dailyData[$d]['in'] }}</td>
                        @endfor
                    </tr>
                    <tr>
                        <td class="label-col">OUT Time</td>
                        @for($d = 1; $d <= $monthDays; $d++)
                            <td>{{ $dailyData[$d]['out'] }}</td>
                        @endfor
                    </tr>
                    <tr>
                        <td class="label-col">Shift From</td>
                        @for($d = 1; $d <= $monthDays; $d++)
                            <td>{{ $dailyData[$d]['shift_from'] }}</td>
                        @endfor
                    </tr>
                    <tr>
                        <td class="label-col">Shift To</td>
                        @for($d = 1; $d <= $monthDays; $d++)
                            <td>{{ $dailyData[$d]['shift_to'] }}</td>
                        @endfor
                    </tr>
                    <tr>
                        <td class="label-col">Working</td>
                        @for($d = 1; $d <= $monthDays; $d++)
                            <td>{{ $dailyData[$d]['working'] }}</td>
                        @endfor
                    </tr>
                    <tr>
                        <td class="label-col">Short hrs.</td>
                        @for($d = 1; $d <= $monthDays; $d++)
                            <td>{{ $dailyData[$d]['short'] }}</td>
                        @endfor
                    </tr>
                    <tr>
                        <td class="label-col">O.Times</td>
                        @for($d = 1; $d <= $monthDays; $d++)
                            <td>{{ $dailyData[$d]['ot'] }}</td>
                        @endfor
                    </tr>
                    <tr>
                        <td class="label-col">Status</td>
                        @for($d = 1; $d <= $monthDays; $d++)
                            <td class="{{ $dailyData[$d]['status_class'] }}">{{ $dailyData[$d]['status_label'] }}</td>
                        @endfor
                    </tr>
                </tbody>
            </table>
        </div>
    @endforeach

</body>
</html>
