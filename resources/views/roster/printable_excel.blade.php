<html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:x="urn:schemas-microsoft-com:office:excel" xmlns="http://www.w3.org/TR/REC-html40">
<head>
    <meta http-equiv="Content-Type" content="text/html; charset=utf-8" />
    @php echo "<!--[if gte mso 9]><xml><x:ExcelWorkbook><x:ExcelWorksheets><x:ExcelWorksheet><x:Name>Monthly Roster</x:Name><x:WorksheetOptions><x:DisplayGridlines/><x:Print><x:ValidPrinterInfo/><x:Orientation>Landscape</x:Orientation></x:Print></x:WorksheetOptions></x:ExcelWorksheet></x:ExcelWorksheets></x:ExcelWorkbook></xml><![endif]-->"; @endphp
    <style>
        table { border-collapse: collapse; font-family: Calibri, Arial, sans-serif; font-size: 11px; }
        th, td { border: 0.5pt solid #cbd5e1; padding: 6px 8px; text-align: center; vertical-align: middle; }
        .head-title { font-size: 16px; font-weight: bold; background: #f8fafc; color: #0f172a; text-align: left; }
        .subhead { font-size: 11px; color: #64748b; background: #f8fafc; text-align: left; }
        .th-main { background: #1e293b; color: #ffffff; font-weight: bold; font-size: 11px; }
        .th-date { background: #334155; color: #ffffff; font-weight: bold; font-size: 10px; }
        .th-tot { background: #0f172a; color: #ffffff; font-weight: bold; font-size: 11px; }
        .emp-name { text-align: left; font-weight: bold; color: #0f172a; }
        .emp-meta { text-align: left; font-size: 10px; color: #475569; }
        
        .p-pres { background: #dcfce7; color: #166534; font-weight: bold; }
        .p-late { background: #fef3c7; color: #92400e; font-weight: bold; }
        .p-wop { background: #dbeafe; color: #1e40af; font-weight: bold; }
        .p-hop { background: #fbcfe8; color: #9d174d; font-weight: bold; }
        .p-abs { background: #fee2e2; color: #991b1b; font-weight: bold; }
        .p-leave { background: #ffedd5; color: #9a3412; font-weight: bold; }
        .p-wo { background: #f3f4f6; color: #4b5563; font-weight: 600; }
        .p-hol { background: #fef08a; color: #854d0e; font-weight: bold; }
        .p-fut { background: #ffffff; color: #94a3b8; }

        .tot-p { background: #e0f2fe; color: #0369a1; font-weight: bold; }
        .tot-a { background: #fee2e2; color: #dc2626; font-weight: bold; }
        .tot-h { background: #fef08a; color: #854d0e; font-weight: bold; }
        .tot-wo { background: #f3f4f6; color: #4b5563; font-weight: bold; }
        .tot-wop { background: #dbeafe; color: #1e40af; font-weight: bold; }
        .tot-hop { background: #fbcfe8; color: #9d174d; font-weight: bold; }
        .tot-pay { background: #dcfce7; color: #166534; font-weight: bold; font-size: 12px; }
    </style>
</head>
<body>
    @php
        $totalCols = count($dateRange) + 12;
        $periodText = ($fromDate ? date('d-m-Y', strtotime($fromDate)) : date('01-m-Y', mktime(0,0,0,$month,1,$year))) . ' to ' . ($toDate ? date('d-m-Y', strtotime($toDate)) : date('t-m-Y', mktime(0,0,0,$month,1,$year)));
    @endphp

    <table>
        <tr>
            <td colspan="{{ $totalCols }}" class="head-title">MONTHLY ROSTER REPORT — {{ date('F Y', mktime(0,0,0,$month,1,$year)) }}</td>
        </tr>
        <tr>
            <td colspan="{{ $totalCols }}" class="subhead">Period: {{ $periodText }} | Generated on: {{ now()->format('d-m-Y h:i A') }}</td>
        </tr>
        <tr>
            <td colspan="{{ $totalCols }}" style="text-align:left;background:#f1f5f9;font-size:10px;padding:4px">
                <b>LEGEND:</b>
                <span class="p-pres">&nbsp;P: Present&nbsp;</span> |
                <span class="p-late">&nbsp;P(L): Late&nbsp;</span> |
                <span class="p-abs">&nbsp;A: Absent&nbsp;</span> |
                <span class="p-wo">&nbsp;WO: Week Off&nbsp;</span> |
                <span class="p-wop">&nbsp;WO-P: Week Off Present&nbsp;</span> |
                <span class="p-hol">&nbsp;H: Holiday&nbsp;</span> |
                <span class="p-hop">&nbsp;H-P: Holiday Present&nbsp;</span> |
                <span class="p-leave">&nbsp;L: Leave&nbsp;</span>
            </td>
        </tr>
        <tr></tr>

        <thead>
            <tr>
                <th class="th-main" rowspan="2">Sr. No.</th>
                <th class="th-main" rowspan="2">Employee Code</th>
                <th class="th-main" rowspan="2">Employee Name</th>
                <th class="th-main" rowspan="2">Category</th>
                <th class="th-main" rowspan="2">Department</th>
                <th class="th-main" rowspan="2">Shift Time</th>
                @foreach($dateRange as $dt)
                    <th class="th-date">
                        {{ $dt->format('d') }}<br>
                        <small>{{ strtoupper($dt->format('D')) }}</small>
                    </th>
                @endforeach
                <th class="th-tot" style="background:#0284c7" rowspan="2">P</th>
                <th class="th-tot" style="background:#dc2626" rowspan="2">A</th>
                <th class="th-tot" style="background:#d97706" rowspan="2">H</th>
                <th class="th-tot" style="background:#64748b" rowspan="2">WO</th>
                <th class="th-tot" style="background:#4338ca" rowspan="2">WO-P</th>
                <th class="th-tot" style="background:#9333ea" rowspan="2">H-P</th>
                <th class="th-tot" style="background:#16a34a" rowspan="2">TOTAL PAYABLE</th>
            </tr>
            <tr>
                @foreach($dateRange as $dt)
                    <th style="background:#475569;color:#fff;font-size:9px">{{ $dt->format('m/y') }}</th>
                @endforeach
            </tr>
        </thead>
        <tbody>
            @foreach($employees as $idx => $user)
                @php 
                    $sum = $attendance->monthSummary($user, $year, $month, $dateRange);
                    $shiftText = $user->shift ? date('h:i A', strtotime($user->shift->start_time)) . ' - ' . date('h:i A', strtotime($user->shift->end_time)) : 'Not Assigned';
                @endphp
                <tr>
                    <td>{{ $idx + 1 }}</td>
                    <td style="font-weight:600;color:#0284c7">{{ $user->employee_code ?: '-' }}</td>
                    <td class="emp-name">{{ $user->displayName() }}</td>
                    <td class="emp-meta">{{ $user->category?->name ?? '-' }}</td>
                    <td class="emp-meta">{{ $user->department ?: '-' }}</td>
                    <td class="emp-meta">{{ $shiftText }}</td>

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
                                'present' => 'p-pres',
                                'late' => 'p-late',
                                'wop' => 'p-wop',
                                'hop' => 'p-hop',
                                'absent' => 'p-abs',
                                'leave' => 'p-leave',
                                'week_off' => 'p-wo',
                                'holiday' => 'p-hol',
                                default => 'p-fut'
                            };

                            $inPunch = $row['ins']->first();
                            $outPunch = $row['outs']->last();
                            $timeStr = '';
                            if ($inPunch) $timeStr .= 'IN:' . $inPunch->punched_at->format('h:i A') . ' ';
                            if ($outPunch) $timeStr .= 'OUT:' . $outPunch->punched_at->format('h:i A');
                        @endphp
                        <td class="{{ $cls }}">
                            <b>{{ $code }}</b>
                            @if($timeStr)
                                <br><small style="font-size:8.5px;font-weight:normal">{{ trim($timeStr) }}</small>
                            @endif
                        </td>
                    @endforeach

                    @php
                        $cntP = $sum['present'] ?? 0;
                        $cntA = $sum['absent'] ?? 0;
                        $cntH = $sum['holiday'] ?? 0;
                        $cntWO = $sum['weekOff'] ?? 0;
                        $cntWOP = $sum['wop'] ?? 0;
                        $cntHOP = $sum['hop'] ?? 0;
                        $totPayable = $sum['payable'] ?? 0;
                    @endphp
                    <td class="tot-p">{{ $cntP }}</td>
                    <td class="tot-a">{{ $cntA }}</td>
                    <td class="tot-h">{{ $cntH }}</td>
                    <td class="tot-wo">{{ $cntWO }}</td>
                    <td class="tot-wop">{{ $cntWOP }}</td>
                    <td class="tot-hop">{{ $cntHOP }}</td>
                    <td class="tot-pay">{{ $totPayable }}</td>
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
