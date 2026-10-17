@extends('layouts.app')
@section('content')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

<style>
    .timeline-container {
        display: grid;
        grid-template-columns: 1fr 420px;
        gap: 16px;
        align-items: stretch;
        margin-top: 14px;
        min-height: calc(100vh - 150px);
    }
    @media (max-width: 1024px) {
        .timeline-container {
            grid-template-columns: 1fr;
        }
    }
    .map-panel {
        position: relative;
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        overflow: hidden;
        min-height: 650px;
        display: flex;
        flex-direction: column;
    }
    #timelineMap {
        width: 100%;
        flex: 1;
        min-height: 620px;
        z-index: 1;
    }
    .map-switch-bar {
        position: absolute;
        top: 14px;
        left: 14px;
        z-index: 1000;
        background: rgba(255, 255, 255, 0.95);
        border: 1px solid #d1d5db;
        border-radius: 8px;
        padding: 3px;
        display: flex;
        box-shadow: 0 4px 12px rgba(0,0,0,0.12);
        backdrop-filter: blur(4px);
    }
    .map-switch-btn {
        border: 0;
        background: transparent;
        padding: 5px 14px;
        font-size: 12px;
        font-weight: 700;
        color: #4b5563;
        border-radius: 6px;
        cursor: pointer;
        transition: all 0.15s ease;
    }
    .map-switch-btn.active {
        background: #1f2937;
        color: #ffffff;
        box-shadow: 0 2px 4px rgba(0,0,0,0.15);
    }
    .map-fullscreen-btn {
        position: absolute;
        top: 14px;
        right: 14px;
        z-index: 1000;
        background: rgba(255, 255, 255, 0.95);
        border: 1px solid #d1d5db;
        border-radius: 8px;
        width: 36px;
        height: 36px;
        display: flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        box-shadow: 0 4px 12px rgba(0,0,0,0.12);
        color: #374151;
        font-size: 14px;
        transition: all 0.15s ease;
    }
    .map-fullscreen-btn:hover {
        background: #f3f4f6;
        color: #111827;
    }

    /* Colored Circular Markers matching Screenshots 2, 3 & 4 */
    /* Orange / Yellow for Transit intermediate points */
    .timeline-marker-bubble-orange {
        background: #f59e0b;
        border: 2px solid #ffffff;
        box-shadow: 0 2px 8px rgba(245, 158, 11, 0.5);
        color: #ffffff;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 11px;
        font-weight: 800;
        cursor: pointer;
        transition: transform 0.2s ease, background 0.2s ease;
    }
    .timeline-marker-bubble-orange:hover, .timeline-marker-bubble-orange.active-marker {
        transform: scale(1.35);
        background: #ea580c;
        box-shadow: 0 4px 12px rgba(234, 88, 12, 0.6);
        z-index: 99999 !important;
    }

    /* Green for Start (Location 1) matching Screenshot 4 */
    .timeline-marker-bubble-green {
        background: #16a34a;
        border: 2px solid #ffffff;
        box-shadow: 0 2px 8px rgba(22, 163, 74, 0.5);
        color: #ffffff;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 11px;
        font-weight: 800;
        cursor: pointer;
        transition: transform 0.2s ease;
    }
    .timeline-marker-bubble-green:hover, .timeline-marker-bubble-green.active-marker {
        transform: scale(1.35);
        background: #15803d;
        box-shadow: 0 4px 14px rgba(21, 128, 61, 0.7);
        z-index: 99999 !important;
    }

    /* Blue for End (Location N / latest) matching Screenshot 4 */
    .timeline-marker-bubble-blue {
        background: #0284c7;
        border: 2px solid #ffffff;
        box-shadow: 0 0 0 6px rgba(2, 132, 199, 0.4);
        color: #ffffff;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 11px;
        font-weight: 800;
        cursor: pointer;
        animation: blue-pulse 2s infinite ease-in-out;
    }
    @keyframes blue-pulse {
        0% { box-shadow: 0 0 0 0 rgba(2, 132, 199, 0.7); }
        70% { box-shadow: 0 0 0 14px rgba(2, 132, 199, 0); }
        100% { box-shadow: 0 0 0 0 rgba(2, 132, 199, 0); }
    }
    .timeline-marker-bubble-blue:hover, .timeline-marker-bubble-blue.active-marker {
        transform: scale(1.35);
        background: #0369a1;
        z-index: 99999 !important;
    }

    /* Right Sidebar Cards matching Screenshots */
    .timeline-card {
        border: 1px solid #e5e7eb;
        border-radius: 12px;
        padding: 12px 14px;
        background: #ffffff;
        cursor: pointer;
        transition: all 0.2s ease;
    }
    .timeline-card:hover {
        border-color: #cbd5e1;
        background: #f8fafc;
        transform: translateY(-1px);
        box-shadow: 0 4px 10px rgba(0,0,0,0.04);
    }
    /* Highlighted card when clicked on map matching Screenshot 4 */
    .timeline-card.active-card {
        border-color: #10b981 !important;
        background: #e6f7f2 !important;
        box-shadow: 0 0 0 2px rgba(16, 185, 129, 0.25);
    }

    .badge-number {
        width: 28px;
        height: 28px;
        border-radius: 50%;
        background: #047857;
        color: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 12px;
        flex-shrink: 0;
        box-shadow: 0 2px 4px rgba(4, 120, 87, 0.25);
    }

    /* Custom Floating Calendar matching Screenshot 2 & 3 */
    .calendar-popover {
        position: absolute;
        top: 100%;
        left: 0;
        width: 290px;
        background: #ffffff;
        border: 1px solid #e2e8f0;
        border-radius: 14px;
        box-shadow: 0 16px 36px rgba(0, 0, 0, 0.18);
        padding: 14px;
        z-index: 1050;
        display: none;
        margin-top: 6px;
        animation: popoverFade 0.15s ease-out;
    }
    @keyframes popoverFade {
        from { opacity: 0; transform: translateY(-6px); }
        to { opacity: 1; transform: translateY(0); }
    }
    .cal-header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        font-weight: 800;
        font-size: 13px;
        color: #111827;
        margin-bottom: 12px;
    }
    .cal-nav-btn {
        border: 0;
        background: none;
        font-size: 14px;
        color: #64748b;
        cursor: pointer;
        padding: 4px 8px;
        border-radius: 6px;
    }
    .cal-nav-btn:hover {
        background: #f1f5f9;
        color: #0f172a;
    }
    .cal-weekdays {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        text-align: center;
        font-size: 11px;
        font-weight: 700;
        color: #64748b;
        margin-bottom: 8px;
    }
    .cal-days-grid {
        display: grid;
        grid-template-columns: repeat(7, 1fr);
        gap: 3px;
        text-align: center;
    }
    .cal-day-cell {
        height: 32px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 12px;
        font-weight: 600;
        color: #1e293b;
        cursor: pointer;
        border-radius: 50%;
        transition: all 0.15s;
    }
    .cal-day-cell:hover {
        background: #f1f5f9;
    }
    .cal-day-cell.selected {
        background: #1f2937 !important;
        color: #ffffff !important;
        font-weight: 800;
    }
    .cal-day-cell.empty {
        cursor: default;
        background: transparent !important;
    }

    .custom-leaflet-popup .leaflet-popup-content-wrapper {
        border-radius: 10px;
        padding: 4px;
        box-shadow: 0 8px 24px rgba(0,0,0,0.18);
    }
    .custom-leaflet-popup .leaflet-popup-content {
        margin: 10px 12px;
        font-family: inherit;
        font-size: 12px;
        line-height: 1.4;
    }
