@extends('layouts.app')
@section('content')
<div class="page-head">
    <h1>Attendance Tracker Report</h1>
    <div class="row">
        <button class="btn light" onclick="window.print()"><i class="fa-solid fa-print"></i> Print Report</button>
        <a class="btn" href="{{ route('reports.generate', ['type'=>'tracker','from'=>$from->startOfMonth()->toDateString(),'format'=>'excel']) }}">
            <i class="fa-solid fa-file-excel"></i> Export Excel
        </a>
    </div>
</div>

<div class="card" style="margin-bottom:16px;text-align:center;background:#fff;border:1px solid #d1d5db">
    <h3 style="margin:4px 0;color:#1e40af">Report Date From : {{ $from->startOfMonth()->format('d-m-Y') }} To : {{ $from->copy()->endOfMonth()->format('d-m-Y') }}</h3>
    <h2 style="margin:4px 0;color:#111827">Company Name : {{ \App\Models\Setting::current()->company_name }}</h2>
    <div style="color:#4b5563;font-weight:700">Branch : Main Office</div>
</div>

@php
$daysInMonth = $from->daysInMonth;
@endphp

@foreach($employees->groupBy(fn($u) => $u->department ?: ($u->department_id ? \App\Models\Department::find($u->department_id)?->name : 'General')) as $dept => $group)
    @foreach($group as $user)
        @php
            $sum = $attendance->monthSummary($user, $from->year, $from->month);
            $shiftFrom = $user->shift ? substr($user->shift->start_time, 0, 5) : '-';
            $shiftTo = $user->shift ? substr($user->shift->end_time, 0, 5) : '-';
            
            $totalMin = 0;
            foreach($sum['rows'] as $r) {
                [$h, $m] = explode(':', $r['hours']);
                $totalMin += ((int)$h * 60) + (int)$m;
            }
            $workHrsStr = sprintf('%02d:%02d', intdiv($totalMin, 60), $totalMin % 60);
        @endphp

        <div class="card" style="margin-bottom:20px;padding:0;overflow-x:auto;border:1px solid #9ca3af;background:#fff">
            <!-- Header Block per Employee matching Image 2 -->
            <div style="background:#f3f4f6;padding:8px 12px;border-bottom:1px solid #9ca3af;display:flex;justify-content:space-between;font-weight:bold;font-size:13px">
                <div>Department : <span style="color:#1d4ed8">{{ $dept }}</span></div>
                <div>Desig : <span style="color:#1d4ed8">{{ $user->designation ?: 'N/A' }}</span></div>
            </div>

            <!-- Employee Summary Row matching Image 2 -->
            <table class="table" style="font-size:11px;margin:0;border-collapse:collapse;white-space:nowrap">
                <tr style="background:#e5e7eb;font-weight:bold">
                    <td style="border:1px solid #9ca3af">EmpCode</td>
                    <td style="border:1px solid #9ca3af">Name</td>
                    <td style="border:1px solid #9ca3af;background:#16a34a;color:#fff;text-align:center">Present</td>
                    <td style="border:1px solid #9ca3af;background:#eab308;color:#fff;text-align:center">Holiday</td>
                    <td style="border:1px solid #9ca3af;background:#9ca3af;color:#fff;text-align:center">WeekOff</td>
                    <td style="border:1px solid #9ca3af;background:#22c55e;color:#fff;text-align:center">Halfday</td>
                    <td style="border:1px solid #9ca3af;background:#dc2626;color:#fff;text-align:center">Absent</td>
                    <td style="border:1px solid #9ca3af;background:#f97316;color:#fff;text-align:center">Leave</td>
                    <td style="border:1px solid #9ca3af;background:#4b5563;color:#fff;text-align:center">PaidDay</td>
                    <td style="border:1px solid #9ca3af;background:#2563eb;color:#fff;text-align:center">WorkHrs</td>
                    <td style="border:1px solid #9ca3af;background:#1d4ed8;color:#fff;text-align:center">ShortHrs</td>
                    <td style="border:1px solid #9ca3af;background:#374151;color:#fff;text-align:center">Overtime</td>
                </tr>
                <tr>
                    <td style="border:1px solid #9ca3af"><b>{{ $user->employee_code ?: '00000' }}</b></td>
                    <td style="border:1px solid #9ca3af"><b>{{ $user->displayName() }}</b></td>
                    <td style="border:1px solid #9ca3af;text-align:center"><b>{{ $sum['present'] }}</b></td>
                    <td style="border:1px solid #9ca3af;text-align:center"><b>{{ $sum['holiday'] }}</b></td>
                    <td style="border:1px solid #9ca3af;text-align:center"><b>{{ $sum['weekOff'] }}</b></td>
                    <td style="border:1px solid #9ca3af;text-align:center"><b>{{ $sum['half'] }}</b></td>
                    <td style="border:1px solid #9ca3af;text-align:center"><b>{{ $sum['absent'] }}</b></td>
                    <td style="border:1px solid #9ca3af;text-align:center"><b>{{ $sum['leave'] }}</b></td>
                    <td style="border:1px solid #9ca3af;text-align:center"><b>{{ $sum['payable'] }}</b></td>
                    <td style="border:1px solid #9ca3af;text-align:center"><b>{{ $workHrsStr }}</b></td>
                    <td style="border:1px solid #9ca3af;text-align:center"><b>00:00</b></td>
                    <td style="border:1px solid #9ca3af;text-align:center"><b>0</b></td>
                </tr>
            </table>

            <!-- Detailed Grid Row per Date (1 to 31) matching Image 2 -->
            <table class="table" style="font-size:10px;margin:0;border-collapse:collapse;white-space:nowrap;text-align:center">
                <thead>
                    <tr style="background:#f3f4f6;font-weight:bold">
                        <th style="border:1px solid #9ca3af;width:70px">Label</th>
                        @for($d=1; $d<=$daysInMonth; $d++)
                            <th style="border:1px solid #9ca3af;min-width:42px">{{ $d }}</th>
                        @endfor
                    </tr>
                </thead>
                <tbody>
                    <!-- IN Time Row -->
                    <tr>
                        <td style="border:1px solid #9ca3af;font-weight:bold;background:#f9fafb;text-align:left">IN Time</td>
                        @foreach($sum['rows'] as $row)
                            @php $in = $row['ins']->first(); @endphp
                            <td style="border:1px solid #9ca3af;color:#15803d;font-weight:bold">
                                {{ $in ? $in->punched_at->format('H:i') : '-' }}
                            </td>
                        @endforeach
                    </tr>
                    <!-- OUT Time Row -->
                    <tr>
                        <td style="border:1px solid #9ca3af;font-weight:bold;background:#f9fafb;text-align:left">OUT Time</td>
                        @foreach($sum['rows'] as $row)
                            @php $out = $row['outs']->last(); @endphp
                            <td style="border:1px solid #9ca3af;color:#b91c1c;font-weight:bold">
                                {{ $out ? $out->punched_at->format('H:i') : '-' }}
                            </td>
                        @endforeach
                    </tr>
                    <!-- Shift From Row -->
                    <tr>
                        <td style="border:1px solid #9ca3af;font-weight:bold;background:#f9fafb;text-align:left">Shift From</td>
                        @for($d=1; $d<=$daysInMonth; $d++)
                            <td style="border:1px solid #9ca3af;color:#6b7280">{{ $shiftFrom }}</td>
                        @endfor
                    </tr>
                    <!-- Shift To Row -->
                    <tr>
                        <td style="border:1px solid #9ca3af;font-weight:bold;background:#f9fafb;text-align:left">Shift To</td>
                        @for($d=1; $d<=$daysInMonth; $d++)
                            <td style="border:1px solid #9ca3af;color:#6b7280">{{ $shiftTo }}</td>
                        @endfor
                    </tr>
                    <!-- Working Row -->
                    <tr>
                        <td style="border:1px solid #9ca3af;font-weight:bold;background:#f9fafb;text-align:left">Working</td>
                        @foreach($sum['rows'] as $row)
                            <td style="border:1px solid #9ca3af;font-weight:bold">{{ $row['hours'] !== '00:00' ? $row['hours'] : '-' }}</td>
                        @endforeach
                    </tr>
                    <!-- O.Times Row -->
                    <tr>
                        <td style="border:1px solid #9ca3af;font-weight:bold;background:#f9fafb;text-align:left">O.Times</td>
                        @for($d=1; $d<=$daysInMonth; $d++)
                            <td style="border:1px solid #9ca3af">-</td>
                        @endfor
                    </tr>
                    <!-- Short hrs. Row -->
                    <tr>
                        <td style="border:1px solid #9ca3af;font-weight:bold;background:#f9fafb;text-align:left">Short hrs.</td>
                        @foreach($sum['rows'] as $row)
                            <td style="border:1px solid #9ca3af;color:#9a3412">-</td>
                        @endforeach
                    </tr>
                    <!-- Status Row matching Image 2 colored cells -->
                    <tr>
                        <td style="border:1px solid #9ca3af;font-weight:bold;background:#f9fafb;text-align:left">Status</td>
                        @foreach($sum['rows'] as $row)
                            @php
                                $st = $row['status'];
                                $code = match($st) {
                                    'present','late' => 'P',
                                    'wop' => 'P',
                                    'absent' => 'A',
                                    'week_off' => 'WO',
                                    'holiday' => 'H',
                                    'leave' => 'L',
                                    default => '-'
                                };
                                $cellBg = match($st) {
                                    'present','late','wop' => '#16a34a',
                                    'absent' => '#dc2626',
                                    'week_off' => '#6b7280',
                                    'holiday' => '#eab308',
                                    'leave' => '#f97316',
                                    default => '#fff'
                                };
                            @endphp
                            <td style="border:1px solid #9ca3af;background:{{ $cellBg }};color:#fff;font-weight:bold;font-size:11px">
                                {{ $code }}
                            </td>
                        @endforeach
                    </tr>
                </tbody>
            </table>
        </div>
    @endforeach
@endforeach
@endsection
