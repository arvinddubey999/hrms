@extends('layouts.app')
@section('content')
<div class="page-head"><h1>Live Tracking Timeline</h1></div>
<div class="grid-2">
    <div class="card" style="min-height:480px">
        <p class="muted">Map / Satellite</p>
        @if($pings->count())
            <p>Path points: {{ $pings->count() }}</p>
            <ol>
                @foreach($pings->take(40) as $p)
                    <li>{{ $p->pinged_at->format('h:i A') }} — {{ round($p->lat,5) }}, {{ round($p->lng,5) }} {{ $p->inside_geofence?'(inside)':'(outside)' }}</li>
                @endforeach
            </ol>
            <iframe width="100%" height="320" style="border:0;border-radius:12px"
                src="https://maps.google.com/maps?q={{ $pings->last()->lat }},{{ $pings->last()->lng }}&z=14&output=embed"></iframe>
        @else
            <p class="muted">No timeline points for this date. Staff app sends location while Live Tracking is on.</p>
        @endif
    </div>
    <div class="card">
        <form method="get">
            <label>Select Date</label>
            <input type="date" name="date" value="{{ $date }}">
            <label>Employee</label>
            <select name="employee">
                @foreach($employees as $e)
                    <option value="{{ $e->id }}" {{ $employee && $employee->id==$e->id?'selected':'' }}>{{ $e->displayName() }}</option>
                @endforeach
            </select>
            <button class="btn" style="margin-top:12px">Load Timeline</button>
        </form>
        <h3>Timeline Locations</h3>
        <p class="muted">{{ $pings->count() }} locations</p>
        @foreach($pings->reverse()->take(12) as $p)
            <div style="border-left:3px solid {{ $p->inside_geofence?'#16a34a':'#f97316' }};padding-left:10px;margin:10px 0">
                <b>{{ $p->pinged_at->format('g:i A') }}</b>
                <div class="muted">{{ $p->address ?: ($p->lat.', '.$p->lng) }}</div>
            </div>
        @endforeach
    </div>
</div>
@endsection
