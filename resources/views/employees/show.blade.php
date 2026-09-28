@extends('layouts.app')
@section('content')
<div class="page-head">
    <h1><a href="{{ route('attendances.index') }}">←</a> Staff Profile</h1>
    <div class="row">
        <form method="post" action="{{ route('employees.destroy', $staff) }}" onsubmit="return confirm('Archive this employee?')">
            @csrf @method('delete')
            <button class="btn light">Delete Employee</button>
        </form>
        <a class="btn" href="{{ route('employees.edit', $staff) }}">Edit Profile</a>
    </div>
</div>

<div class="grid-2">
    <div class="card person" style="align-items:flex-start">
        <span class="dot" style="width:86px;height:86px;font-size:22px;background:#7c3aed"><img src="{{ $staff->profile_photo ? asset('storage/'.$staff->profile_photo) : asset('images/user.png') }}" alt="Profile Photo" style="width:100%;height:100%;object-fit:cover;border-radius:10%;"></span>
        <!-- {{ $staff->initials() }} -->
        <div>
            <h2 style="margin:0">{{ $staff->displayName() }}</h2>
            <div class="muted">Employee Code: {{ $staff->employee_code ?: '...' }}</div>
            <div class="muted">Designation: {{ $staff->designation ?: '...' }}</div>
            <div class="muted">Department: {{ $staff->department ?: '...' }}</div>
            <div>Phone: {{ $staff->phone }}</div>
            <div>Email: {{ $staff->email ?: '—' }}</div>
        </div>
    </div>
    <div>
        <div class="card">
            <strong>Employment Details</strong>
            <p>Date of Joining <span style="float:right">{{ optional($staff->date_of_joining)->format('F j, Y') ?: '—' }}</span></p>
            <p>Current Salary <span style="float:right">₹{{ number_format($staff->salary,1) }}</span></p>
        </div>
        <div class="card" style="margin-top:12px">
            <strong>Current Location</strong>
            <p class="muted">{{ $staff->last_lat ? $staff->last_lat.', '.$staff->last_lng : 'No live location yet' }}</p>
            <div class="muted">Last updated: {{ optional($staff->last_location_at)->format('F j, Y \a\t h:i A') ?: '—' }}</div>
        </div>
        <div class="card" style="margin-top:12px">
            <strong>Attendance Settings</strong>
            <p>Mobile Attendance <span style="float:right">{{ $staff->mobile_attendance?'Enabled':'Disabled' }}</span></p>
            <p>Multiple Attendance <span style="float:right">{{ $staff->multiple_attendance?'Enabled':'Disabled' }}</span></p>
            <p>Live Tracking <span style="float:right">{{ $staff->live_tracking?'Enabled':'Disabled' }}</span></p>
        </div>
        <div class="card" style="margin-top:12px">
            <strong>Benefits Information</strong>
            <p>PF Number <span style="float:right">{{ $staff->pf_number ?: '...' }}</span></p>
            <p>UAN <span style="float:right">{{ $staff->uan ?: '...' }}</span></p>
            <p>ESI Applicable <span style="float:right">{{ $staff->esi_applicable?'Y':'N' }}</span></p>
        </div>
    </div>
</div>

