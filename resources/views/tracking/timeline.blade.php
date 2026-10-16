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

    /* Orange Number Marker on Map matching Screenshot 1 */
    .timeline-marker-bubble {
        background: #f97316;
        border: 2px solid #ffffff;
        box-shadow: 0 2px 8px rgba(0,0,0,0.35);
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
    .timeline-marker-bubble:hover, .timeline-marker-bubble.active-marker {
        transform: scale(1.3);
        background: #ea580c;
        border-color: #fef08a;
        z-index: 99999 !important;
    }
    .timeline-marker-current {
        background: #0284c7;
        border: 2px solid #ffffff;
        box-shadow: 0 0 0 6px rgba(2, 132, 199, 0.35);
        color: #ffffff;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 11px;
        font-weight: 800;
        animation: marker-pulse 2s infinite ease-in-out;
    }
    @keyframes marker-pulse {
        0% { box-shadow: 0 0 0 0 rgba(2, 132, 199, 0.6); }
        70% { box-shadow: 0 0 0 12px rgba(2, 132, 199, 0); }
        100% { box-shadow: 0 0 0 0 rgba(2, 132, 199, 0); }
    }

    /* Right Sidebar Cards */
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
    .timeline-card.active-card {
        border-color: #16a34a;
        background: #f0fdf4;
        box-shadow: 0 0 0 2px rgba(22, 163, 74, 0.2);
    }

    .badge-number {
        width: 28px;
        height: 28px;
        border-radius: 50%;
        background: #16a34a;
        color: #ffffff;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 700;
        font-size: 12px;
        flex-shrink: 0;
        box-shadow: 0 2px 4px rgba(22, 163, 74, 0.25);
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
    <!-- Left Panel: Interactive Map matching Screenshot 1 -->
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

    <!-- Right Panel: Filters, Actions, and Location List matching Screenshot 1 -->
    <div class="card" style="padding: 18px 20px; border: 1px solid #e5e7eb; border-radius: 12px; background: #ffffff; display: flex; flex-direction: column;">
        <h2 style="margin: 0 0 16px 0; font-size: 18px; font-weight: 800; color: #111827;">Live Tracking Timeline</h2>

        <!-- Filter Form -->
        <form method="GET" action="{{ route('tracking.timeline') }}" id="timelineFilterForm">
            <div style="margin-bottom: 12px;">
                <label style="display: block; font-size: 12px; font-weight: 600; color: #6b7280; margin-bottom: 4px;">Select Date</label>
                <input type="date" name="date" value="{{ $date }}" style="width: 100%; border: 1px solid #d1d5db; border-radius: 8px; padding: 8px 12px; font-size: 13px; font-weight: 500; color: #111827;" onchange="this.form.submit()">
            </div>

            <div style="margin-bottom: 14px;">
                <label style="display: block; font-size: 12px; font-weight: 600; color: #6b7280; margin-bottom: 4px;">Employee</label>
                <select name="employee" style="width: 100%; border: 1px solid #d1d5db; border-radius: 8px; padding: 8px 12px; font-size: 13px; font-weight: 500; color: #111827;" onchange="this.form.submit()">
                    @forelse($employees as $e)
                        <option value="{{ $e->id }}" {{ $employee && $employee->id == $e->id ? 'selected' : '' }}>
                            {{ $e->displayName() }} ({{ $e->employee_code ?: 'EMP'.$e->id }})
                        </option>
                    @empty
                        <option value="">No Active Employees</option>
                    @endforelse
                </select>
            </div>

            <!-- Action Buttons Row matching Screenshot 1 -->
            <div style="display: flex; gap: 8px; margin-bottom: 8px; flex-wrap: wrap;">
                <button type="submit" class="btn" style="flex: 1; min-width: 110px; background: #1f2937; color: #ffffff; border: 0; border-radius: 8px; font-weight: 600; font-size: 13px; padding: 9px 12px; display: inline-flex; align-items: center; justify-content: center; gap: 6px;">
                    <i class="fa-solid fa-arrows-rotate" style="font-size: 11px;"></i> Load Timeline
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

        <!-- Header: Timeline Locations & Points Counter matching Screenshot 1 -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
            <div>
                <h3 style="margin: 0; font-size: 15px; font-weight: 700; color: #111827;">Timeline Locations</h3>
                <div style="color: #6b7280; font-size: 12px; margin-top: 2px;">
                    <b>{{ $totalLocations }}</b> locations &bull; <b>{{ $totalPoints }}</b> points
                </div>
            </div>
            @if($totalLocations > 0)
                <span class="badge" style="background: #dcfce7; color: #15803d; font-size: 11px; font-weight: 700; padding: 3px 8px; border-radius: 6px;">
                    <i class="fa-solid fa-route"></i> Track Active
                </span>
            @endif
        </div>

        <!-- Scrollable Locations List matching Screenshot 1 -->
        <div id="timelineScrollList" style="flex: 1; max-height: 520px; overflow-y: auto; padding-right: 4px; display: flex; flex-direction: column; gap: 10px;">
            @php
                // Reverse values so newest/last location is displayed at top (e.g. 119 -> 118 -> ... -> 1)
                $reversedPings = $pings->reverse()->values();
            @endphp

            @forelse($reversedPings as $idx => $p)
                @php
                    $itemNum = $pings->count() - $idx;
                    $isEnd = ($idx === 0);
                    $isStart = ($itemNum === 1);
                    $status = $p->journey_status ?: ($isEnd ? 'End' : ($isStart ? 'Start' : 'Transit'));
                    
                    $statusColor = match(strtolower($status)) {
                        'start' => '#16a34a',
                        'end' => '#ef4444',
                        'stayed' => '#0d9488',
                        default => '#0284c7'
                    };

                    $batteryVal = $p->battery !== null ? (int)$p->battery : 75;
                    $batteryColor = $batteryVal <= 20 ? '#ef4444' : ($batteryVal <= 50 ? '#f59e0b' : '#16a34a');
                @endphp
                <div class="timeline-card" 
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
                                    <i class="fa-solid fa-battery-half" style="color: {{ $batteryColor }};"></i>
                                    {{ $p->battery_formatted }}
                                </span>
                            </div>
                            <div style="font-size: 12px; font-weight: 700; color: {{ $statusColor }}; margin-top: 1px;">
                                {{ $status }}
                            </div>
                        </div>
                    </div>

                    <!-- Address string with icon matching Screenshot 1 -->
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

                    <!-- Note if End or Stayed -->
                    @if($isEnd)
                        <div style="font-size: 11px; color: #16a34a; font-weight: 600; margin-top: 6px; display: flex; align-items: center; gap: 4px;">
                            <i class="fa-solid fa-circle-check" style="font-size: 10px;"></i> Last tracked location
                        </div>
                    @elseif($status === 'Stayed' || $p->stayed_minutes)
                        <div style="font-size: 11px; color: #0d9488; font-weight: 600; margin-top: 6px; display: flex; align-items: center; gap: 4px;">
                            <i class="fa-solid fa-clock" style="font-size: 10px;"></i> Stayed at this location for {{ $p->stayed_minutes ?: 130 }} minutes
                        </div>
                    @endif
                </div>
            @empty
                <div style="text-align: center; padding: 40px 16px; color: #6b7280;">
                    <i class="fa-solid fa-map-location-dot" style="font-size: 42px; color: #cbd5e1; margin-bottom: 12px;"></i>
                    <div style="font-weight: 700; color: #374151; font-size: 14px;">No timeline locations found</div>
                    <div style="font-size: 12px; margin-top: 4px; color: #9ca3af;">
                        Staff phone pushes tracking points via Live Tracking API. Once tracking points are received, they appear here in realtime.
                    </div>
                </div>
            @endforelse
        </div>
    </div>
</div>

@php
    // Prepare markers JSON for Leaflet
    $timelinePoints = $pings->values()->map(function($p, $idx) use ($pings) {
        $itemNum = $idx + 1;
        $isLast = ($idx === $pings->count() - 1);
        return [
            'id' => $p->id,
            'index' => $itemNum,
            'lat' => (float)$p->lat,
            'lng' => (float)$p->lng,
            'time' => $p->formatted_time,
            'status' => $p->journey_status ?: ($isLast ? 'End' : ($idx === 0 ? 'Start' : 'Transit')),
            'address' => $p->address ?: 'Unknown location',
            'points' => $p->points ?: 1,
            'accuracy' => $p->accuracy_formatted,
            'battery' => $p->battery_formatted,
            'is_last' => $isLast,
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
    let polyline = null;

    document.addEventListener('DOMContentLoaded', function () {
        initTimelineMap();
    });

    function initTimelineMap() {
        const defaultLat = pointsData.length > 0 ? pointsData[pointsData.length - 1].lat : 21.1873241;
        const defaultLng = pointsData.length > 0 ? pointsData[pointsData.length - 1].lng : 72.8371532;

        map = L.map('timelineMap', {
            zoomControl: false,
            attributionControl: true
        }).setView([defaultLat, defaultLng], 14);

        L.control.zoom({ position: 'bottomright' }).addTo(map);

        // Google Street Layer
        streetLayer = L.tileLayer('https://{s}.google.com/vt/lyrs=m&x={x}&y={y}&z={z}', {
            maxZoom: 20,
            subdomains: ['mt0','mt1','mt2','mt3'],
            attribution: '&copy; Google Maps'
        });

        // Google Hybrid Satellite Layer
        satLayer = L.tileLayer('https://{s}.google.com/vt/lyrs=y&x={x}&y={y}&z={z}', {
            maxZoom: 20,
            subdomains: ['mt0','mt1','mt2','mt3'],
            attribution: '&copy; Google Maps Satellite'
        });

        streetLayer.addTo(map);

        // Render markers and polyline
        if (pointsData.length > 0) {
            const latlngs = [];

            pointsData.forEach((pt, index) => {
                const latlng = [pt.lat, pt.lng];
                latlngs.push(latlng);

                // Use pulsing blue marker for current/latest point, orange numbered bubble for others
                const isCurrent = pt.is_last;
                const markerClass = isCurrent ? 'timeline-marker-current' : 'timeline-marker-bubble';
                const size = isCurrent ? 34 : 28;

                const customIcon = L.divIcon({
                    className: '',
                    html: `<div class="${markerClass}" id="map-bubble-${pt.index}" style="width:${size}px; height:${size}px;">${pt.index}</div>`,
                    iconSize: [size, size],
                    iconAnchor: [size / 2, size / 2]
                });

                const marker = L.marker(latlng, { icon: customIcon }).addTo(map);

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
                marker.on('click', function () {
                    highlightCard(pt.index);
                });

                markersMap[pt.index] = marker;
            });

            // Draw route polyline in blue matching Screenshot 1
            polyline = L.polyline(latlngs, {
                color: '#2563eb',
                weight: 3.5,
                opacity: 0.85,
                lineJoin: 'round',
                smoothFactor: 1.0
            }).addTo(map);

            // Fit map to show full path
            map.fitBounds(polyline.getBounds(), { padding: [40, 40] });
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

    function focusMarker(index, lat, lng) {
        if (map && markersMap[index]) {
            map.flyTo([lat, lng], 17, { duration: 0.7 });
            markersMap[index].openPopup();
            highlightCard(index);
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

    function toggleMapFullscreen() {
        const card = document.getElementById('mapCard');
        const icon = document.getElementById('fullscreenIcon');
        if (!document.fullscreenElement) {
            card.requestFullscreen().catch(err => {
                console.error(err);
            });
            icon.classList.remove('fa-expand');
            icon.classList.add('fa-compress');
        } else {
            document.exitFullscreen();
            icon.classList.remove('fa-compress');
            icon.classList.add('fa-expand');
        }
        setTimeout(() => { if (map) map.invalidateSize(); }, 300);
    }
</script>
@endsection
