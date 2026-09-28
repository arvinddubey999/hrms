<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Monthly Attendance</title>
    <style>
        body{font-family:Arial,sans-serif;padding:24px}
        table{width:100%;border-collapse:collapse;font-size:12px}
        th,td{border:1px solid #333;padding:6px}
        th{background:#1e3a5f;color:#fff}
        .head{background:#1e3a5f;color:#fff;padding:12px}
        .sub{background:#5b6b7a;color:#fff;padding:8px}
        .present{color:green;font-weight:700}
        .absent{color:red;font-weight:700}
    </style>
</head>
<body>
<div class="head">MONTHLY ATTENDANCE <span style="float:right">Date: {{ $month }}/{{ $year }}</span></div>
<div class="sub">EMP NAME : {{ $staff->displayName() }} &nbsp; ENG MOB : {{ $staff->phone }}</div>
<table>
    <tr><th>DATE</th><th>STAT</th><th>IN</th><th>IN LOC</th><th>OUT</th><th>OUT LOC</th><th>TOTAL HOURS</th><th>EARLY</th><th>LATE</th></tr>
    @foreach($summary['rows'] as $row)
        @php $in=$row['ins']->first(); $out=$row['outs']->last(); @endphp
        <tr>
            <td>{{ \Carbon\Carbon::parse($row['date'])->format('d-m-Y') }}</td>
            <td class="{{ $row['status'] }}">{{ $row['status']==='present'||$row['status']==='late'?'Present':($row['status']==='absent'?'Ab':str_replace('_',' ',ucfirst($row['status']))) }}</td>
            <td>{{ $in?->punched_at?->format('g:i A') }}</td>
            <td>{{ $in?->location_text }}</td>
            <td>{{ $out?->punched_at?->format('g:i A') }}</td>
            <td>{{ $out?->location_text }}</td>
            <td>{{ $row['hours'] }}</td>
            <td></td><td>{{ $row['status']==='late'?'Late':'' }}</td>
        </tr>
    @endforeach
</table>
<h3>ATTENDANCE SUMMARY</h3>
<table>
    <tr><th>PRESENT</th><th>ABSENT</th><th>HALF DAY</th><th>LEAVE</th><th>HOLIDAY</th><th>WEEK OFF</th><th>LATE</th></tr>
    <tr>
        <td style="background:#dcfce7">{{ $summary['present'] }}</td>
        <td style="background:#fecaca">{{ $summary['absent'] }}</td>
        <td style="background:#fde68a">{{ $summary['half'] }}</td>
        <td style="background:#fed7aa">{{ $summary['leave'] }}</td>
        <td>{{ $summary['holiday'] }}</td>
        <td style="background:#e5e7eb">{{ $summary['weekOff'] }}</td>
        <td style="background:#fecaca">{{ $summary['late'] }}</td>
    </tr>
</table>
</body>
</html>
