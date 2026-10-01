<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Report - {{ $companyName }}</title>
    <style>
        body { font-family: sans-serif; font-size: 11px; margin: 20px; color: #111827; }
        h1, h2 { text-align: center; margin: 4px 0; }
        .company-title { font-size: 18px; font-weight: bold; color: #111827; }
        .report-title { font-size: 14px; font-weight: bold; color: #4b5563; text-transform: uppercase; margin-bottom: 4px; }
        .table { width: 100%; border-collapse: collapse; margin-top: 15px; }
        .table th, .table td { border: 1px solid #d1d5db; padding: 6px 8px; text-align: left; }
        .table th { background: #fef08a; font-size: 10px; font-weight: bold; text-transform: uppercase; text-align: center; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom:12px;text-align:right">
        <button onclick="window.print()" style="padding:6px 12px;font-weight:bold;cursor:pointer">Print / Save as PDF</button>
    </div>

    <h2 class="company-title">{{ $companyName }}</h2>
    <h1 class="report-title">{{ str_replace('_', ' ', strtoupper($type)) }} REPORT</h1>
    <p style="text-align:center;color:#6b7280;margin-top:0">Period: {{ $from->format('d M Y') }} to {{ $to->format('d M Y') }}</p>

    <table class="table">
        <thead>
            @if($type === 'tasks')
                <tr>
                    <th>Task ID</th>
                    <th>Title</th>
                    <th>Department</th>
                    <th>Assigned To</th>
                    <th>Priority</th>
                    <th>Status</th>
                    <th>Due Date</th>
                </tr>
            @elseif($type === 'overall')
                <tr>
                    <th>Emp Code</th>
                    <th>Employee Name</th>
                    <th>Company</th>
                    <th>Department</th>
                    <th>Present</th>
                    <th>Absent</th>
                    <th>Leave</th>
                    <th>Week Off</th>
                    <th>Late</th>
                    <th>Payable Days</th>
                </tr>
            @elseif(str_starts_with($type, 'master_'))
                <tr>
                    <th>Emp Code</th>
                    <th>Name</th>
                    <th>Designation</th>
                    <th>Category</th>
                    <th>Department</th>
                    <th>Phone</th>
                    <th>Status</th>
                </tr>
            @else
                <tr>
                    <th>Date</th>
                    <th>Emp Code</th>
                    <th>Employee Name</th>
                    <th>Department</th>
                    <th>Status</th>
                    <th>IN Time</th>
                    <th>OUT Time</th>
                    <th>Hours</th>
                </tr>
            @endif
        </thead>
        <tbody>
            @if($type === 'tasks')
                @foreach($tasks as $t)
                    <tr>
                        <td style="text-align:center">#{{ $t->id }}</td>
                        <td><b>{{ $t->title }}</b></td>
                        <td>{{ $t->department->name ?? 'General' }}</td>
                        <td>{{ $t->assignee?->displayName() ?? 'Unassigned' }}</td>
                        <td style="text-align:center">{{ strtoupper($t->priority) }}</td>
                        <td style="text-align:center"><b>{{ strtoupper($t->status) }}</b></td>
                        <td style="text-align:center">{{ optional($t->due_date)->format('d-m-Y') ?: '-' }}</td>
                    </tr>
                @endforeach
            @elseif($type === 'overall')
                @foreach($employees as $user)
                    @php $sum = $attendance->monthSummary($user, $from->year, $from->month); @endphp
                    <tr>
                        <td style="text-align:center">{{ $user->employee_code ?: 'N/A' }}</td>
                        <td><b>{{ $user->displayName() }}</b></td>
                        <td>{{ $user->company->name ?? '-' }}</td>
                        <td>{{ $user->department ?: '-' }}</td>
                        <td style="text-align:center">{{ $sum['present'] }}</td>
                        <td style="text-align:center">{{ $sum['absent'] }}</td>
                        <td style="text-align:center">{{ $sum['leave'] }}</td>
                        <td style="text-align:center">{{ $sum['weekOff'] }}</td>
                        <td style="text-align:center">{{ $sum['late'] }}</td>
                        <td style="text-align:center"><b>{{ $sum['payable'] }}</b></td>
                    </tr>
                @endforeach
            @elseif(str_starts_with($type, 'master_'))
                @foreach($employees as $user)
                    <tr>
                        <td style="text-align:center">{{ $user->employee_code ?: 'N/A' }}</td>
                        <td><b>{{ $user->displayName() }}</b></td>
                        <td>{{ $user->designation ?: '-' }}</td>
                        <td>{{ $user->category->name ?? '-' }}</td>
                        <td>{{ $user->department ?: '-' }}</td>
                        <td>{{ $user->phone }}</td>
                        <td style="text-align:center">{{ strtoupper($user->status) }}</td>
                    </tr>
                @endforeach
            @else
                @foreach($employees as $user)
                    @php $cursor = $from->copy(); @endphp
                    @while($cursor->lte($to))
                        @php
                            $sum = $attendance->monthSummary($user, $cursor->year, $cursor->month);
                            $row = collect($sum['rows'])->firstWhere('date', $cursor->toDateString());
                            $in = $row ? $row['ins']->first() : null;
                            $out = $row ? $row['outs']->last() : null;
                        @endphp
                        @if($row)
                            <tr>
                                <td style="text-align:center">{{ $cursor->format('d-m-Y') }}</td>
                                <td style="text-align:center">{{ $user->employee_code ?: 'N/A' }}</td>
                                <td><b>{{ $user->displayName() }}</b></td>
                                <td>{{ $user->department ?: '-' }}</td>
                                <td style="text-align:center"><b>{{ strtoupper($row['status']) }}</b></td>
                                <td style="text-align:center">{{ $in ? $in->punched_at->format('h:i A') : '-' }}</td>
                                <td style="text-align:center">{{ $out ? $out->punched_at->format('h:i A') : '-' }}</td>
                                <td style="text-align:center">{{ $row['hours'] }}</td>
                            </tr>
                        @endif
                        @php $cursor->addDay(); @endphp
                    @endwhile
                @endforeach
            @endif
        </tbody>
    </table>
</body>
</html>
