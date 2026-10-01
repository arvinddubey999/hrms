<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Monthly Attendance Sheet - {{ $emp->displayName() }}</title>
    <style>
        body { font-family: sans-serif; font-size: 11px; margin: 20px; color: #111; }
        h1, h2 { text-align: center; margin: 4px 0; }
        .table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .table th, .table td { border: 1px solid #ccc; padding: 5px 8px; text-align: left; }
        .table th { background: #fef08a; text-transform: uppercase; font-size: 10px; text-align: center; }
    </style>
</head>
<body>
    <h2>{{ $companyName }}</h2>
    <h1>MONTHLY ATTENDANCE SHEET: {{ strtoupper($emp->displayName()) }}</h1>
    <p style="text-align:center;margin-bottom:15px">Month: {{ $from->format('F Y') }} | Emp Code: {{ $emp->employee_code ?: 'N/A' }} | Dept: {{ $emp->department ?: '-' }}</p>

    <table class="table">
        <thead>
            <tr>
                <th>Date</th>
                <th>Day</th>
                <th>Status</th>
                <th>First IN</th>
                <th>Last OUT</th>
                <th>Total Hours</th>
            </tr>
        </thead>
        <tbody>
            @php $cursor = $from->copy(); @endphp
            @while($cursor->lte($to))
                @php
                    $row = collect($summary['rows'])->firstWhere('date', $cursor->toDateString());
                    $in = $row ? $row['ins']->first() : null;
                    $out = $row ? $row['outs']->last() : null;
                    $st = $row ? $row['status'] : 'absent';
                @endphp
                <tr>
                    <td style="text-align:center">{{ $cursor->format('d-m-Y') }}</td>
                    <td style="text-align:center">{{ $cursor->format('D') }}</td>
                    <td style="text-align:center"><b>{{ ucfirst(str_replace('_',' ',$st)) }}</b></td>
                    <td style="text-align:center">{{ $in ? $in->punched_at->format('h:i A') : '-' }}</td>
                    <td style="text-align:center">{{ $out ? $out->punched_at->format('h:i A') : '-' }}</td>
                    <td style="text-align:center">{{ $row['hours'] ?? '00:00 hrs' }}</td>
                </tr>
                @php $cursor->addDay(); @endphp
            @endwhile
        </tbody>
    </table>
</body>
</html>