</style>

<div class="page-head" style="margin-bottom: 0;">
    <div>
        <h1 style="font-size: 22px; font-weight: 800; color: #111827; margin: 0;">Live Tracking Timeline</h1>
        <p class="muted" style="margin: 2px 0 0 0; font-size: 13px;">View real-time journey route, GPS accuracy, battery status and export timeline reports</p>
    </div>
    <div class="row" style="gap: 8px;">
        <a class="btn light" href="{{ route('tracking.realtime') }}" style="border-radius: 8px; font-weight: 600;">
            <i class="fa-solid fa-location-dot" style="color: #2563eb;"></i> Realtime View
        </a>
    </div>
</div>

<div class="timeline-container">
    <!-- Left Panel: Interactive Map matching Screenshots 2, 3 & 4 -->
    <div class="map-panel" id="mapCard">
        <!-- Top Controls: Map / Satellite switcher -->
        <div class="map-switch-bar">
            <button type="button" class="map-switch-btn active" id="btnMapStreet" onclick="setMapLayer('street')">Map</button>
            <button type="button" class="map-switch-btn" id="btnMapSat" onclick="setMapLayer('satellite')">Satellite</button>
        </div>

        <!-- Fullscreen Button -->
        <button type="button" class="map-fullscreen-btn" onclick="toggleMapFullscreen()" title="Toggle Fullscreen">
            <i class="fa-solid fa-expand" id="fullscreenIcon"></i>
        </button>

        <!-- Leaflet Map Container -->
        <div id="timelineMap"></div>
    </div>

    <!-- Right Panel: Filters, Actions, and Location List matching Screenshots -->
    <div class="card" style="padding: 18px 20px; border: 1px solid #e5e7eb; border-radius: 12px; background: #ffffff; display: flex; flex-direction: column;">
        <h2 style="margin: 0 0 16px 0; font-size: 18px; font-weight: 800; color: #111827;">Live Tracking Timeline</h2>

        <!-- Filter Form -->
        <form method="GET" action="{{ route('tracking.timeline') }}" id="timelineFilterForm">
            <!-- Select Date with Readonly Input & Custom Calendar Popover matching Screenshot 2 & 3 -->
            <div style="margin-bottom: 12px; position: relative;">
                <label style="display: block; font-size: 12px; font-weight: 600; color: #6b7280; margin-bottom: 4px;">Select Date</label>
                
                @php
                    $displayFormattedDate = \Carbon\Carbon::parse($date)->format('D, M d, Y');
                @endphp
                <div style="position: relative;">
                    <input type="text" id="datePickerDisplay" readonly value="{{ $displayFormattedDate }}" onclick="toggleCalendarPopover()" style="width: 100%; border: 1px solid #d1d5db; border-radius: 8px; padding: 8px 12px; font-size: 13px; font-weight: 600; color: #111827; background: #f8fafc; cursor: pointer;">
                    <input type="hidden" name="date" id="selectedDateValue" value="{{ $date }}">
                </div>

                <!-- Custom Calendar Popover matching Screenshot 2 & 3 -->
                <div class="calendar-popover" id="calendarPopover">
                    <div class="cal-header">
                        <button type="button" class="cal-nav-btn" onclick="navCalendarMonth(-1)">&larr;</button>
                        <span id="calMonthYearTitle">October 2026</span>
                        <button type="button" class="cal-nav-btn" onclick="navCalendarMonth(1)">&rarr;</button>
                    </div>
                    <div class="cal-weekdays">
                        <span>Su</span><span>Mo</span><span>Tu</span><span>We</span><span>Th</span><span>Fr</span><span>Sa</span>
                    </div>
                    <div class="cal-days-grid" id="calDaysGrid">
                        <!-- Populated by JavaScript -->
                    </div>
                </div>
            </div>

            <div style="margin-bottom: 14px;">
                <label style="display: block; font-size: 12px; font-weight: 600; color: #6b7280; margin-bottom: 4px;">Employee</label>
                <select name="employee" style="width: 100%; border: 1px solid #d1d5db; border-radius: 8px; padding: 8px 12px; font-size: 13px; font-weight: 500; color: #111827;" onchange="this.form.submit()">
                    @forelse($employees as $e)
                        <option value="{{ $e->id }}" {{ $employee && $employee->id == $e->id ? 'selected' : '' }}>
                            {{ $e->displayName() }}
                        </option>
                    @empty
                        <option value="">No Active Employees</option>
                    @endforelse
                </select>
            </div>

            <!-- Action Buttons Row matching Screenshots -->
            <div style="display: flex; gap: 8px; margin-bottom: 8px; flex-wrap: wrap;">
                <button type="submit" class="btn" style="flex: 1; min-width: 110px; background: #1f2937; color: #ffffff; border: 0; border-radius: 8px; font-weight: 600; font-size: 13px; padding: 9px 12px; display: inline-flex; align-items: center; justify-content: center; gap: 6px;">
                    Load Timeline
                </button>

                @if($employee)
                    <a href="{{ route('tracking.timeline.export', ['employee' => $employee->id, 'date' => $date]) }}" 
                       class="btn light" 
                       title="Download formatted Excel report"
                       style="background: #ffffff; border: 1px solid #d1d5db; color: #374151; border-radius: 8px; font-weight: 600; font-size: 12px; padding: 9px 12px; display: inline-flex; align-items: center; gap: 6px; text-decoration: none;">
                        <i class="fa-solid fa-file-excel" style="color: #16a34a; font-size: 14px;"></i> Download Timeline Report
                    </a>
                @endif

                <button type="button" onclick="fitAllMarkers()" class="btn light" style="background: #ffffff; border: 1px solid #d1d5db; color: #374151; border-radius: 8px; font-weight: 600; font-size: 12px; padding: 9px 12px; display: inline-flex; align-items: center; gap: 6px;">
                    <i class="fa-solid fa-expand"></i> Fit All
                </button>
            </div>
        </form>

        <hr style="margin: 14px 0; border: 0; border-top: 1px solid #f1f5f9;">

        <!-- Header: Timeline Locations & Points Counter matching Screenshots -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
            <div>
                <h3 style="margin: 0; font-size: 15px; font-weight: 700; color: #111827;">Timeline Locations</h3>
                <div style="color: #6b7280; font-size: 12px; margin-top: 2px;">
                    <b>{{ $totalLocations }}</b> locations &bull; <b>{{ $totalPoints }}</b> points
                </div>
            </div>
        </div>

        <!-- Scrollable Locations List matching Screenshot 4 -->
        <div id="timelineScrollList" style="flex: 1; max-height: 520px; overflow-y: auto; padding-right: 4px; display: flex; flex-direction: column; gap: 10px;">
            @php
                // Reverse values so newest/last location is displayed at top (e.g. 18 -> 17 -> ... -> 1)
                $reversedPings = $pings->reverse()->values();
            @endphp

            @forelse($reversedPings as $idx => $p)
                @php
                    $itemNum = $pings->count() - $idx;
                    $isEnd = ($idx === 0);
                    $isStart = ($itemNum === 1);
                    $status = $p->journey_status ?: ($isEnd ? 'End' : ($isStart ? 'Start' : 'Transit'));
                    
                    $batteryVal = $p->battery !== null ? (int)$p->battery : 21;
                    $batteryColor = $batteryVal <= 20 ? '#ef4444' : ($batteryVal <= 50 ? '#f59e0b' : '#16a34a');
                @endphp
                <div class="timeline-card {{ $isEnd ? 'active-card' : '' }}" 
                     id="timeline-card-{{ $itemNum }}" 
                     onclick="focusMarker({{ $itemNum }}, {{ $p->lat }}, {{ $p->lng }})">
                    
                    <div style="display: flex; align-items: flex-start; gap: 12px; margin-bottom: 8px;">
                        <!-- Circle Index Number Badge -->
                        <div class="badge-number">
                            {{ $itemNum }}
                        </div>

                        <div style="flex: 1; min-width: 0;">
                            <div style="display: flex; justify-content: space-between; align-items: center;">
                                <span style="font-weight: 700; font-size: 13px; color: #111827;">
                                    {{ $p->formatted_time }}
                                </span>
                                <span style="font-size: 11px; font-weight: 700; color: {{ $batteryColor }}; display: inline-flex; align-items: center; gap: 4px;">
                                    <i class="fa-solid fa-battery-quarter" style="color: {{ $batteryColor }};"></i>
                                    {{ $p->battery_formatted }}
                                </span>
                            </div>
                            <div style="font-size: 12px; font-weight: 600; color: #64748b; margin-top: 1px;">
                                {{ $status }}
                            </div>
                        </div>
                    </div>

                    <!-- Address string with icon matching Screenshot 4 -->
                    <div style="font-size: 12px; color: #374151; line-height: 1.45; margin-bottom: 8px; display: flex; gap: 6px;">
                        <i class="fa-solid fa-location-dot" style="color: #9ca3af; margin-top: 3px; font-size: 12px; flex-shrink: 0;"></i>
                        <span style="overflow: hidden; text-overflow: ellipsis; display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical;">
                            {{ $p->address ?: 'Unknown location' }}
                        </span>
                    </div>

                    <!-- Points & Accuracy footer row -->
                    <div style="display: flex; justify-content: flex-start; gap: 16px; font-size: 11px; color: #6b7280; border-top: 1px dashed #f1f5f9; padding-top: 6px;">
                        <span>Points: <b style="color: #111827;">{{ $p->points ?: 1 }}</b></span>
                        <span>Accuracy: <b style="color: #111827;">{{ $p->accuracy_formatted }}</b></span>
                    </div>

                    <!-- Stayed duration note / Last tracked note matching User Requirement -->
                    @if($isEnd)
                        <div style="font-size: 11px; color: #059669; font-weight: 600; margin-top: 6px; display: flex; align-items: center; gap: 4px;">
                            <i class="fa-solid fa-check" style="font-size: 10px;"></i> Last tracked location
                        </div>
                    @elseif($status === 'Stayed' || !empty($p->stayed_minutes))
                        <div style="font-size: 11px; color: #0d9488; font-weight: 600; margin-top: 6px; display: flex; align-items: center; gap: 4px;">
                            <i class="fa-regular fa-clock" style="font-size: 11px;"></i> Stayed at this location for {{ $p->stayed_minutes ?: 9 }} minutes
                        </div>
                    @endif
                </div>
            @empty
                <div style="text-align: center; padding: 40px 16px; color: #6b7280;">
                    <i class="fa-solid fa-map-location-dot" style="font-size: 42px; color: #cbd5e1; margin-bottom: 12px;"></i>
                    <div style="font-weight: 700; color: #374151; font-size: 14px;">No timeline locations found</div>
                    <div style="font-size: 12px; margin-top: 4px; color: #9ca3af;">
                        Staff phone pushes tracking points via Live Tracking API. Once tracking points are received, they appear here.
                    </div>
                </div>
            @endforelse
        </div>
    </div>
