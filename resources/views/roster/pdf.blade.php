<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Monthly Roster Report — {{ date('F Y', mktime(0,0,0,$month,1,$year)) }}</title>
    <style>
        @page {
            size: landscape;
            margin: 6mm;
        }
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Arial, sans-serif;
            font-size: 8px;
            margin: 0;
            padding: 8px;
            color: #1e293b;
        }
        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 8px;
            border-bottom: 1px solid #cbd5e1;
            padding-bottom: 6px;
        }
        h1 {
            font-size: 14px;
            margin: 0;
            color: #0f172a;
        }
        .subhead {
            font-size: 8.5px;
            color: #64748b;
        }
        .legend {
            display: flex;
            gap: 8px;
            font-size: 7.5px;
            margin-bottom: 8px;
            flex-wrap: wrap;
        }
        .legend-item {
            padding: 2px 5px;
            border-radius: 4px;
            font-weight: 600;
        }
        .table {
            width: 100%;
            border-collapse: collapse;
            font-size: 7.5px;
        }
        .table th, .table td {
            border: 1px solid #cbd5e1;
            padding: 2px 3px;
            text-align: center;
            vertical-align: middle;
        }
        .table th {
            background: #f1f5f9;
            color: #334155;
            font-weight: 700;
        }
        .emp-col {
            text-align: left !important;
            min-width: 130px;
            max-width: 160px;
        }
        .present { background: #dcfce7; color: #166534; font-weight: bold; }
        .late { background: #fef3c7; color: #92400e; font-weight: bold; }
        .wop { background: #dbeafe; color: #1e40af; font-weight: bold; }
        .hop { background: #fbcfe8; color: #9d174d; font-weight: bold; }
        .absent { background: #fee2e2; color: #991b1b; font-weight: bold; }
        .leave { background: #ffedd5; color: #9a3412; font-weight: bold; }
        .week_off { background: #f3f4f6; color: #4b5563; }
        .holiday { background: #fef08a; color: #854d0e; font-weight: bold; }
        .future { background: #ffffff; color: #94a3b8; }
        
        .tot-col {
            font-weight: bold;
            font-size: 8px;
        }
        @media print {
            .no-print { display: none; }
            body { padding: 0; }
        }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom:10px;text-align:right">
        <button onclick="window.print()" style="padding:6px 14px;background:#0284c7;color:#fff;border:none;border-radius:6px;font-weight:bold;cursor:pointer">🖨️ Print / Download PDF</button>
    </div>

    <div class="header">
        <div>
            <h1>Monthly Roster Report</h1>
            <div class="subhead">Period: {{ $fromDate ? date('d-m-Y', strtotime($fromDate)) : date('01-m-Y', mktime(0,0,0,$month,1,$year)) }} to {{ $toDate ? date('d-m-Y', strtotime($toDate)) : date('t-m-Y', mktime(0,0,0,$month,1,$year)) }} | Generated on: {{ now()->format('d-m-Y h:i A') }}</div>
        </div>
        <div class="legend">
            <span class="legend-item present">P: Present</span>
            <span class="legend-item late">P(L): Late</span>
            <span class="legend-item absent">A: Absent</span>
            <span class="legend-item week_off">WO: Week Off</span>
            <span class="legend-item wop">WO-P: Week Off Present</span>
            <span class="legend-item holiday">H: Holiday</span>
            <span class="legend-item hop">H-P: Holiday Present</span>
            <span class="legend-item" style="border:1px dashed #cbd5e1">-: Upcoming</span>
        </div>
    </div>

    <table class="table">
        <thead>
            <tr>
                <th class="emp-col">Employee Details</th>
                @foreach($dateRange as $dt)
                    <th>
                        <div>{{ $dt->format('d') }}</div>
                        <div style="font-weight:normal;font-size:6.5px;color:#64748b">{{ strtoupper($dt->format('D')) }}</div>
                    </th>
                @endforeach
                <th style="background:#e0f2fe;color:#0369a1">P</th>
                <th style="background:#fee2e2;color:#991b1b">A</th>
                <th style="background:#fef08a;color:#854d0e">H</th>
                <th style="background:#f3f4f6;color:#4b5563">WO</th>
                <th style="background:#dbeafe;color:#1e40af">WO-P</th>
                <th style="background:#fbcfe8;color:#9d174d">H-P</th>
                <th style="background:#dcfce7;color:#166534">PAYABLE</th>
            </tr>
        </thead>
        <tbody>
            @foreach($employees as $user)
                @php 
                    $sum = $attendance->monthSummary($user, $year, $month, $dateRange);
                    $shiftText = $user->shift ? date('h:i A', strtotime($user->shift->start_time)) . ' - ' . date('h:i A', strtotime($user->shift->end_time)) : 'Not Assigned';
                @endphp
                <tr>
                    <td class="emp-col">
                        <div style="font-weight:bold;color:#0f172a;font-size:8px">{{ $user->displayName() }}</div>
                        <div style="font-size:6.8px;color:#475569">
                            <span style="font-weight:600;color:#0284c7">{{ $user->employee_code ?: '-' }}</span> | 
                            <span>{{ $user->category?->name ?? '-' }}</span> | 
                            <span>{{ $user->department ?: '-' }}</span>
                        </div>
                        <div style="font-size:6.5px;color:#64748b">
                            Shift: {{ $shiftText }}
                        </div>
                    </td>
                    @foreach($sum['rows'] as $row)
                        @php
                            $st = $row['status'];
                            $code = match($st) {
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
                            $cls = match($st) {
                                'present' => 'present',
                                'late' => 'late',
                                'wop' => 'wop',
                                'hop' => 'hop',
                                'absent' => 'absent',
                                'leave' => 'leave',
                                'week_off' => 'week_off',
                                'holiday' => 'holiday',
                                default => 'future'
                            };
                        @endphp
                        <td class="{{ $cls }}">{{ $code }}</td>
                    @endforeach

                    @php
                        $cntP = $sum['present'] ?? 0;
                        $cntA = $sum['absent'] ?? 0;
                        $cntH = $sum['holiday'] ?? 0;
                        $cntWO = $sum['weekOff'] ?? 0;
                        $cntWOP = $sum['wop'] ?? 0;
                        $cntHOP = $sum['hop'] ?? 0;
                        $totPayable = $sum['payable'] ?? ($cntP + $cntH + $cntWO + $cntWOP + $cntHOP);
                    @endphp
                    <td class="tot-col" style="background:#e0f2fe;color:#0369a1">{{ $cntP }}</td>
                    <td class="tot-col" style="background:#fee2e2;color:#dc2626">{{ $cntA }}</td>
                    <td class="tot-col" style="background:#fef08a;color:#854d0e">{{ $cntH }}</td>
                    <td class="tot-col" style="background:#f3f4f6;color:#4b5563">{{ $cntWO }}</td>
                    <td class="tot-col" style="background:#dbeafe;color:#1e40af">{{ $cntWOP }}</td>
                    <td class="tot-col" style="background:#fbcfe8;color:#9d174d">{{ $cntHOP }}</td>
                    <td class="tot-col" style="background:#dcfce7;color:#166534;font-size:8.5px">{{ $totPayable }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
