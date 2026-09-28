@extends('layouts.app')
@section('content')
<h1>← Reports</h1>
<div class="card" style="background:#111;color:#fff">
    <h2>Custom Report Designer</h2>
    <p>Design your own report templates — choose columns, set order, customize labels, and export in Excel or PDF format.</p>
    <a class="btn light" href="{{ route('reports.tracker') }}">Open Designer / Tracker</a>
</div>
<div class="tabs">
    <a class="active">Attendance & Hours</a>
    <a class="chip">Field Tracking</a>
    <a class="chip">Finance & Payroll</a>
    <a class="chip">HR & Master Data</a>
</div>
<div class="report-grid">
    @foreach([
        ['daywise','Daywise Attendance Report','Generate day-by-day attendance reports'],
        ['multi','Daywise Attendance Report with multiple Punch in/out','View comprehensive punch details'],
        ['overall','Overall Attendance Report','Get monthly summaries or custom range reports'],
        ['detailed','Detailed Attendance Report','Download in-depth attendance trackers'],
        ['hours','Working Hours Report','Analyze the total actual hours worked'],
        ['overtime','Overtime Hours Report','Track extra hours put in by employees'],
    ] as $r)
        <div class="report-card">
            <h3>{{ $r[1] }}</h3>
            <p class="muted">{{ $r[2] }}</p>
            <form method="get" action="{{ route('reports.generate') }}">
                <input type="hidden" name="type" value="{{ $r[0]==='overall'?'overall':'daywise' }}">
                <input type="date" name="from" value="{{ now()->startOfMonth()->toDateString() }}">
                <input type="date" name="to" value="{{ now()->toDateString() }}">
                <button class="btn" style="margin-top:8px">Generate</button>
            </form>
        </div>
    @endforeach
    <div class="report-card">
        <h3>Monthly Summary Sheet Download</h3>
        <a class="btn" href="{{ route('reports.tracker') }}">Open sheet</a>
    </div>
</div>
@endsection