<div class="card" style="margin-top:16px">
    <div class="tabs">
        <a class="{{ $tab==='attendance'?'active':'' }}" href="{{ route('employees.show', [$staff,'tab'=>'attendance','month'=>$month,'year'=>$year]) }}">Attendance</a>
        <a class="{{ $tab==='salary'?'active':'' }}" href="{{ route('employees.show', [$staff,'tab'=>'salary','month'=>$month,'year'=>$year]) }}">Salary</a>
        <a class="chip">Expense</a>
        <a class="chip">Incentive</a>
        <a class="chip">Loan/EMI</a>
    </div>

    @if($tab==='attendance')
        <div class="row">
            <a class="btn" href="{{ route('employees.monthly', [$staff,'month'=>$month,'year'=>$year]) }}" target="_blank">Monthly Reports</a>
            <a class="btn" href="{{ route('reports.generate', ['type'=>'overall','from'=>sprintf('%04d-%02d-01',$year,$month)]) }}">Monthly Summary Sheet</a>
            <a class="btn" href="{{ route('tasks.index') }}">Task Report</a>
        </div>
        <div class="kpi" style="margin:16px 0">
            <div class="badge ok">Present {{ $summary['present'] }} times</div>
            <div class="badge no">Absent {{ $summary['absent'] }} times</div>
            <div class="badge warn">Half Day {{ $summary['half'] }} times</div>
            <div class="chip">Holiday {{ $summary['holiday'] }} times</div>
            <div class="chip">Week Off {{ $summary['weekOff'] }} times</div>
            <div class="badge warn">Paid Leave {{ $summary['leave'] }} times</div>
            <div class="badge no">Late {{ $summary['late'] }} times</div>
        </div>
        <h3>{{ \Carbon\Carbon::create($year,$month,1)->format('F Y') }}</h3>
        <div class="cal">
            @foreach(['SUN','MON','TUE','WED','THU','FRI','SAT'] as $d)<div class="muted">{{ $d }}</div>@endforeach
            @php $start = \Carbon\Carbon::create($year,$month,1); @endphp
            @for($i=0;$i<$start->dayOfWeek;$i++)<div></div>@endfor
            @foreach($summary['rows'] as $row)
                @php $cls = $row['status']==='present'||$row['status']==='late'?'present':($row['status']==='week_off'?'off':($row['status']==='leave'?'leave':($row['status']==='absent'?'absent':''))); @endphp
                <a class="day {{ $cls }}" href="{{ route('employees.day', [$staff,'date'=>$row['date']]) }}">
                    <strong>{{ (int) substr($row['date'],-2) }}</strong>
                    <div class="muted">{{ str_replace('_',' ', $row['status']) }}</div>
                </a>
            @endforeach
        </div>
        <form class="row" style="margin-top:16px" method="post" action="{{ route('employees.mark', $staff) }}">
            @csrf
            <select name="type" style="width:120px"><option value="in">IN</option><option value="out">OUT</option></select>
            <input type="time" name="time" style="width:140px">
            <button class="btn">Mark Attendance (manager/keypad staff)</button>
        </form>
    @else
        @php $pay = app(\App\Services\PayrollService::class)->compute($staff,$year,$month); @endphp
        <form class="row" method="get">
            <input type="hidden" name="tab" value="salary">
            <select name="month" style="width:160px">
                @for($m=1;$m<=12;$m++)<option value="{{ $m }}" {{ $month==$m?'selected':'' }}>{{ date('F', mktime(0,0,0,$m,1)) }}</option>@endfor
            </select>
            <input type="number" name="year" value="{{ $year }}" style="width:120px">
            <button class="btn light">Go</button>
            <a class="btn" href="{{ route('payroll.payslip', [$staff,'m'=>$month,'y'=>$year]) }}" target="_blank">Generate Payslip</a>
        </form>
        <div class="card" style="margin-top:12px">
            <h3>Salary Info</h3>
            <p>Pay Type <b>{{ ucfirst($staff->pay_type) }}</b> &nbsp; Month CTC <b>₹{{ number_format($staff->salary) }}</b></p>
            <p>Basic Salary <b>₹{{ number_format($staff->salary) }}</b> &nbsp; Daily Rate <b>₹{{ $pay['daily'] }}</b></p>
        </div>
        <div class="card" style="margin-top:12px">
            <h3>Attendance Summary</h3>
            <p>Total Days {{ $pay['summary']['days'] }} &nbsp; Present Days {{ $pay['summary']['present'] }}</p>
            <p>Week Offs {{ $pay['summary']['weekOff'] }} &nbsp; Absent Days {{ $pay['summary']['absent'] }}</p>
            <p>Payable Days {{ $pay['payable_days'] }}</p>
        </div>
        <div class="card" style="margin-top:12px">
            <h3>Advance Payments Made <form style="display:inline" method="post" action="{{ route('payroll.advance', $staff) }}">@csrf
                <input name="title" placeholder="cash" style="width:120px">
                <input name="amount" type="number" step="0.01" style="width:100px">
                <input type="date" name="paid_on" value="{{ now()->toDateString() }}" style="width:150px">
                <button class="btn">+ Add Payment</button>
            </form></h3>
            @forelse($staff->advances as $a)
                <p>{{ $a->title }} <span class="badge ok">{{ $a->status }}</span> <span style="color:#b91c1c;float:right">- ₹{{ number_format($a->amount,2) }}</span><br><span class="muted">{{ $a->paid_on->toDateString() }}</span></p>
            @empty
                <p class="muted">No advances</p>
            @endforelse
        </div>
        <div class="card" style="margin-top:12px">
            <h3>Incentives <form style="display:inline" method="post" action="{{ route('payroll.incentive', $staff) }}">@csrf
                <input name="title" placeholder="Target Achieve" style="width:160px">
                <input name="amount" type="number" step="0.01" style="width:100px">
                <input type="date" name="paid_on" value="{{ now()->toDateString() }}" style="width:150px">
                <button class="btn">+ Add Incentive</button>
            </form></h3>
            @forelse($staff->incentives as $a)
                <p>{{ $a->title }} <span class="badge ok">{{ $a->status }}</span> <span style="color:#166534;float:right">+ ₹{{ number_format($a->amount,2) }}</span></p>
            @empty
                <p class="muted">No incentives</p>
            @endforelse
        </div>
        <p>Basic Salary {{ $pay['payable_days'] }} days × ₹{{ $pay['daily'] }} = ₹{{ number_format($pay['basic'],2) }}</p>
        <p>Total Incentive + ₹{{ number_format($pay['incentive_total'],2) }}</p>
        <p>Total Advance Payments Made <span style="color:#b91c1c">- ₹{{ number_format($pay['advance_total'],2) }}</span></p>
        <div class="badge ok" style="font-size:18px;padding:12px 18px">Net Payable: ₹{{ number_format($pay['net'],2) }}</div>
    @endif
</div>
@endsection
