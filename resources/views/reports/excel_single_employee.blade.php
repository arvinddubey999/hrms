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
    </style>
</head>
<body>
    <table>
        <tr>
            <td colspan="9" class="company-header">{{ $companyName }}</td>
        </tr>
        <tr>
            <td colspan="9" class="sub-header">Individual Monthly Attendance Sheet: {{ $emp->displayName() }} ({{ $from->format('F Y') }})</td>
        </tr>
        <tr><td colspan="9"></td></tr>
        <tr>
            <td colspan="2"><b>Employee Code:</b> {{ $emp->employee_code ?: 'N/A' }}</td>
            <td colspan="3"><b>Designation:</b> {{ $emp->designation ?: '-' }}</td>
            <td colspan="4"><b>Department:</b> {{ $emp->department ?: '-' }}</td>
        </tr>
        <tr><td colspan="9"></td></tr>
        <tr class="col-header">
            <td>DATE</td>
            <td>DAY</td>
            <td>STATUS</td>
            <td>FIRST IN</td>
            <td>LAST OUT</td>
            <td>PUNCH COUNT</td>
            <td>WORKING HOURS</td>
            <td>LATE</td>
            <td>EARLY</td>
        </tr>
        @php $cursor = $from->copy(); @endphp
        @while($cursor->lte($to))
            @php
                $row = collect($summary['rows'])->firstWhere('date', $cursor->toDateString());
                $in = $row ? $row['ins']->first() : null;
                $out = $row ? $row['outs']->last() : null;
                $st = $row ? $row['status'] : 'absent';
                $cnt = $row ? (count($row['ins']) + count($row['outs'])) : 0;
            @endphp
            <tr>
                <td style="text-align:center">{{ $cursor->format('d-m-Y') }}</td>
                <td style="text-align:center">{{ $cursor->format('D') }}</td>
                <td style="text-align:center"><b>{{ ucfirst(str_replace('_',' ',$st)) }}</b></td>
                <td style="text-align:center">{{ $in ? $in->punched_at->format('h:i A') : '-' }}</td>
                <td style="text-align:center">{{ $out ? $out->punched_at->format('h:i A') : '-' }}</td>
                <td style="text-align:center">{{ $cnt }}</td>
                <td style="text-align:center">{{ $row['hours'] ?? '00:00 hrs' }}</td>
                <td style="text-align:center">{{ $st==='late'?'Yes':'-' }}</td>
                <td style="text-align:center">-</td>
            </tr>
            @php $cursor->addDay(); @endphp
        @endwhile
    </table>
</body>
</html>
