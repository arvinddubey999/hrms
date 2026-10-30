@extends('layouts.app')
@section('content')
<style>
    /* Ensure outer page never scrolls horizontally */
    .main {
        min-width: 0 !important;
        max-width: 100% !important;
        overflow-x: hidden !important;
    }
    
    .roster-card {
        margin-bottom: 14px;
        padding: 0;
        border-radius: 12px;
        border: 1px solid #e2e8f0;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
        background: #fff;
    }

    .roster-scroll-container {
        width: 100%;
        max-width: 100%;
        overflow-x: auto;
        overflow-y: auto;
        max-height: calc(100vh - 270px);
        position: relative;
    }

    .roster-table {
        border-collapse: separate !important;
        border-spacing: 0 !important;
        font-size: 12px;
        white-space: nowrap;
        margin: 0;
        width: max-content;
        min-width: 100%;
    }

    .roster-table thead {
        position: sticky;
        top: 0;
        z-index: 20;
        background: #f8fafc;
    }

    .roster-table thead th {
        position: sticky;
        top: 0;
        z-index: 20;
        background: #f8fafc;
        border-bottom: 2px solid #cbd5e1;
        padding: 8px 6px;
    }

    /* Fixed sticky employee column */
    .roster-table th.sticky-emp-col {
        position: sticky !important;
        left: 0 !important;
        top: 0 !important;
        z-index: 35 !important;
        background: #f1f5f9 !important;
        min-width: 240px !important;
        max-width: 270px !important;
        width: 250px !important;
        box-shadow: 3px 0 6px -2px rgba(0, 0, 0, 0.12) !important;
        border-right: 2px solid #cbd5e1 !important;
        text-align: left;
        padding: 10px 14px;
    }

    .roster-table td.sticky-emp-col {
        position: sticky !important;
        left: 0 !important;
        z-index: 15 !important;
        background: #ffffff !important;
        min-width: 240px !important;
        max-width: 270px !important;
        width: 250px !important;
        box-shadow: 3px 0 6px -2px rgba(0, 0, 0, 0.12) !important;
        border-right: 2px solid #cbd5e1 !important;
        text-align: left;
        padding: 8px 12px;
    }

    .roster-table tbody tr:hover td.sticky-emp-col {
        background: #f8fafc !important;
    }
    
    .roster-table tbody tr:hover td {
        filter: brightness(0.98);
    }
</style>

<div class="page-head">
    <h1>Monthly Roster Report</h1>
    <div class="row">
        <a class="btn light" href="{{ route('roster.export.excel', ['m'=>$month,'y'=>$year, 'from_date'=>$fromDate, 'to_date'=>$toDate, 'employee_id'=>$employeeId, 'department_id'=>$departmentId, 'company_id'=>$companyId]) }}"><i class="fa-solid fa-file-excel" style="color:#16a34a"></i> Excel</a>
        <a class="btn light" href="{{ route('roster.export.pdf', ['m'=>$month,'y'=>$year, 'from_date'=>$fromDate, 'to_date'=>$toDate, 'employee_id'=>$employeeId, 'department_id'=>$departmentId, 'company_id'=>$companyId]) }}" target="_blank"><i class="fa-solid fa-file-pdf" style="color:#dc2626"></i> PDF</a>
    </div>
</div>

