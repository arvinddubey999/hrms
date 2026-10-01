<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>Monthly Roster Report</title>
    <style>
        body { font-family: sans-serif; font-size: 10px; margin: 10px; }
        h1 { text-align: center; font-size: 16px; margin-bottom: 5px; }
        .table { width: 100%; border-collapse: collapse; font-size: 8px; }
        .table th, .table td { border: 1px solid #999; padding: 2px; text-align: center; }
        .table th { background: #f3f4f6; }
        .present { background: #dcfce7; color: #166534; font-weight: bold; }
        .absent { background: #fee2e2; color: #991b1b; font-weight: bold; }
        .week_off { background: #f3f4f6; color: #4b5563; }
        .leave { background: #ffedd5; color: #9a3412; }
        @media print { .no-print { display: none; } }
    </style>
</head>
<body>
    <div class="no-print" style="margin-bottom:10px;text-align:right">
        <button onclick="window.print()">Print / Download PDF</button>
    </div>
    <h1>Monthly Roster Report — {{ date('F Y', mktime(0,0,0,$month,1,$year)) }}</h1>

    <table class="table">
        <thead>
            <tr>
                <th style="text-align:left;width:120px">Employee</th>
                @for($d=1;$d<=$days;$d++)
                    <th>{{ $d }}</th>
                @endfor
            </tr>
        </thead>
        <tbody>
            @foreach($employees as $user)
                @php $sum = $attendance->monthSummary($user,$year,$month); @endphp
                <tr>
                    <td style="text-align:left"><b>{{ $user->displayName() }}</b></td>
                    @foreach($sum['rows'] as $row)
                        @php
                            $st = $row['status'];
                            $code = match($st) {
                                'present' => 'P',
                                'late' => 'P',
                                'wop' => 'WOP',
                                'absent' => 'A',
                                'leave' => 'L',
                                'week_off' => 'WO',
                                'holiday' => 'H',
                                default => '-'
                            };
                        @endphp
                        <td class="{{ $st }}">{{ $code }}</td>
                    @endforeach
                </tr>
            @endforeach
        </tbody>
    </table>
</body>
</html>
