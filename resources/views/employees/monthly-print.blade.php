<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Monthly Attendance - {{ $staff->displayName() }}</title>
    <style>
        body { font-family: sans-serif; font-size: 11px; margin: 20px; color: #111; }
        .no-print { margin-bottom: 12px; text-align: right; }
        .banner-top { background: #374151; color: #ffffff; padding: 12px 18px; font-size: 16px; font-weight: bold; border-radius: 4px 4px 0 0; }
        .banner-sub { background: #4b5563; color: #ffffff; padding: 10px 18px; font-size: 13px; font-weight: bold; margin-bottom: 15px; border-radius: 0 0 4px 4px; }
        table { width: 100%; border-collapse: collapse; margin-bottom: 20px; font-size: 11px; }
        th, td { border: 1px solid #d1d5db; padding: 6px 8px; text-align: center; }
        th { background: #374151; color: #ffffff; font-weight: bold; text-transform: uppercase; font-size: 10px; }
        .stat-present { color: #16a34a; font-weight: bold; }
        .stat-absent { color: #dc2626; font-weight: bold; }
        .summary-title { font-size: 13px; font-weight: bold; text-transform: uppercase; color: #111827; margin: 20px 0 8px 0; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
    <div class="no-print">
        <button onclick="window.print()" style="padding:8px 16px;font-weight:bold;cursor:pointer;background:#1f2937;color:#fff;border:0;border-radius:6px">Print / Save as PDF</button>
    </div>

    <div class="banner-top">
        MONTHLY ATTENDANCE <span style="float:right">Date: {{ $month }}/{{ $year }}</span>
    </div>
    <div class="banner-sub">
        EMP NAME : {{ $staff->displayName() }} &nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp;&nbsp; ENG MOB : {{ $staff->phone }}
    </div>

    <table>
        <thead>
            <tr>
                <th>DATE</th>
                <th>STAT</th>
                <th>IN</th>
                <th style="width:30%">IN LOC</th>
                <th>OUT</th>
                <th style="width:25%">OUT LOC</th>
                <th>TOTAL HOURS</th>
                <th>EARLY</th>
                <th>LATE</th>
            </tr>
        </thead>
        <tbody>
            @foreach($summary['rows'] as $row)
                @php $in = $row['ins']->first(); $out = $row['outs']->last(); @endphp
                <tr>
                    <td>{{ \Carbon\Carbon::parse($row['date'])->format('d-m-Y') }}</td>
                    <td class="{{ $row['status']==='present'||$row['status']==='late'?'stat-present':'stat-absent' }}">
                        {{ $row['status']==='present'||$row['status']==='late'?'Present':($row['status']==='absent'?'Absent':ucfirst(str_replace('_',' ',$row['status']))) }}
                    </td>
                    <td>{{ $in ? $in->punched_at->format('g:i A') : '-' }}</td>
                    <td style="font-size:10px;text-align:left">{{ $in ? $in->location_text : '-' }}</td>
                    <td>{{ $out ? $out->punched_at->format('g:i A') : '-' }}</td>
                    <td style="font-size:10px;text-align:left">{{ $out ? $out->location_text : '-' }}</td>
                    <td>{{ $row['hours'] }}</td>
                    <td>-</td>
                    <td>{{ $row['status']==='late'?'Late':'-' }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>

    <div class="summary-title">ATTENDANCE SUMMARY</div>
    <table>
        <thead>
            <tr>
                <th>PRESENT</th>
                <th>ABSENT</th>
                <th>HALF DAY</th>
                <th>LEAVE</th>
                <th>HOLIDAY</th>
                <th>WEEK OFF</th>
                <th>WO PRESENT</th>
                <th>HO PRESENT</th>
                <th>LATE</th>
                <th>EARLY</th>
                <th>TOTAL HOURS</th>
            </tr>
        </thead>
        <tbody>
            <tr>
                <td style="background:#bbf7d0;color:#166534;font-weight:bold">{{ $summary['present'] }}</td>
                <td style="background:#fca5a5;color:#991b1b;font-weight:bold">{{ $summary['absent'] }}</td>
                <td style="background:#fef08a;color:#854d0e;font-weight:bold">{{ $summary['half'] }}</td>
                <td style="background:#fed7aa;color:#9a3412;font-weight:bold">{{ $summary['leave'] }}</td>
                <td style="background:#ccfbf1;color:#0f766e;font-weight:bold">{{ $summary['holiday'] }}</td>
                <td style="background:#e2e8f0;color:#334155;font-weight:bold">{{ $summary['weekOff'] }}</td>
                <td style="background:#bae6fd;color:#0369a1;font-weight:bold">{{ $summary['wop'] }}</td>
                <td style="background:#e9d5ff;color:#6b21a8;font-weight:bold">0</td>
                <td style="background:#fca5a5;color:#991b1b;font-weight:bold">{{ $summary['late'] }}</td>
                <td style="background:#fed7aa;color:#9a3412;font-weight:bold">0</td>
                <td style="background:#bae6fd;color:#0369a1;font-weight:bold">
                    @php
                        $totMinutes = 0;
                        foreach($summary['rows'] as $r) {
                            $parts = explode(':', $r['hours']);
                            if(count($parts)==2) {
                                $totMinutes += ((int)$parts[0])*60 + (int)$parts[1];
                            }
                        }
                    @endphp
                    {{ sprintf('%02d:%02d', intdiv($totMinutes, 60), $totMinutes % 60) }}
                </td>
            </tr>
        </tbody>
    </table>
</body>
</html>