<div class="card" style="margin-bottom:14px;padding:12px 18px">
    <form method="get" class="row" style="gap:10px;flex-wrap:wrap;align-items:center">
        @if($companies->count() > 1 || (auth()->user() && auth()->user()->isSuperAdmin()))
        <select name="company_id" onchange="this.form.submit()" style="width:160px">
            <option value="">All Companies</option>
            @foreach($companies as $comp)
                <option value="{{ $comp->id }}" {{ $companyId==$comp->id?'selected':'' }}>{{ $comp->name }}</option>
            @endforeach
        </select>
        @elseif($companies->count() === 1)
        <input type="hidden" name="company_id" value="{{ $companies->first()->id }}">
        <div style="background:#e0f2fe;color:#0369a1;padding:6px 12px;border-radius:8px;font-size:12px;font-weight:700;display:inline-flex;align-items:center;gap:6px">
            <i class="fa-solid fa-building"></i> {{ $companies->first()->name }}
        </div>
        @endif

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

        <div style="display:flex;align-items:center;gap:6px">
            <span style="font-size:12px;color:#6b7280">From:</span>
            <input type="date" name="from_date" value="{{ $fromDate }}" style="width:135px" onchange="this.form.submit()">
            <span style="font-size:12px;color:#6b7280">To:</span>
            <input type="date" name="to_date" value="{{ $toDate }}" style="width:135px" onchange="this.form.submit()">
        </div>

        <a class="btn light" href="{{ route('roster.index') }}">Reset Filters</a>
    </form>
</div>

<!-- COLOR CODE ICON LEGEND DISPLAY -->
<div class="card" style="margin-bottom:14px;padding:12px 18px">
    <strong style="display:block;margin-bottom:8px;font-size:12px;color:#6b7280;text-transform:uppercase">Color Code Legend:</strong>
    <div class="row" style="gap:14px;flex-wrap:wrap">
        <span class="badge ok" style="background:#dcfce7;color:#166534"><i class="fa-solid fa-circle" style="color:#16a34a"></i> P (Present)</span>
        <span class="badge no" style="background:#fee2e2;color:#991b1b"><i class="fa-solid fa-circle" style="color:#dc2626"></i> A (Absent)</span>
        <span class="badge warn" style="background:#fef3c7;color:#92400e"><i class="fa-solid fa-circle" style="color:#d97706"></i> L (Late / Leave)</span>
        <span class="badge" style="background:#dbeafe;color:#1e40af"><i class="fa-solid fa-circle" style="color:#2563eb"></i> WOP (Week Off Present)</span>
        <span class="badge" style="background:#f3f4f6;color:#4b5563"><i class="fa-solid fa-circle" style="color:#9ca3af"></i> WO (Week Off)</span>
        <span class="badge" style="background:#fef08a;color:#854d0e"><i class="fa-solid fa-circle" style="color:#eab308"></i> H (Holiday)</span>
        <span class="badge" style="background:#fbcfe8;color:#831843"><i class="fa-solid fa-circle" style="color:#db2777"></i> HOP (Holiday Present)</span>
        <span class="badge" style="background:#f8fafc;color:#64748b;border:1px dashed #cbd5e1"><i class="fa-solid fa-minus" style="color:#94a3b8"></i> - (Upcoming / Not Marked)</span>
    </div>
</div>