</div>

@php
    // Prepare markers JSON for Leaflet matching Screenshot 2, 3 & 4
    $timelinePoints = $pings->values()->map(function($p, $idx) use ($pings) {
        $itemNum = $idx + 1;
        $isStart = ($itemNum === 1);
        $isEnd = ($idx === $pings->count() - 1);

        // Marker color type: 'start' (green), 'end' (blue), 'transit' (orange)
        $colorType = $isEnd ? 'end' : ($isStart ? 'start' : 'transit');

        return [
            'id' => $p->id,
            'index' => $itemNum,
            'lat' => (float)$p->lat,
            'lng' => (float)$p->lng,
            'time' => $p->formatted_time,
            'status' => $p->journey_status ?: ($isEnd ? 'End' : ($isStart ? 'Start' : 'Transit')),
            'color_type' => $colorType,
            'address' => $p->address ?: 'Unknown location',
            'points' => $p->points ?: 1,
            'accuracy' => $p->accuracy_formatted,
            'battery' => $p->battery_formatted,
            'is_end' => $isEnd,
            'is_start' => $isStart,
            'maps_url' => "https://www.google.com/maps?q={$p->lat},{$p->lng}",
        ];
    });
@endphp

<script>
    const pointsData = @json($timelinePoints);
    let map = null;
    let streetLayer = null;
    let satLayer = null;
    let currentLayerType = 'street';
    let markersMap = {};
    let circlesMap = {};
    let polyline = null;

    // Calendar state
    let calCurrentDate = new Date("{{ $date }}");

    document.addEventListener('DOMContentLoaded', function () {
        initTimelineMap();
        initCalendarPicker();

        // Close calendar when clicking outside
        document.addEventListener('click', function (e) {
            const popover = document.getElementById('calendarPopover');
            const displayInput = document.getElementById('datePickerDisplay');
            if (popover && !popover.contains(e.target) && e.target !== displayInput) {
                popover.style.display = 'none';
            }
        });
    });

    function initTimelineMap() {
        const defaultLat = pointsData.length > 0 ? pointsData[pointsData.length - 1].lat : 21.1873241;
        const defaultLng = pointsData.length > 0 ? pointsData[pointsData.length - 1].lng : 72.8371532;

        map = L.map('timelineMap', {
            zoomControl: false,
            attributionControl: true
        }).setView([defaultLat, defaultLng], 15);

        L.control.zoom({ position: 'bottomright' }).addTo(map);

        streetLayer = L.tileLayer('https://{s}.google.com/vt/lyrs=m&x={x}&y={y}&z={z}', {
            maxZoom: 20,
            subdomains: ['mt0','mt1','mt2','mt3'],
            attribution: '&copy; Google Maps'
        });

        satLayer = L.tileLayer('https://{s}.google.com/vt/lyrs=y&x={x}&y={y}&z={z}', {
            maxZoom: 20,
            subdomains: ['mt0','mt1','mt2','mt3'],
            attribution: '&copy; Google Maps Satellite'
        });

        streetLayer.addTo(map);

        if (pointsData.length > 0) {
            const latlngs = [];

            pointsData.forEach((pt, index) => {
                const latlng = [pt.lat, pt.lng];
                latlngs.push(latlng);

                // Determine bubble style and color matching Screenshot 4:
                // Start = Green, End = Blue, Intermediate = Orange / Yellow
                let markerClass = 'timeline-marker-bubble-orange';
                let circleColor = '#f59e0b';
                let circleFill = 'rgba(245, 158, 11, 0.35)';
                let circleRadius = 45;

                if (pt.is_end) {
                    markerClass = 'timeline-marker-bubble-blue';
                    circleColor = '#0284c7';
                    circleFill = 'rgba(2, 132, 199, 0.45)';
                    circleRadius = 60;
                } else if (pt.is_start) {
                    markerClass = 'timeline-marker-bubble-green';
                    circleColor = '#16a34a';
                    circleFill = 'rgba(22, 163, 74, 0.45)';
                    circleRadius = 55;
                }

                // Translucent range circle on map matching Screenshot 4
                const circle = L.circle(latlng, {
                    color: circleColor,
                    fillColor: circleFill,
                    fillOpacity: 0.5,
                    weight: 1.5,
                    radius: circleRadius
                }).addTo(map);

                circle.on('click', function () {
                    onMarkerCircleClick(pt.index, pt.lat, pt.lng);
                });
                circlesMap[pt.index] = circle;

                // Center number bubble
                const size = pt.is_end ? 34 : 28;
                const customIcon = L.divIcon({
                    className: '',
                    html: `<div class="${markerClass}" id="map-bubble-${pt.index}" style="width:${size}px; height:${size}px;">${pt.index}</div>`,
                    iconSize: [size, size],
                    iconAnchor: [size / 2, size / 2]
                });

                const marker = L.marker(latlng, { icon: customIcon }).addTo(map);

                marker.on('click', function () {
                    onMarkerCircleClick(pt.index, pt.lat, pt.lng);
                });

                // Popup content
                const popupContent = `
                    <div style="min-width: 220px;">
                        <div style="display:flex; justify-content:space-between; align-items:center; margin-bottom:4px;">
                            <span style="font-weight:700; color:#111; font-size:13px;">#${pt.index} - ${pt.time}</span>
                            <span style="font-size:11px; font-weight:700; color:#ef4444;">${pt.battery}</span>
                        </div>
                        <div style="font-size:11px; font-weight:700; color:#2563eb; margin-bottom:6px;">${pt.status}</div>
                        <div style="font-size:12px; color:#4b5563; margin-bottom:8px; line-height:1.4;">
                            <i class="fa-solid fa-location-dot" style="color:#f97316;"></i> ${pt.address}
                        </div>
                        <div style="display:flex; justify-content:space-between; font-size:11px; color:#6b7280; border-top:1px dashed #e2e8f0; padding-top:6px; margin-bottom:8px;">
                            <span>Points: <b>${pt.points}</b></span>
                            <span>Accuracy: <b>${pt.accuracy}</b></span>
                        </div>
                        <a href="${pt.maps_url}" target="_blank" style="display:inline-block; font-size:11px; color:#0284c7; font-weight:600; text-decoration:underline;">
                            <i class="fa-solid fa-arrow-up-right-from-square"></i> View in Google Maps
                        </a>
                    </div>
                `;

                marker.bindPopup(popupContent, { className: 'custom-leaflet-popup' });
                markersMap[pt.index] = marker;
            });

            // Blue polyline route with direction
            polyline = L.polyline(latlngs, {
                color: '#2563eb',
                weight: 3.5,
                opacity: 0.85,
                lineJoin: 'round',
                smoothFactor: 1.0
            }).addTo(map);

            map.fitBounds(polyline.getBounds(), { padding: [40, 40] });
        }
    }

    // Bidirectional click handler: clicking circle on map highlights corresponding card in history
    function onMarkerCircleClick(index, lat, lng) {
        highlightCard(index);
        if (markersMap[index]) {
            markersMap[index].openPopup();
        }
    }

    function highlightCard(index) {
        document.querySelectorAll('.timeline-card').forEach(el => el.classList.remove('active-card'));
        const card = document.getElementById('timeline-card-' + index);
        if (card) {
            card.classList.add('active-card');
            card.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }
    }

    function focusMarker(index, lat, lng) {
        if (map && markersMap[index]) {
            map.flyTo([lat, lng], 17, { duration: 0.6 });
            markersMap[index].openPopup();
            highlightCard(index);
        }
    }

    function setMapLayer(type) {
        if (type === currentLayerType) return;
        if (type === 'satellite') {
            map.removeLayer(streetLayer);
            satLayer.addTo(map);
            document.getElementById('btnMapSat').classList.add('active');
            document.getElementById('btnMapStreet').classList.remove('active');
            currentLayerType = 'satellite';
        } else {
            map.removeLayer(satLayer);
            streetLayer.addTo(map);
            document.getElementById('btnMapStreet').classList.add('active');
            document.getElementById('btnMapSat').classList.remove('active');
            currentLayerType = 'street';
        }
    }

    function fitAllMarkers() {
        if (polyline && map) {
            map.fitBounds(polyline.getBounds(), { padding: [40, 40] });
        }
    }

    function toggleMapFullscreen() {
        const card = document.getElementById('mapCard');
        const icon = document.getElementById('fullscreenIcon');
        if (!document.fullscreenElement) {
            card.requestFullscreen().catch(() => {});
            icon.classList.remove('fa-expand');
            icon.classList.add('fa-compress');
        } else {
            document.exitFullscreen();
            icon.classList.remove('fa-compress');
            icon.classList.add('fa-expand');
        }
        setTimeout(() => { if (map) map.invalidateSize(); }, 300);
    }

    // --- Custom Calendar Popover matching Screenshot 2 & 3 ---
    function toggleCalendarPopover() {
        const popover = document.getElementById('calendarPopover');
        popover.style.display = (popover.style.display === 'block') ? 'none' : 'block';
        if (popover.style.display === 'block') {
            renderCalendar();
        }
    }

    function navCalendarMonth(step) {
        calCurrentDate.setMonth(calCurrentDate.getMonth() + step);
        renderCalendar();
    }

    function renderCalendar() {
        const monthNames = ["January","February","March","April","May","June","July","August","September","October","November","December"];
        const year = calCurrentDate.getFullYear();
        const month = calCurrentDate.getMonth();

        document.getElementById('calMonthYearTitle').textContent = `${monthNames[month]} ${year}`;

        const firstDayIndex = new Date(year, month, 1).getDay();
        const daysInMonth = new Date(year, month + 1, 0).getDate();

        const grid = document.getElementById('calDaysGrid');
        grid.innerHTML = '';

        // Empty cells before the first day
        for (let i = 0; i < firstDayIndex; i++) {
            const emptyCell = document.createElement('div');
            emptyCell.className = 'cal-day-cell empty';
            grid.appendChild(emptyCell);
        }

        // Current selected date string 'YYYY-MM-DD'
        const currentSelected = document.getElementById('selectedDateValue').value;

        // Day cells
        for (let day = 1; day <= daysInMonth; day++) {
            const dayCell = document.createElement('div');
            dayCell.className = 'cal-day-cell';
            dayCell.textContent = day;

            const mFormatted = String(month + 1).padStart(2, '0');
            const dFormatted = String(day).padStart(2, '0');
            const dateStr = `${year}-${mFormatted}-${dFormatted}`;

            if (dateStr === currentSelected) {
                dayCell.classList.add('selected');
            }

            dayCell.onclick = function () {
                selectCalendarDate(year, month, day);
            };

            grid.appendChild(dayCell);
        }
    }

    function selectCalendarDate(year, month, day) {
        const selectedObj = new Date(year, month, day);
        const days = ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'];
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

        const dayName = days[selectedObj.getDay()];
        const monthName = months[month];
        const mFormatted = String(month + 1).padStart(2, '0');
        const dFormatted = String(day).padStart(2, '0');

        const displayStr = `${dayName}, ${monthName} ${dFormatted}, ${year}`;
        const valStr = `${year}-${mFormatted}-${dFormatted}`;

        document.getElementById('datePickerDisplay').value = displayStr;
        document.getElementById('selectedDateValue').value = valStr;
        document.getElementById('calendarPopover').style.display = 'none';

        // Auto submit form to load timeline
        document.getElementById('timelineFilterForm').submit();
    }
</script>
@endsection
