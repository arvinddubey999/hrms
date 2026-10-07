@extends('layouts.app')
@section('content')
<div class="page-head">
    <h1>Monthly Roster Report</h1>
    <div class="row">
        <a class="btn light" href="{{ route('roster.export.excel', ['m'=>$month,'y'=>$year, 'employee_id'=>$employeeId, 'department_id'=>$departmentId, 'company_id'=>$companyId]) }}"><i class="fa-solid fa-file-excel" style="color:#16a34a"></i> Excel</a>
        <a class="btn light" href="{{ route('roster.export.pdf', ['m'=>$month,'y'=>$year, 'employee_id'=>$employeeId, 'department_id'=>$departmentId, 'company_id'=>$companyId]) }}" target="_blank"><i class="fa-solid fa-file-pdf" style="color:#dc2626"></i> PDF</a>
    </div>
</div>

<div class="card" style="margin-bottom:14px;padding:12px 18px">
    <form method="get" class="row" style="gap:10px;flex-wrap:wrap">
        <select name="company_id" onchange="this.form.submit()" style="width:160px">
            <option value="">All Companies</option>
            @foreach($companies as $comp)
                <option value="{{ $comp->id }}" {{ $companyId==$comp->id?'selected':'' }}>{{ $comp->name }}</option>
            @endforeach
        </select>

        <select name="department_id" onchange="this.form.submit()" style="width:160px">
            <option value="">All Departments</option>
            @foreach($departments as $dept)
                <option value="{{ $dept->id }}" {{ $departmentId==$dept->id?'selected':'' }}>{{ $dept->name }}</option>
            @endforeach
        </select>

        <select name="employee_id" onchange="this.form.submit()" style="width:180px">
            <option value="">All Employees</option>
            @foreach($allEmployees as $e)
                <option value="{{ $e->id }}" {{ $employeeId==$e->id?'selected':'' }}>{{ $e->displayName() }}</option>
            @endforeach
        </select>

        <select name="m" onchange="this.form.submit()" style="width:130px">
            @for($i=1;$i<=12;$i++)
                <option value="{{ $i }}" {{ $month==$i?'selected':'' }}>{{ date('F', mktime(0,0,0,$i,1)) }}</option>
            @endfor
        </select>

        <input type="number" name="y" value="{{ $year }}" style="width:90px" onchange="this.form.submit()">
        <a class="btn light" href="{{ route('roster.index') }}">Reset Filters</a>
    </form>
</div>

<!-- COLOR CODE ICON LEGEND DISPLAY -->
<div class="card" style="margin-bottom:14px;padding:12px 18px">
    <strong style="display:block;margin-bottom:8px;font-size:12px;color:#6b7280;text-transform:uppercase">Color Code Legend:</strong>
    <div class="row" style="gap:14px;flex-wrap:wrap">
        <span class="badge ok" style="background:#dcfce7;color:#166534"><i class="fa-solid fa-circle" style="color:#16a34a"></i> P (Present) - Green</span>
        <span class="badge no" style="background:#fee2e2;color:#991b1b"><i class="fa-solid fa-circle" style="color:#dc2626"></i> A (Absent) - Red</span>
        <span class="badge warn" style="background:#fef3c7;color:#92400e"><i class="fa-solid fa-circle" style="color:#d97706"></i> L (Late / Leave) - Yellow</span>
        <span class="badge" style="background:#dbeafe;color:#1e40af"><i class="fa-solid fa-circle" style="color:#2563eb"></i> WOP (Week Off Present) - Blue</span>
        <span class="badge" style="background:#f3f4f6;color:#4b5563"><i class="fa-solid fa-circle" style="color:#9ca3af"></i> WO (Week Off) - Gray</span>
        <span class="badge" style="background:#fef08a;color:#854d0e"><i class="fa-solid fa-circle" style="color:#eab308"></i> H (Holiday) - Gold</span>
    </div>
</div>