<!-- INDEPENDENT SCROLLING TABLE CONTAINER (FIXED NAME COLUMN & ONLY DATE COLUMNS SCROLL) -->
<div class="roster-card">
    <div class="roster-scroll-container">
        <table class="roster-table">
            <thead>
            <tr>
                <th class="sticky-emp-col">Employee</th>
                @foreach($dateRange as $dt)
                    <th style="text-align:center;min-width:70px">
                        <div>{{ $dt->format('d') }}</div>
                        <small style="font-weight:normal;color:#6b7280">{{ strtoupper($dt->format('D')) }}</small>
                    </th>
                @endforeach
                <th style="text-align:center;background:#e0f2fe;color:#0369a1;min-width:50px">P</th>
                <th style="text-align:center;background:#fee2e2;color:#991b1b;min-width:50px">A</th>
                <th style="text-align:center;background:#fef08a;color:#854d0e;min-width:50px">H</th>
                <th style="text-align:center;background:#f3f4f6;color:#4b5563;min-width:50px">WO</th>
                <th style="text-align:center;background:#dbeafe;color:#1e40af;min-width:50px">WOP</th>
                <th style="text-align:center;background:#fbcfe8;color:#9d174d;min-width:50px">HOP</th>
                <th style="text-align:center;background:#dcfce7;color:#166534;font-weight:bold;min-width:90px">TOTAL PAYABLE</th>
            </tr>
            </thead>
            <tbody>
            @foreach($employees as $user)
                @php $sum = $attendance->monthSummary($user, $year, $month, $dateRange); @endphp
                <tr>
                    <td class="sticky-emp-col">
                        <!-- EMPLOYEE NAME (CLICKABLE TO OPEN PROFILE) -->
                        <div>
                            <a href="{{ route('employees.show', $user) }}" target="_blank" style="color:#0f172a;font-weight:700;font-size:13px;text-decoration:none" onmouseover="this.style.textDecoration='underline'" onmouseout="this.style.textDecoration='none'" title="Click to view employee profile">
                                {{ $user->displayName() }} <i class="fa-solid fa-arrow-up-right-from-square" style="font-size:9.5px;color:#94a3b8;margin-left:2px"></i>
                            </a>
                        </div>
                        
                        <!-- EMPLOYEE CODE, CATEGORY & DEPARTMENT -->
                        <div style="font-size:10.5px;color:#475569;margin-top:2px;display:flex;align-items:center;gap:4px;flex-wrap:wrap">
                            <span style="font-weight:600;color:#0284c7">{{ $user->employee_code ?: 'N/A' }}</span>
                            <span style="color:#cbd5e1">•</span>
                            <span>{{ $user->category?->name ?? 'No Category' }}</span>
                            <span style="color:#cbd5e1">•</span>
                            <span>{{ $user->department ?: '-' }}</span>
                        </div>

                        <!-- SHIFT TIME UNDER NAME -->
                        <div style="font-size:10px;color:#64748b;margin-top:2px;display:flex;align-items:center;gap:4px">
                            <i class="fa-regular fa-clock" style="color:#0ea5e9;font-size:10px"></i>
                            @if($user->shift)
                                <span>Shift: {{ date('h:i A', strtotime($user->shift->start_time)) }} - {{ date('h:i A', strtotime($user->shift->end_time)) }}</span>
                            @else
                                <span style="color:#94a3b8">Shift: Not Assigned</span>
                            @endif
                        </div>
                    </td>

                    @foreach($sum['rows'] as $row)
                        @php
                            $st = $row['status'];
                            $inPunch = $row['ins']->first();
                            $outPunch = $row['outs']->last();
                            $inTime = $inPunch ? $inPunch->punched_at->format('g:i A') : null;
                            $outTime = $outPunch ? $outPunch->punched_at->format('g:i A') : null;
                            $hTitle = $row['holiday_title'] ?? null;

                            if ($st === 'present') {
                                $code = 'P'; $bg = '#dcfce7'; $fg = '#166534';
                            } elseif ($st === 'late') {
                                $code = 'P(L)'; $bg = '#fef3c7'; $fg = '#92400e';
                            } elseif ($st === 'wop') {
                                $code = 'WOP'; $bg = '#dbeafe'; $fg = '#1e40af';
                            } elseif ($st === 'hop') {
                                $code = 'HOP'; $bg = '#fbcfe8'; $fg = '#9d174d';
                            } elseif ($st === 'absent') {
                                $code = 'A'; $bg = '#fee2e2'; $fg = '#991b1b';
                            } elseif ($st === 'leave') {
                                $code = 'L'; $bg = '#ffedd5'; $fg = '#9a3412';
                            } elseif ($st === 'week_off') {
                                $code = 'WO'; $bg = '#f3f4f6'; $fg = '#4b5563';
                            } elseif ($st === 'holiday') {
                                $code = 'H'; $bg = '#fef08a'; $fg = '#854d0e';
                            } else {
                                $code = '-'; $bg = '#fff'; $fg = '#94a3b8';
                            }

                            // Tooltip construction
                            if ($hTitle) {
                                $tooltip = 'Holiday: ' . $hTitle;
                            } elseif ($code === 'P') {
                                $tooltip = 'Present';
                            } elseif ($code === 'P(L)') {
                                $tooltip = 'Late Punch';
                            } elseif ($code === 'WO') {
                                $tooltip = 'Week Off';
                            } elseif ($code === 'WOP') {
                                $tooltip = 'Week Off Present';
                            } elseif ($code === 'HOP') {
                                $tooltip = 'Holiday Present';
                            } elseif ($code === 'A') {
                                $tooltip = 'Absent';
                            } elseif ($code === 'L') {
                                $tooltip = 'Leave';
                            } else {
                                $tooltip = 'Upcoming / Not Marked';
                            }

                            if ($inTime) { $tooltip .= ' | IN: ' . $inTime; }
                            if ($outTime) { $tooltip .= ' | OUT: ' . $outTime; }
                            $tooltip .= ' — Click to view Day Punches Log';
                        @endphp
                        <td style="text-align:center;background:{{ $bg }};border-right:1px solid #f1f5f9;border-bottom:1px solid #f1f5f9;padding:0" title="{{ $tooltip }}">
                            <!-- CLICKABLE CELL OPENING DAY PUNCHES LOG -->
                            <a href="{{ route('employees.day', [$user, 'date' => $row['date']]) }}" target="_blank" style="text-decoration:none;display:block;padding:6px 3px;color:inherit;height:100%" title="{{ $tooltip }}">
                                <!-- CODE DISPLAY -->
                                <div style="font-weight:800;color:{{ $fg }};font-size:12.5px">{{ $code }}</div>
                                
                                <!-- IN TIME AND OUT TIME DISPLAY -->
                                @if($inTime || $outTime)
                                    <div style="font-size:9px;color:#374151;margin-top:2px;line-height:1.15">
                                        @if($inTime)<div style="color:#166534;font-weight:600">IN: {{ $inTime }}</div>@endif
                                        @if($outTime)<div style="color:#9a3412;font-weight:600">OUT: {{ $outTime }}</div>@endif
                                    </div>
                                @endif
                            </a>
                        </td>
                    @endforeach

                    @php
                        $cntP = $sum['present'] ?? 0;
                        $cntA = $sum['absent'] ?? 0;
                        $cntH = $sum['holiday'] ?? 0;
                        $cntWO = $sum['weekOff'] ?? 0;
                        $cntWOP = $sum['wop'] ?? 0;
                        $cntHOP = $sum['hop'] ?? 0;
                        $totPayable = $sum['payable'] ?? ($cntP + $cntH + $cntWO + $cntWOP + $cntHOP);
                    @endphp
                    <td style="text-align:center;background:#e0f2fe;font-weight:bold;border-bottom:1px solid #f1f5f9">{{ $cntP }}</td>
                    <td style="text-align:center;background:#fee2e2;font-weight:bold;color:#dc2626;border-bottom:1px solid #f1f5f9">{{ $cntA }}</td>
                    <td style="text-align:center;background:#fef08a;font-weight:bold;border-bottom:1px solid #f1f5f9">{{ $cntH }}</td>
                    <td style="text-align:center;background:#f3f4f6;font-weight:bold;border-bottom:1px solid #f1f5f9">{{ $cntWO }}</td>
                    <td style="text-align:center;background:#dbeafe;font-weight:bold;border-bottom:1px solid #f1f5f9">{{ $cntWOP }}</td>
                    <td style="text-align:center;background:#fbcfe8;font-weight:bold;color:#9d174d;border-bottom:1px solid #f1f5f9">{{ $cntHOP }}</td>
                    <td style="text-align:center;background:#dcfce7;font-weight:bold;color:#166534;font-size:13px;border-bottom:1px solid #f1f5f9">{{ $totPayable }}</td>
                </tr>
            @endforeach
            </tbody>
        </table>
    </div>
</div>
@endsection
