@extends('layouts.app')
@section('content')
<h1>Attendance Tracker</h1>
<p class="muted">Report Date From : {{ $from->startOfMonth()->format('d-m-Y') }} To : {{ $from->copy()->endOfMonth()->format('d-m-Y') }}</p>
<p><b>Company Name : {{ \App\Models\Setting::current()->company_name }}</b></p>
@foreach($employees->groupBy(fn($u)=>$u->category->name ?? 'N/A') as $dept => $group)
    <h3>Department : {{ $dept }}</h3>
    <div style="overflow:auto">
    <table class="table">
        <thead>
        <tr>
            <th>Emp</th>
            @for($d=1;$d<=$from->daysInMonth;$d++)<th>{{ $d }}</th>@endfor
            <th>P</th><th>A</th><th>WO</th>
        </tr>
        </thead>
        <tbody>
        @foreach($group as $user)
            @php $sum = $attendance->monthSummary($user, $from->year, $from->month); @endphp
            <tr>
                <td>{{ $user->displayName() }}</td>
                @foreach($sum['rows'] as $row)
                    @php
                        $letter = match($row['status']) { 'present','late' => 'P', 'absent' => 'A', 'week_off' => 'WO', 'leave' => 'L', default => '-' };
                        $bg = $letter==='P'?'#16a34a':($letter==='A'?'#dc2626':($letter==='WO'?'#6b7280':($letter==='L'?'#f97316':'#fff')));
                    @endphp
                    <td style="background:{{ $bg }};color:#fff;text-align:center">{{ $letter }}</td>
                @endforeach
                <td>{{ $sum['present'] }}</td>
                <td>{{ $sum['absent'] }}</td>
                <td>{{ $sum['weekOff'] }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
    </div>
@endforeach
@endsection
