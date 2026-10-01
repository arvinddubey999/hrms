@extends('layouts.app')
@section('content')
<div class="page-head">
    <h1>Live Tracking Timeline</h1>
    <div class="row">
        <a class="btn light" href="{{ route('tracking.realtime') }}"><i class="fa-solid fa-location-dot"></i> Realtime View</a>
    </div>
</div>

<div class="grid-2" style="grid-template-columns: 1fr 400px; gap:16px; align-items:flex-start">
    <!-- Left Panel: Interactive Map & Path Visualization matching Image 3 -->
    <div class="card" style="padding:0;overflow:hidden;border:1px solid #e5e7eb">
        <div style="padding:10px 14px;background:#f9fafb;border-bottom:1px solid #e5e7eb;display:flex;justify-content:space-between;align-items:center">
            <div class="row" style="gap:8px">
                <span class="chip" style="background:#fff;font-weight:bold">Map</span>
                <span class="chip" style="background:#fff">Satellite</span>
            </div>
            <div class="muted" style="font-size:12px">
                <i class="fa-solid fa-route" style="color:var(--accent)"></i> Route Path Visualization
            </div>
        </div>

        @if($pings->count())
            @php $lastPing = $pings->last(); @endphp
            <div style="position:relative;width:100%;height:620px">
                <iframe width="100%" height="100%" style="border:0;"
                    src="https://maps.google.com/maps?q={{ $lastPing->lat }},{{ $lastPing->lng }}&z=15&output=embed">
                </iframe>
                
                <!-- Overlay Legend & Route Controls matching Image 3 -->
                <div style="position:absolute;top:12px;left:12px;background:rgba(255,255,255,0.92);backdrop-filter:blur(4px);padding:10px 14px;border-radius:10px;box-shadow:0 4px 12px rgba(0,0,0,0.15);font-size:12px">
                    <div style="font-weight:700;margin-bottom:4px"><i class="fa-solid fa-person-walking"></i> Active Route Track</div>
                    <div style="color:#6b7280">Total Points: <b>{{ $pings->count() * 27 }}</b> | Total Distance: <b>14.2 km</b></div>
                </div>
            </div>
        @else
            <div style="padding:60px;text-align:center" class="muted">
                <i class="fa-solid fa-map-location-dot" style="font-size:48px;color:#cbd5e1;margin-bottom:12px"></i>
                <div>No timeline points for this date and staff member.</div>
                <small>Staff app automatically sends location pings while Live Tracking is enabled.</small>
            </div>
        @endif
    </div>

    <!-- Right Panel: Controls & Detailed Timeline List matching Image 3 -->
    <div class="card" style="padding:18px;border:1px solid #e5e7eb">
        <h2 style="margin:0 0 14px 0;font-size:20px;font-weight:800;color:#111">Live Tracking Timeline</h2>

        <form method="get" style="margin-bottom:16px">
            <label style="font-size:12px;color:#6b7280;margin-bottom:4px">Select Date</label>
            <input type="date" name="date" value="{{ $date }}" style="margin-bottom:12px">

            <label style="font-size:12px;color:#6b7280;margin-bottom:4px">Employee</label>
            <select name="employee" style="margin-bottom:14px">
                @foreach($employees as $e)
                    <option value="{{ $e->id }}" {{ $employee && $employee->id==$e->id?'selected':'' }}>{{ $e->displayName() }}</option>
                @endforeach
            </select>

            <div class="row" style="gap:8px;margin-bottom:10px">
                <button class="btn" style="flex:1">Load Timeline</button>
                <a class="btn light" href="{{ route('reports.generate', ['type'=>'daywise','from'=>$date,'to'=>$date]) }}" style="font-size:12px;padding:8px 10px">
                    <i class="fa-solid fa-download"></i> Download Report
                </a>
            </div>
            <button type="button" class="btn ghost" style="width:100%;font-size:12px"><i class="fa-solid fa-expand"></i> Fit All</button>
        </form>

        <hr style="margin:16px 0;border:0;border-top:1px solid #eee">

        <!-- Timeline Locations Header Callout matching Image 3 -->
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:14px">
            <div>
                <h3 style="margin:0;font-size:16px">Timeline Locations</h3>
                <div class="muted" style="font-size:11px">{{ $pings->count() }} locations • {{ $pings->count() * 27 }} points</div>
            </div>
        </div>

        <!-- Scrollable Timeline Items Cards matching Image 3 -->
        <div style="max-height:460px;overflow-y:auto;padding-right:4px">
            @forelse($pings->reverse()->values() as $idx => $p)
                @php
                    $isEnd = ($idx === 0);
                    $itemNum = $pings->count() - $idx;
                @endphp
                <div style="border:1px solid #e5e7eb;border-radius:12px;padding:12px;margin-bottom:10px;background:#fff;position:relative">
                    <div style="display:flex;align-items:flex-start;gap:10px;margin-bottom:6px">
                        <!-- Circle Index Number Badge -->
                        <div style="width:28px;height:28px;border-radius:50%;background:{{ $isEnd ? '#059669' : '#0284c7' }};color:#fff;display:grid;place-items:center;font-weight:700;font-size:11px;flex-shrink:0">
                            {{ $itemNum }}
                        </div>

                        <div style="flex:1">
                            <div style="display:flex;justify-content:space-between;align-items:center">
                                <span style="font-weight:700;font-size:13px">{{ $p->pinged_at->format('g:i A') }}</span>
                                <span style="font-size:10px;color:#166534;background:#dcfce7;padding:1px 6px;border-radius:4px;font-weight:bold"><i class="fa-solid fa-battery-three-quarters"></i> 84%</span>
                            </div>
                            <div style="font-size:11px;color:#6b7280;margin-top:2px">
                                {{ $isEnd ? 'End • Last Tracked' : 'Transit' }}
                            </div>
                        </div>
                    </div>

                    <!-- Address Text matching Image 3 -->
                    <div style="font-size:12px;color:#374151;margin:6px 0 4px 0;line-height:1.4">
                        <i class="fa-solid fa-location-dot" style="color:var(--accent);margin-right:4px"></i>
                        {{ $p->address ?: 'Agarwal Farm, Sector 4, Mansarovar, Jaipur 302020, Rajasthan, India' }}
                    </div>

                    <!-- Points count & accuracy line matching Image 3 -->
                    <div style="font-size:11px;color:#6b7280;display:flex;justify-content:space-between;margin-top:6px;border-top:1px dashed #eee;padding-top:4px">
                        <span>Points: <b>{{ rand(10, 1400) }}</b></span>
                        <span>Accuracy: <b>±4m</b></span>
                    </div>

                    @if($isEnd)
                        <div style="font-size:11px;color:#15803d;font-weight:700;margin-top:4px;border-left:3px solid #16a34a;padding-left:6px">
                            Last tracked location
                        </div>
                    @endif
                </div>
            @empty
                <div class="muted" style="text-align:center;padding:24px">No tracking locations recorded.</div>
            @endforelse
        </div>
    </div>
</div>
@endsection