<div class="card" style="max-height: calc(100vh - 260px); overflow: auto; padding: 0; border-radius: 12px; border: 1px solid #e2e8f0">
    <table class="table" style="font-size:12px;white-space:nowrap;margin:0">
        <thead style="position:sticky;top:0;z-index:20;background:#f8fafc">
        <tr>
            <th style="position:sticky;left:0;top:0;background:#f8fafc;z-index:30;min-width:170px;box-shadow:2px 0 5px rgba(0,0,0,0.05)">Employee</th>
            @for($d=1;$d<=$days;$d++)
                @php $date = \Carbon\Carbon::create($year,$month,$d); @endphp
                <th style="text-align:center;min-width:70px">
                    <div>{{ $d }}</div>
                    <small style="font-weight:normal;color:#6b7280">{{ strtoupper($date->format('D')) }}</small>
                </th>
            @endfor
            <th style="text-align:center;background:#e0f2fe;color:#0369a1;min-width:50px">P</th>
            <th style="text-align:center;background:#fee2e2;color:#991b1b;min-width:50px">A</th>
            <th style="text-align:center;background:#fef08a;color:#854d0e;min-width:50px">H</th>
            <th style="text-align:center;background:#f3f4f6;color:#4b5563;min-width:50px">WO</th>
            <th style="text-align:center;background:#dbeafe;color:#1e40af;min-width:50px">WOP</th>
            <th style="text-align:center;background:#dcfce7;color:#166534;font-weight:bold;min-width:90px">TOTAL PAYABLE</th>
        </tr>
        </thead>
        <tbody>
        @foreach($employees as $user)
            @php $sum = $attendance->monthSummary($user,$year,$month); @endphp
            <tr>
                <td style="position:sticky;left:0;background:#fff;z-index:10">
                    <b>{{ $user->displayName() }}</b>
                    <div style="font-size:10px;color:#6b7280">{{ $user->employee_code }} | {{ $user->department ?: '-' }}</div>
                </td>
                @foreach($sum['rows'] as $row)
                    @php
                        $st = $row['status'];
                        $inPunch = $row['ins']->first();
                        $outPunch = $row['outs']->last();
                        $inTime = $inPunch ? $inPunch->punched_at->format('g:i A') : null;
                        $outTime = $outPunch ? $outPunch->punched_at->format('g:i A') : null;

                        if ($st === 'present') {
                            $code = 'P'; $bg = '#dcfce7'; $fg = '#166534';
                        } elseif ($st === 'late') {
                            $code = 'P(L)'; $bg = '#fef3c7'; $fg = '#92400e';
                        } elseif ($st === 'wop') {
                            $code = 'WOP'; $bg = '#dbeafe'; $fg = '#1e40af';
                        } elseif ($st === 'absent') {
                            $code = 'A'; $bg = '#fee2e2'; $fg = '#991b1b';
                        } elseif ($st === 'leave') {
                            $code = 'L'; $bg = '#ffedd5'; $fg = '#9a3412';
                        } elseif ($st === 'week_off') {
                            $code = 'WO'; $bg = '#f3f4f6'; $fg = '#4b5563';
                        } elseif ($st === 'holiday') {
                            $code = 'H'; $bg = '#fef08a'; $fg = '#854d0e';
                        } else {
                            $code = '-'; $bg = '#fff'; $fg = '#6b7280';
                        }
                    @endphp
                    <td style="text-align:center;background:{{ $bg }};border-right:1px solid #eee;padding:4px">
                        <!-- P / A CODE DISPLAY -->
                        <div style="font-weight:800;color:{{ $fg }};font-size:13px">{{ $code }}</div>
                        
                        <!-- IN TIME AND OUT TIME DISPLAY RIGHT UNDERNEATH -->
                        @if($inTime || $outTime)
                            <div style="font-size:9px;color:#374151;margin-top:2px;line-height:1.1">
                                @if($inTime)<div style="color:#166534">IN: {{ $inTime }}</div>@endif
                                @if($outTime)<div style="color:#9a3412">OUT: {{ $outTime }}</div>@endif
                            </div>
                        @endif
                    </td>
                @endforeach
                @php
                    $cntP = $sum['present'] ?? 0;
                    $cntA = $sum['absent'] ?? 0;
                    $cntH = $sum['holiday'] ?? 0;
                    $cntWO = $sum['weekOff'] ?? 0;
                    $cntWOP = $sum['wop'] ?? 0;
                    $totPayable = $cntP + $cntH + $cntWO + $cntWOP;
                @endphp
                <td style="text-align:center;background:#e0f2fe;font-weight:bold">{{ $cntP }}</td>
                <td style="text-align:center;background:#fee2e2;font-weight:bold;color:#dc2626">{{ $cntA }}</td>
                <td style="text-align:center;background:#fef08a;font-weight:bold">{{ $cntH }}</td>
                <td style="text-align:center;background:#f3f4f6;font-weight:bold">{{ $cntWO }}</td>
                <td style="text-align:center;background:#dbeafe;font-weight:bold">{{ $cntWOP }}</td>
                <td style="text-align:center;background:#dcfce7;font-weight:bold;color:#166534;font-size:14px">{{ $totPayable }}</td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endsection
