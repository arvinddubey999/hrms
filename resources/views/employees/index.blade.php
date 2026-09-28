@extends('layouts.app')
@section('content')
@php
$hour = (int) now('Asia/Kolkata')->format('G');
$greet = $hour < 12 ? 'Good morning' : ($hour < 17 ? 'Good afternoon' : 'Good evening');
$colors = ['#7c3aed','#2563eb','#059669','#db2777','#ea580c','#4f46e5'];
@endphp
<div class="page-head">
    <h1>Employees</h1>
    <div class="row">
        <a class="btn light" href="{{ route('tracking.realtime') }}">Live View</a>
        <a class="btn" href="{{ route('employees.create') }}">+ Add employee</a>
    </div>
</div>

<div class="card hello">
    <div class="row" style="justify-content:space-between">
        <div>
            <h2>{{ $greet }}</h2>
            <p>Here's the attendance status of employees at</p>
            <div class="co">{{ $setting->company_name }}</div>
        </div>
        <div class="row">
            <span class="badge ok">Live</span>
            <form method="get">
                <input type="date" name="date" value="{{ $date->toDateString() }}" onchange="this.form.submit()">
            </form>
        </div>
    </div>
</div>

<div class="card" style="margin-top:14px">
    <div class="stats">
        <div>
            <strong>Attendance<br>Statistics</strong>
            <div class="muted" style="margin-top:8px">{{ $date->isToday() ? 'Today' : $date->format('d M Y') }}</div>
        </div>
        <div>
            <div class="kpi">
                <div><span class="bar" style="background:#16a34a"></span><small>PRESENT</small><b>{{ $stats['present'] }}</b></div>
                <div><span class="bar" style="background:#ef4444"></span><small>ABSENT</small><b>{{ $stats['absent'] }}</b></div>
                <div><span class="bar" style="background:#9ca3af"></span><small>NOT MARKED</small><b>{{ $stats['not_marked'] }}</b></div>
                <div><span class="bar" style="background:#eab308"></span><small>LATE</small><b>{{ $stats['late'] }}</b></div>
                <div><span class="bar" style="background:#f97316"></span><small>LEAVE</small><b>{{ $stats['leave'] }}</b></div>
                <div><span class="bar" style="background:#fb923c"></span><small>EARLY</small><b>{{ $stats['early'] }}</b></div>
            </div>
            <div class="kpi" style="margin-top:16px">
                <div><small>TOTAL HEADS</small><b>{{ $stats['total'] }}</b></div>
                <div><small>ADMIN</small><b>{{ $stats['admin'] }}</b></div>
                <div><small>MANAGER</small><b>{{ $stats['manager'] }}</b></div>
                <div><small>EMPLOYEE</small><b>{{ $stats['employee'] }}</b></div>
                <div><small>ARCHIVED</small><b>{{ $stats['archived'] }}</b></div>
            </div>
        </div>
    </div>
</div>

<div class="tabs">
    <a class="{{ !$categoryId ? 'active' : '' }}" href="{{ route('attendances.index', ['date'=>$date->toDateString()]) }}">All</a>
    @foreach($categories as $cat)
        <a class="{{ $categoryId==$cat->id ? 'active' : '' }}" href="{{ route('attendances.index', ['date'=>$date->toDateString(),'category'=>$cat->id]) }}">{{ $cat->name }}</a>
    @endforeach
</div>

<div class="card">
    <table class="table">
        <thead>
        <tr>
            <th>Name</th><th>Designation</th><th>Phone</th><th>Department</th><th>Category</th><th>Attendance</th>
        </tr>
        </thead>
        <tbody>
        @foreach($rows as $i => $row)
            @php $u = $row['user']; $color = $colors[$i % count($colors)]; @endphp
            <tr>
                <td>
                    <a class="person" href="{{ route('employees.show', $u) }}">
                        @if($u->profile_photo)
                            <img src="{{ asset('storage/'.$u->profile_photo) }}" alt="Profile Photo" style="width:10%;height:10%;object-fit:cover;border-radius:50%;">
                        @else
                            <span class="dot" style="background:{{ $color }}">{{ $u->initials() }}</span>
                        @endif
                        <!-- <span class="dot" style="background:{{ $color }}">{{ $u->initials() }}</span> -->
                        {{ $u->displayName() }}
                    </a>
                </td>
                <td>{{ $u->designation ?: '-' }}</td>
                <td>{{ $u->phone }}</td>
                <td>{{ $u->department ?: '-' }}</td>
                <td>{{ $u->category->name ?? 'Select Category' }}</td>
                <td>
                    @forelse($row['punches'] as $p)
                        <span class="badge {{ $p->type }}">{{ strtoupper($p->type) }} {{ $p->punched_at->format('g:i A') }}</span>
                    @empty
                        <span class="muted">Not Marked</span>
                    @endforelse
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
</div>
@endsection
