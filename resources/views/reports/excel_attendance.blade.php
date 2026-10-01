<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <style>
        table { border-collapse: collapse; width: 100%; font-family: Calibri, sans-serif; font-size: 11pt; }
        th, td { border: 1px solid #d4d4d4; padding: 6px 10px; text-align: left; }
        .company-header { font-size: 16pt; font-weight: bold; text-align: center; background: #ffffff; color: #111827; }
        .sub-header { font-size: 12pt; font-style: italic; text-align: center; background: #ffffff; color: #4b5563; }
        .col-header { background: #fef08a; font-weight: bold; text-transform: uppercase; font-size: 10pt; color: #111827; text-align: center; }
        .status-present { background: #bbf7d0; color: #166534; font-weight: bold; text-align: center; }
        .status-absent { background: #fca5a5; color: #991b1b; font-weight: bold; text-align: center; }
        .status-pending { background: #fef08a; color: #854d0e; font-weight: bold; text-align: center; }
        .status-late { background: #fef08a; color: #854d0e; font-weight: bold; text-align: center; }
    </style>
</head>
<body>
    <table>
        <tr>
            <td colspan="13" class="company-header">{{ $companyName }}</td>
        </tr>
        <tr>
            <td colspan="13" class="sub-header">Attendance Report: {{ $from->format('d/m/Y') }} to {{ $to->format('d/m/Y') }}</td>
        </tr>
        <tr><td colspan="13"></td></tr>

        @if($type === 'overall')
            <tr class="col-header">
                <td>EMP ID</td>
                <td>NAME</td>
                <td>DESIGNATION</td>
                <td>DEPARTMENT</td>
                <td>CATEGORY</td>
                <td>PRESENT</td>
                <td>ABSENT</td>
                <td>LEAVE</td>
                <td>WEEK OFF</td>
                <td>LATE</td>
                <td>EARLY</td>
                <td>PAYABLE DAYS</td>
                <td>TOTAL HOURS</td>
            </tr>
            @foreach($employees as $emp)
                @php $sum = $attendance->monthSummary($emp, $from->year, $from->month); @endphp
                <tr>
                    <td style="text-align:center"><code>{{ $emp->employee_code ?: sprintf('%05d', $emp->id) }}</code></td>
                    <td><b>{{ $emp->displayName() }}</b></td>
                    <td>{{ $emp->designation ?: '-' }}</td>
                    <td>{{ $emp->department ?: '-' }}</td>
                    <td>{{ $emp->category->name ?? '-' }}</td>
                    <td style="text-align:center">{{ $sum['present'] }}</td>
                    <td style="text-align:center">{{ $sum['absent'] }}</td>
                    <td style="text-align:center">{{ $sum['leave'] }}</td>
                    <td style="text-align:center">{{ $sum['weekOff'] }}</td>
                    <td style="text-align:center">{{ $sum['late'] }}</td>
                    <td style="text-align:center">-</td>
                    <td style="text-align:center"><b>{{ $sum['payable'] }}</b></td>
                    <td style="text-align:center">
                        @php
                            $totMinutes = 0;
                            foreach($sum['rows'] as $r) {
                                $parts = explode(':', $r['hours']);
                                if(count($parts)==2) $totMinutes += ((int)$parts[0])*60 + (int)$parts[1];
                            }
                        @endphp
                        {{ sprintf('%d hrs %dm', intdiv($totMinutes, 60), $totMinutes % 60) }}
                    </td>
                </tr>
            @endforeach
        @else
            <tr class="col-header">
                <td>DATE</td>
                <td>EMP ID</td>
                <td>NAME</td>
                <td>DESIGNATION</td>
                <td>CATEGORY</td>
                <td>DEPARTMENT</td>
                <td>STATUS</td>
                <td>SHIFT</td>
                <td>IN</td>
                <td>OUT</td>
                <td>TOTAL</td>
                <td>LATE</td>
                <td>EARLY</td>
            </tr>
            @foreach($employees as $emp)
                @php $cursor = $from->copy(); @endphp
                @while($cursor->lte($to))
                    @php
                        $sum = $attendance->monthSummary($emp, $cursor->year, $cursor->month);
                        $row = collect($sum['rows'])->firstWhere('date', $cursor->toDateString());
                        $in = $row ? $row['ins']->first() : null;
                        $out = $row ? $row['outs']->last() : null;
                        $st = $row ? $row['status'] : 'absent';
                        $hasNoOut = $row ? $row['has_in_no_out'] : false;

                        $stDisplay = 'Absent';
                        $stClass = 'status-absent';
                        if ($hasNoOut) {
                            $stDisplay = 'Pending OUT';
                            $stClass = 'status-pending';
                        } elseif ($st === 'present' || $st === 'late' || $st === 'wop') {
                            $stDisplay = 'Present';
                            $stClass = 'status-present';
                        } elseif ($st === 'leave') {
                            $stDisplay = 'Leave';
                        }
                    @endphp
                    @if($row)
                        <tr>
                            <td style="text-align:center">{{ $cursor->format('d/m/Y') }}</td>
                            <td style="text-align:center"><code>{{ $emp->employee_code ?: sprintf('%05d', $emp->id) }}</code></td>
                            <td><b>{{ $emp->displayName() }}</b></td>
                            <td>{{ $emp->designation ?: '-' }}</td>
                            <td>{{ $emp->category->name ?? '-' }}</td>
                            <td>{{ $emp->department ?: '-' }}</td>
                            <td class="{{ $stClass }}">{{ $stDisplay }}</td>
                            <td>{{ $emp->shift->name ?? 'No Shift Found' }}</td>
                            <td style="text-align:center">{{ $in ? $in->punched_at->format('g:i A') : '-' }}</td>
                            <td style="text-align:center">{{ $out ? $out->punched_at->format('g:i A') : '-' }}</td>
                            <td style="text-align:center">
                                @if($row['hours'] && $row['hours'] !== '00:00')
                                    @php
                                        $hParts = explode(':', $row['hours']);
                                        $hStr = isset($hParts[1]) ? ((int)$hParts[0]).'h '.((int)$hParts[1]).'m' : $row['hours'];
                                    @endphp
                                    {{ $hStr }}
                                @else
                                    -
                                @endif
                            </td>
                            <td style="text-align:center">{{ $st==='late'?'Yes':'-' }}</td>
                            <td style="text-align:center">-</td>
                        </tr>
                    @endif
                    @php $cursor->addDay(); @endphp
                @endwhile
            @endforeach
        @endif
    </table>
</body>
</html>
