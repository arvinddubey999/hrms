@extends('layouts.app')
@section('content')
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css" integrity="sha256-p4NxAoJBhIIN+hmNHrzRCf9tD/miZyoHS5obTRR9BMY=" crossorigin="" />
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js" integrity="sha256-20nQCchB9co0qIjJZRGuk2/Z9VM+kNiyxNV1lvTlZBo=" crossorigin=""></script>

<style>
    .geofence-page-container {
        display: flex;
        flex-direction: column;
        gap: 16px;
    }
    .geofence-map-card {
        height: 380px;
        width: 100%;
        border-radius: 14px;
        border: 1px solid #e5e7eb;
        overflow: hidden;
        background: #f8fafc;
        position: relative;
    }
    #geofenceOverviewMap {
        width: 100%;
        height: 100%;
        z-index: 1;
    }
    .geofence-table-card {
        background: #ffffff;
        border: 1px solid #e5e7eb;
        border-radius: 14px;
        padding: 20px;
        box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05);
    }
    .geofence-row {
        cursor: pointer;
        transition: background 0.15s ease;
    }
    .geofence-row:hover {
        background: #f0fdf4 !important;
    }
    .geofence-row.active-row {
        background: #dcfce7 !important;
    }
    .table-action-btn {
        border: 1px solid #e2e8f0;
        background: #ffffff;
        color: #4b5563;
        width: 32px;
        height: 32px;
        border-radius: 8px;
        display: inline-flex;
        align-items: center;
        justify-content: center;
        cursor: pointer;
        transition: all 0.15s ease;
    }
    .table-action-btn:hover {
        background: #f1f5f9;
        color: #111827;
        border-color: #cbd5e1;
    }
    .table-action-btn.delete-btn:hover {
        background: #fee2e2;
        color: #dc2626;
        border-color: #fca5a5;
    }

    /* Modal Overlay & 3-Column Card matching Screenshot 2 */
    .modal-overlay {
        position: fixed;
        inset: 0;
        background: rgba(15, 23, 42, 0.45);
        backdrop-filter: blur(4px);
        display: none;
        align-items: center;
        justify-content: center;
        z-index: 9999;
        padding: 16px;
        box-sizing: border-box;
    }
    .modal-card-clean {
        background: #ffffff;
        border-radius: 16px;
        width: 1060px;
        max-width: 96vw;
        max-height: 90vh;
        overflow-x: hidden !important;
        overflow-y: auto;
        padding: 18px 24px 20px 24px;
        box-shadow: 0 20px 40px rgba(0, 0, 0, 0.12);
        border: 1px solid #e5e7eb;
        animation: modalScale 0.2s ease-out;
        display: flex;
        flex-direction: column;
        box-sizing: border-box;
    }
    @keyframes modalScale {
        from { opacity: 0; transform: scale(0.97); }
        to { opacity: 1; transform: scale(1); }
    }

    /* Force reset all inputs and labels inside this modal to override global app.css */
    .modal-card-clean * {
        box-sizing: border-box;
    }
    .modal-card-clean label {
        display: flex !important;
        margin: 0 !important;
        padding: 0 !important;
        cursor: pointer;
    }
    .modal-card-clean input[type="checkbox"] {
        width: 16px !important;
        min-width: 16px !important;
        max-width: 16px !important;
        height: 16px !important;
        padding: 0 !important;
        margin: 0 !important;
        accent-color: #ea580c;
        cursor: pointer;
        flex-shrink: 0 !important;
        display: inline-block !important;
        vertical-align: middle;
        border: 1.5px solid #cbd5e1;
        border-radius: 4px;
    }

    /* 4-Column Layout */
    .geofence-modal-grid {
        display: grid;
        grid-template-columns: 260px 180px 200px 1fr;
        gap: 12px;
        align-items: stretch;
        width: 100%;
        box-sizing: border-box;
    }
    @media (max-width: 1024px) {
        .geofence-modal-grid {
            grid-template-columns: 1fr 1fr;
        }
    }
    @media (max-width: 640px) {
        .geofence-modal-grid {
            grid-template-columns: 1fr;
        }
    }

    .modal-section-card {
        border: 1px solid #e2e8f0;
        border-radius: 12px;
        padding: 14px 14px 12px 14px;
        background: #ffffff;
        display: flex;
        flex-direction: column;
        height: 385px;
        box-sizing: border-box;
        overflow: hidden;
    }
    .modal-section-header {
        display: flex;
        align-items: center;
        gap: 8px;
        font-size: 13.5px;
        font-weight: 700;
        color: #0f172a;
        margin-bottom: 12px;
        flex-shrink: 0;
    }

    /* Clean subtle custom scrollbars */
    .clean-scrollbar::-webkit-scrollbar {
        width: 5px;
        height: 5px;
    }
    .clean-scrollbar::-webkit-scrollbar-track {
        background: #f8fafc;
        border-radius: 4px;
    }
    .clean-scrollbar::-webkit-scrollbar-thumb {
        background: #cbd5e1;
        border-radius: 4px;
    }
    .clean-scrollbar::-webkit-scrollbar-thumb:hover {
        background: #94a3b8;
    }

    /* Category scroll pills carousel */
    .category-scroll-pills {
        display: flex;
        gap: 6px;
        overflow-x: auto;
        padding-bottom: 4px;
        scrollbar-width: none;
        flex: 1;
    }
    .category-scroll-pills::-webkit-scrollbar {
        display: none;
    }
    .cat-pill {
        background: #f1f5f9;
        color: #475569;
        font-size: 11.5px;
        font-weight: 600;
        padding: 4px 12px;
        border-radius: 16px;
        cursor: pointer;
        white-space: nowrap;
        border: 0;
        transition: all 0.15s ease;
        flex-shrink: 0;
    }
    .cat-pill.active {
        background: #ea580c;
        color: #ffffff;
        font-weight: 700;
    }

    /* Column 2 Category items */
    .cat-row-label {
        display: flex !important;
        align-items: center !important;
        gap: 10px !important;
        padding: 6px 8px !important;
        cursor: pointer;
        border-radius: 6px;
        transition: background 0.12s;
        user-select: none;
    }
    .cat-row-label:hover {
        background: #f8fafc;
    }
    .cat-name-text {
        font-size: 13px;
        font-weight: 500;
        color: #334155;
    }

    /* Column 3 Employee rows */
    .emp-row-card {
        display: flex;
        align-items: center;
        padding: 7px 8px;
        border-radius: 6px;
        transition: background 0.12s;
        min-width: 0;
    }
    .emp-row-card:hover {
        background: #f8fafc;
    }
    .emp-row-label {
        display: flex !important;
        align-items: flex-start !important;
        gap: 10px !important;
        width: 100% !important;
        min-width: 0 !important;
        cursor: pointer;
        user-select: none;
    }
    .emp-row-info {
        flex: 1;
        min-width: 0;
        display: flex;
        flex-direction: column;
        gap: 2px;
    }
    .emp-row-head {
        display: flex;
        align-items: center;
        gap: 8px;
        min-width: 0;
    }
    .emp-name-text {
        font-size: 12.5px;
        font-weight: 700;
        color: #1e293b;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }
    .emp-badge {
        font-size: 10px;
        font-weight: 700;
        padding: 2px 7px;
        border-radius: 10px;
        text-transform: uppercase;
        letter-spacing: 0.3px;
        flex-shrink: 0;
    }
    .emp-badge.incharge {
        background: #e0f2fe;
        color: #0284c7;
    }
    .emp-badge.staff {
        background: #f1f5f9;
        color: #64748b;
    }
    .emp-sub-dept {
        font-size: 11px;
        color: #64748b;
        white-space: nowrap;
        overflow: hidden;
        text-overflow: ellipsis;
    }

    /* Inputs in settings column */
    .modal-form-select {
        width: 100% !important;
        border: 1px solid #d1d5db !important;
        border-radius: 8px !important;
        padding: 7px 10px !important;
        font-size: 12.5px !important;
        font-weight: 500 !important;
        color: #111827 !important;
        background: #ffffff !important;
        outline: none;
        height: 36px !important;
    }
    .modal-form-textarea {
        width: 100% !important;
        border: 1px solid #d1d5db !important;
        border-radius: 8px !important;
        padding: 8px 10px !important;
        font-size: 12.5px !important;
        color: #111827 !important;
        background: #ffffff !important;
        resize: none !important;
        height: 70px !important;
        outline: none;
    }
    .modal-form-input {
        width: 100% !important;
        border: 1px solid #d1d5db !important;
        border-radius: 8px !important;
        padding: 7px 10px !important;
        font-size: 13px !important;
        font-weight: 600 !important;
        color: #111827 !important;
        background: #ffffff !important;
        outline: none;
        height: 36px !important;
    }
    .modal-btn-confirm {
        background: #1e293b;
        color: #ffffff;
        border: 0;
        border-radius: 24px;
        padding: 9px 34px;
        font-size: 13px;
        font-weight: 700;
        cursor: pointer;
        box-shadow: 0 4px 12px rgba(15, 23, 42, 0.2);
        transition: background 0.15s, transform 0.15s;
    }
    .modal-btn-confirm:hover {
        background: #0f172a;
        transform: translateY(-1px);
    }
</style>

<div class="geofence-page-container">
    <!-- Top Half: Map View showing configured Geofences matching Screenshot 3 -->
    <div class="geofence-map-card">
        <div id="geofenceOverviewMap"></div>
        <div style="position: absolute; top: 12px; right: 12px; z-index: 1000; background: rgba(255,255,255,0.95); padding: 6px 12px; border-radius: 8px; font-size: 12px; font-weight: 700; border: 1px solid #d1d5db; box-shadow: 0 2px 6px rgba(0,0,0,0.1);">
            <i class="fa-solid fa-draw-polygon" style="color: #ea580c;"></i> {{ $geofences->count() }} Geofences Configured
        </div>
    </div>

    <!-- Bottom Half: Manage Geo-fences Table matching Screenshot 3 -->
    <div class="geofence-table-card">
        <div style="display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 16px; flex-wrap: wrap; gap: 12px;">
            <div>
                <h2 style="margin: 0 0 4px 0; font-size: 18px; font-weight: 800; color: #111827;">Manage Geo-fences</h2>
                <div style="font-size: 13px; color: #6b7280;">Select a geofence to highlight it on the map. You can also edit or delete existing fences.</div>
            </div>
            <button type="button" class="btn" onclick="openAddGeofenceModal()" style="background: #111827; color: #ffffff; border: 0; border-radius: 8px; font-weight: 700; font-size: 13px; padding: 9px 16px; display: inline-flex; align-items: center; gap: 6px;">
                <i class="fa-solid fa-plus"></i> Add New Geo Fence
            </button>
        </div>

        <div style="overflow-x: auto;">
            <table class="table" style="width: 100%; border-collapse: collapse;">
                <thead>
                    <tr style="background: #f8fafc; border-bottom: 2px solid #e2e8f0; text-align: left; font-size: 12.5px; color: #475569;">
                        <th style="padding: 10px 14px;">Address</th>
                        <th style="padding: 10px 14px;">Latitude</th>
                        <th style="padding: 10px 14px;">Longitude</th>
                        <th style="padding: 10px 14px;">Radius (m)</th>
                        <th style="padding: 10px 14px;">Company / Department</th>
                        <th style="padding: 10px 14px;">Status</th>
                        <th style="padding: 10px 14px; text-align: right;">Actions</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($geofences as $fence)
                        @php
                            $compNames = [];
                            if (!empty($fence->assigned_companies) && is_array($fence->assigned_companies)) {
                                $compNames = $companies->whereIn('id', $fence->assigned_companies)->pluck('name')->all();
                            } elseif ($fence->company) {
                                $compNames = [$fence->company->name];
                            }

                            $deptNames = [];
                            if (!empty($fence->assigned_departments) && is_array($fence->assigned_departments)) {
                                $deptNames = $departments->whereIn('id', $fence->assigned_departments)->pluck('name')->all();
                            }

                            $compDisplay = !empty($compNames) ? implode(', ', $compNames) : 'All Companies';
                            $deptDisplay = !empty($deptNames) ? implode(', ', $deptNames) : 'All Departments';
                        @endphp
                        <tr class="geofence-row" id="row-fence-{{ $fence->id }}" onclick="highlightGeofence({{ $fence->id }}, {{ $fence->latitude }}, {{ $fence->longitude }}, {{ $fence->radius }})">
                            <td style="padding: 12px 14px; font-weight: 600; color: #1e293b; max-width: 380px;">
                                <div style="display: flex; align-items: flex-start; gap: 8px;">
                                    <i class="fa-solid fa-location-dot" style="color: #ef4444; margin-top: 3px;"></i>
                                    <div>
                                        <div style="font-weight: 700; color: #0f172a;">{{ $fence->name }}</div>
                                        <div style="font-size: 12px; color: #64748b; font-weight: normal;">{{ $fence->address ?: 'Address not specified (Current Location)' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td style="padding: 12px 14px; font-family: monospace; font-size: 13px; color: #334155;">{{ round($fence->latitude, 6) }}</td>
                            <td style="padding: 12px 14px; font-family: monospace; font-size: 13px; color: #334155;">{{ round($fence->longitude, 6) }}</td>
                            <td style="padding: 12px 14px; font-weight: 700; color: #0f172a;">{{ $fence->radius }}</td>
                            <td style="padding: 12px 14px; color: #475569; font-size: 12.5px;">
                                <div style="font-weight: 700; color: #1e293b;">{{ $compDisplay }}</div>
                                <div style="font-size: 11.5px; color: #64748b;">{{ $deptDisplay }}</div>
                            </td>
                            <td style="padding: 12px 14px;">
                                <span style="background: {{ $fence->status === 'active' ? '#dcfce7' : '#fee2e2' }}; color: {{ $fence->status === 'active' ? '#15803d' : '#b91c1c' }}; font-size: 11.5px; font-weight: 700; padding: 2px 8px; border-radius: 6px;">
                                    {{ $fence->status }}
                                </span>
                            </td>
                            <td style="padding: 12px 14px; text-align: right;" onclick="event.stopPropagation()">
                                <div style="display: inline-flex; gap: 6px;">
                                    <button type="button" class="table-action-btn" onclick="editGeofence({{ json_encode($fence) }})" title="Edit Geofence">
                                        <i class="fa-solid fa-pencil" style="font-size: 12px;"></i>
                                    </button>
                                    <form method="POST" action="{{ route('tracking.geofences.destroy', $fence->id) }}" onsubmit="return confirm('Are you sure you want to delete this Geo-fence location?')" style="display:inline; margin:0;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="table-action-btn delete-btn" title="Delete Geofence">
                                            <i class="fa-solid fa-trash-can" style="font-size: 12px;"></i>
                                        </button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" style="padding: 30px; text-align: center; color: #94a3b8;">
                                <i class="fa-solid fa-draw-polygon" style="font-size: 36px; color: #cbd5e1; margin-bottom: 8px;"></i>
                                <div>No Geo-fences added yet. Click <b>+ Add New Geo Fence</b> to configure one.</div>
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- Modal: Configure New Geofence EXACTLY matching Screenshot 2 -->
<div class="modal-overlay" id="geofenceModal">
    <div class="modal-card-clean" id="geofenceModalCard">
        <!-- Top Header row matching Screenshot 2 -->
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 14px; flex-shrink: 0;">
            <div style="display: flex; align-items: center; gap: 10px;">
                <div style="background: #ffedd5; width: 32px; height: 32px; border-radius: 8px; display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                    <i class="fa-solid fa-crosshairs" style="color: #ea580c; font-size: 16px;"></i>
                </div>
                <h3 id="modalHeading" style="margin: 0; font-size: 16.5px; font-weight: 800; color: #1e293b;">Configure New Geofence</h3>
            </div>
            <div style="display: flex; align-items: center; gap: 6px;">
                <button type="button" class="btn light" onclick="closeGeofenceModal()" style="border-radius: 8px; font-size: 12px; padding: 5px 12px; font-weight: 600; border: 1px solid #e2e8f0; background: #fff; color: #334155;">
                    Minimize Form
                </button>
                <span style="color: #cbd5e1; font-size: 13px; margin: 0 2px;">|</span>
                <button type="button" onclick="closeGeofenceModal()" style="border: 0; background: transparent; font-size: 12px; font-weight: 600; color: #334155; cursor: pointer; padding: 5px 8px;">
                    Close
                </button>
            </div>
        </div>

        <form id="geofenceForm" method="POST" action="{{ route('tracking.geofences.store') }}" style="display: flex; flex-direction: column; margin: 0;">
            @csrf
            <input type="hidden" name="_method" id="formMethod" value="POST">
            <input type="hidden" name="company_id" id="fieldCompanyId" value="">

            <!-- 4 Column Main Grid: Settings, Companies, Departments, Employees -->
            <div class="geofence-modal-grid">
                <!-- Column 1: Settings -->
                <div class="modal-section-card">
                    <div class="modal-section-header">
                        <i class="fa-solid fa-crosshairs" style="color: #ea580c; font-size: 14px;"></i>
                        <span>Settings</span>
                    </div>

                    <div class="clean-scrollbar" style="flex: 1; overflow-y: auto; padding-right: 2px; display: flex; flex-direction: column; justify-content: space-between;">
                        <div>
                            <!-- Want to add location? -->
                            <div style="margin-bottom: 12px;">
                                <div style="font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 5px;">Want to add location?</div>
                                <select id="locationModeSelect" class="modal-form-select" onchange="handleLocationModeChange()">
                                    <option value="current">Current Location</option>
                                    <option value="away">Away from location</option>
                                </select>
                            </div>

                            <!-- Search Location Group (visible when Away is selected) -->
                            <div id="searchLocationGroup" style="display: none; margin-bottom: 12px; position: relative;">
                                <div style="font-size: 12px; font-weight: 600; color: #2563eb; margin-bottom: 5px; display: flex; justify-content: space-between; align-items: center;">
                                    <span>Search Location / Landmark (India / Surat)</span>
                                    <span style="font-size: 11px; font-weight: normal; color: #64748b"><i class="fa-solid fa-location-crosshairs"></i> India & Local Markets</span>
                                </div>
                                <input type="text" id="locationSearchInput" class="modal-form-input" placeholder="Type market, landmark, or area (e.g. Kohinoor Market, Ring Road, Surat)..." oninput="searchLocationQuery(this.value)">
                                <div id="locationSuggestList" style="position: absolute; top: 100%; left: 0; right: 0; background: #fff; border: 1px solid #cbd5e1; border-radius: 8px; box-shadow: 0 10px 25px rgba(0,0,0,0.18); max-height: 220px; overflow-y: auto; z-index: 1000; display: none;"></div>
                            </div>

                            <!-- Geofence Address -->
                            <div style="margin-bottom: 12px;">
                                <div style="font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 5px;">Geofence Address</div>
                                <textarea name="address" id="fieldAddress" class="modal-form-textarea" placeholder="Enter address or landmark..."></textarea>
                            </div>

                            <!-- Geofence Radius (meters) -->
                            <div style="margin-bottom: 10px;">
                                <div style="font-size: 12px; font-weight: 600; color: #374151; margin-bottom: 5px; display: flex; align-items: center; gap: 6px;">
                                    <i class="fa-regular fa-circle-dot" style="color: #ea580c; border: 1px dashed #ea580c; border-radius: 50%; padding: 1px; font-size: 11px;"></i>
                                    <span>Geofence Radius (meters)</span>
                                </div>
                                <input type="number" name="radius" id="fieldRadius" class="modal-form-input" value="100" min="5" max="5000" required>
                            </div>

                            <input type="hidden" name="latitude" id="fieldLatitude" value="21.148127">
                            <input type="hidden" name="longitude" id="fieldLongitude" value="72.815560">
                        </div>

                        <!-- Bottom Lat/Lng Display matching Screenshot 2 -->
                        <div style="font-size: 11px; font-weight: 700; color: #334155; font-family: -apple-system, BlinkMacSystemFont, Segoe UI, Roboto, monospace; border-top: 1px solid #f1f5f9; padding-top: 8px; margin-top: 6px;">
                            LAT: <span id="dispLat">21.148127</span> | LONG: <span id="dispLng">72.815560</span>
                        </div>
                    </div>
                </div>

                <!-- Column 2: Companies (X selected) -->
                <div class="modal-section-card">
                    <div class="modal-section-header">
                        <i class="fa-solid fa-building" style="color: #ea580c; font-size: 14px;"></i>
                        <span>Companies (<span id="compSelectedCount">0</span> selected)</span>
                    </div>

                    <div class="clean-scrollbar" style="flex: 1; overflow-y: auto; display: flex; flex-direction: column; gap: 3px; padding-right: 4px;">
                        @foreach($companies as $comp)
                            <label class="cat-row-label">
                                <input type="checkbox" name="assigned_companies[]" value="{{ $comp->id }}" class="comp-checkbox" data-comp-id="{{ $comp->id }}" onchange="handleCompanyCheck({{ $comp->id }}, this.checked)">
                                <span class="cat-name-text">{{ $comp->name }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <!-- Column 3: Departments (X selected) -->
                <div class="modal-section-card">
                    <div class="modal-section-header">
                        <i class="fa-solid fa-sitemap" style="color: #0f172a; font-size: 14px;"></i>
                        <span>Departments (<span id="deptSelectedCount">0</span> selected)</span>
                    </div>

                    <div class="clean-scrollbar" style="flex: 1; overflow-y: auto; display: flex; flex-direction: column; gap: 3px; padding-right: 4px;">
                        @foreach($departments as $dept)
                            <label class="cat-row-label">
                                <input type="checkbox" name="assigned_departments[]" value="{{ $dept->id }}" class="dept-checkbox" data-dept-id="{{ $dept->id }}" onchange="handleDepartmentCheck({{ $dept->id }}, this.checked)">
                                <span class="cat-name-text">{{ $dept->name }}</span>
                            </label>
                        @endforeach
                    </div>
                </div>

                <!-- Column 4: Select Employees (X selected) -->
                <div class="modal-section-card">
                    <div class="modal-section-header">
                        <i class="fa-solid fa-users" style="color: #ea580c; font-size: 14px;"></i>
                        <span>Select Employees (<span id="empSelectedCount">0</span> selected)</span>
                    </div>

                    <!-- Department Filter Carousel with arrows -->
                    <div style="display: flex; align-items: center; gap: 4px; margin-bottom: 2px;">
                        <button type="button" onclick="scrollDeptPills(-1)" style="border:0; background:none; color:#64748b; cursor:pointer; font-size:12px; padding:2px 4px;">&#9664;</button>
                        <div class="category-scroll-pills" id="deptPillsContainer">
                            <button type="button" class="cat-pill active" onclick="filterEmployeesByDept('all', this)">All</button>
                            @foreach($departments as $dept)
                                @php $empCount = $employees->where('department_id', $dept->id)->count(); @endphp
                                @if($empCount > 0)
                                    <button type="button" class="cat-pill" onclick="filterEmployeesByDept({{ $dept->id }}, this)">
                                        {{ $dept->name }} ({{ $empCount }})
                                    </button>
                                @endif
                            @endforeach
                        </div>
                        <button type="button" onclick="scrollDeptPills(1)" style="border:0; background:none; color:#64748b; cursor:pointer; font-size:12px; padding:2px 4px;">&#9654;</button>
                    </div>

                    <!-- Pill Slider Bar -->
                    <div style="display: flex; justify-content: center; margin: 2px 0 8px 0;">
                        <div style="width: 44px; height: 4px; background: #94a3b8; border-radius: 4px;"></div>
                    </div>

                    <!-- Search Filter + All + Clear row -->
                    <div style="display: flex; gap: 6px; align-items: center; margin-bottom: 8px;">
                        <div style="flex: 1;">
                            <input type="text" id="empSearchInput" placeholder="Filter employees..." oninput="searchEmployeesInList(this.value)" style="width: 100% !important; border: 1px solid #d1d5db !important; border-radius: 8px !important; padding: 6px 10px !important; font-size: 12px !important; height: 32px !important; outline: none; background: #fff !important;">
                        </div>
                        <button type="button" class="btn light" onclick="selectAllEmployees(true)" style="padding: 0 12px; height: 32px; font-size: 11.5px; font-weight: 600; border-radius: 8px; border: 1px solid #e2e8f0; background: #fff; color: #334155; cursor: pointer;">All</button>
                        <button type="button" class="btn light" onclick="selectAllEmployees(false)" style="padding: 0 12px; height: 32px; font-size: 11.5px; font-weight: 600; border-radius: 8px; border: 1px solid #e2e8f0; background: #fff; color: #334155; cursor: pointer;">Clear</button>
                    </div>

                    <!-- Scrollable Employee Rows -->
                    <div id="empListContainer" class="clean-scrollbar" style="flex: 1; overflow-y: auto; display: flex; flex-direction: column; gap: 3px; padding-right: 4px;">
                        @foreach($employees as $emp)
                            @php
                                $badgeClass = in_array(strtolower($emp->role ?? ''), ['admin', 'manager', 'supervisor', 'floor incharge']) ? 'incharge' : 'staff';
                                $badgeText = $emp->role ? strtoupper($emp->role) : 'Staff';
                            @endphp
                            <div class="emp-row-card emp-card" data-dept-id="{{ $emp->department_id }}" data-comp-id="{{ $emp->company_id }}" data-emp-name="{{ strtolower($emp->displayName()) }}">
                                <label class="emp-row-label">
                                    <input type="checkbox" name="assigned_employees[]" value="{{ $emp->id }}" class="emp-checkbox emp-for-dept-{{ $emp->department_id }} emp-for-comp-{{ $emp->company_id }}" onchange="updateEmpCount()">
                                    <div class="emp-row-info">
                                        <div class="emp-row-head">
                                            <span class="emp-name-text">{{ $emp->displayName() }}</span>
                                            <span class="emp-badge {{ $badgeClass }}">{{ $badgeText }}</span>
                                        </div>
                                        <div class="emp-sub-dept">{{ $emp->department?->name ?? 'General' }} <span style="color:#94a3b8">({{ $emp->company?->name ?? 'Tulsi Fabrics' }})</span></div>
                                    </div>
                                </label>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

            <!-- Bottom Center Confirm Location Button matching Screenshot 2 -->
            <div style="text-align: center; margin-top: 16px; flex-shrink: 0;">
                <button type="submit" class="modal-btn-confirm">
                    Confirm Location
                </button>
            </div>
        </form>
    </div>
</div>

@php
    $geofencesJson = $geofences->map(function($g) {
        return [
            'id' => $g->id,
            'name' => $g->name,
            'company_id' => $g->company_id,
            'assigned_companies' => $g->assigned_companies ?: ($g->company_id ? [$g->company_id] : []),
            'address' => $g->address,
            'latitude' => (float)$g->latitude,
            'longitude' => (float)$g->longitude,
            'radius' => (int)$g->radius,
            'category' => $g->category,
            'assigned_categories' => $g->assigned_categories ?: [],
            'assigned_departments' => $g->assigned_departments ?: [],
            'assigned_employees' => $g->assigned_employees ?: [],
            'status' => $g->status,
        ];
    });
@endphp

<script>
    const fencesData = @json($geofencesJson);
    let overviewMap = null;
    let overviewMarkers = {};
    let overviewCircles = {};

    document.addEventListener('DOMContentLoaded', function () {
        initOverviewMap();
    });

    function initOverviewMap() {
        const defaultLat = fencesData.length > 0 ? fencesData[0].latitude : 21.1882;
        const defaultLng = fencesData.length > 0 ? fencesData[0].longitude : 72.8377;

        overviewMap = L.map('geofenceOverviewMap', {
            zoomControl: false
        }).setView([defaultLat, defaultLng], 14);

        L.control.zoom({ position: 'bottomright' }).addTo(overviewMap);

        // Google Maps Street Layer
        L.tileLayer('https://{s}.google.com/vt/lyrs=m&x={x}&y={y}&z={z}', {
            maxZoom: 20,
            subdomains: ['mt0','mt1','mt2','mt3'],
            attribution: '&copy; Google Maps'
        }).addTo(overviewMap);

        // Render each geofence
        if (fencesData.length > 0) {
            const bounds = [];
            fencesData.forEach(fence => {
                const latlng = [fence.latitude, fence.longitude];
                bounds.push(latlng);

                const circle = L.circle(latlng, {
                    color: '#ea580c',
                    fillColor: '#f97316',
                    fillOpacity: 0.28,
                    weight: 2,
                    radius: fence.radius
                }).addTo(overviewMap);

                const marker = L.marker(latlng).addTo(overviewMap);
                marker.bindPopup(`
                    <div style="font-family:inherit; min-width:180px;">
                        <h4 style="margin:0 0 4px 0; font-size:14px; font-weight:800; color:#0f172a;">${fence.name}</h4>
                        <div style="font-size:12px; color:#475569; margin-bottom:4px;">${fence.address || 'No address specified'}</div>
                        <div style="font-size:11px; font-weight:700; color:#ea580c;">Radius: ${fence.radius} meters</div>
                    </div>
                `);

                overviewMarkers[fence.id] = marker;
                overviewCircles[fence.id] = circle;
            });

            overviewMap.fitBounds(bounds, { padding: [40, 40] });
        }
    }

    function highlightGeofence(id, lat, lng, radius) {
        document.querySelectorAll('.geofence-row').forEach(r => r.classList.remove('active-row'));
        const row = document.getElementById('row-fence-' + id);
        if (row) row.classList.add('active-row');

        if (overviewMap && overviewMarkers[id]) {
            overviewMap.flyTo([lat, lng], 16, { duration: 0.6 });
            overviewMarkers[id].openPopup();
        }
    }

    function openAddGeofenceModal() {
        document.getElementById('modalHeading').textContent = 'Configure New Geofence';
        document.getElementById('geofenceForm').action = "{{ route('tracking.geofences.store') }}";
        document.getElementById('formMethod').value = 'POST';

        document.getElementById('fieldAddress').value = '';
        document.getElementById('fieldRadius').value = '100';
        document.getElementById('locationModeSelect').value = 'current';
        handleLocationModeChange();

        document.querySelectorAll('.comp-checkbox').forEach(c => c.checked = false);
        document.querySelectorAll('.dept-checkbox').forEach(c => c.checked = false);
        document.querySelectorAll('.emp-checkbox').forEach(c => c.checked = false);
        updateCompCount();
        updateDeptCount();
        updateEmpCount();

        document.getElementById('geofenceModal').style.display = 'flex';
    }

    function editGeofence(fence) {
        document.getElementById('modalHeading').textContent = 'Edit Geofence: ' + (fence.name || 'Geofence');
        document.getElementById('geofenceForm').action = "/tracking/geofences/" + fence.id;
        document.getElementById('formMethod').value = 'PUT';

        document.getElementById('fieldAddress').value = fence.address || '';
        document.getElementById('fieldRadius').value = fence.radius;
        document.getElementById('fieldLatitude').value = fence.latitude;
        document.getElementById('fieldLongitude').value = fence.longitude;
        document.getElementById('dispLat').textContent = fence.latitude;
        document.getElementById('dispLng').textContent = fence.longitude;

        const assignedComps = fence.assigned_companies || (fence.company_id ? [fence.company_id] : []);
        const assignedDepts = fence.assigned_departments || [];
        const assignedEmps = fence.assigned_employees || [];

        document.querySelectorAll('.comp-checkbox').forEach(c => {
            c.checked = assignedComps.includes(parseInt(c.value)) || assignedComps.includes(String(c.value));
        });

        document.querySelectorAll('.dept-checkbox').forEach(c => {
            c.checked = assignedDepts.includes(parseInt(c.value)) || assignedDepts.includes(String(c.value));
        });

        document.querySelectorAll('.emp-checkbox').forEach(c => {
            c.checked = assignedEmps.includes(parseInt(c.value)) || assignedEmps.includes(String(c.value));
        });

        updateCompCount();
        updateDeptCount();
        updateEmpCount();

        document.getElementById('geofenceModal').style.display = 'flex';
    }

    function closeGeofenceModal() {
        document.getElementById('geofenceModal').style.display = 'none';
    }

    function handleLocationModeChange() {
        const mode = document.getElementById('locationModeSelect').value;
        const searchGroup = document.getElementById('searchLocationGroup');

        if (mode === 'current') {
            searchGroup.style.display = 'none';
            if (navigator.geolocation) {
                navigator.geolocation.getCurrentPosition(pos => {
                    const lat = pos.coords.latitude.toFixed(6);
                    const lng = pos.coords.longitude.toFixed(6);
                    document.getElementById('fieldLatitude').value = lat;
                    document.getElementById('fieldLongitude').value = lng;
                    document.getElementById('dispLat').textContent = lat;
                    document.getElementById('dispLng').textContent = lng;
                }, () => {
                    // Default Surat coords if location blocked
                    document.getElementById('fieldLatitude').value = '21.148127';
                    document.getElementById('fieldLongitude').value = '72.815560';
                    document.getElementById('dispLat').textContent = '21.148127';
                    document.getElementById('dispLng').textContent = '72.815560';
                });
            }
        } else {
            searchGroup.style.display = 'block';
        }
    }

    let suggestTimeout = null;
    function searchLocationQuery(query) {
        clearTimeout(suggestTimeout);
        const list = document.getElementById('locationSuggestList');
        if (!query || query.trim().length < 2) {
            if (list) list.style.display = 'none';
            return;
        }

        suggestTimeout = setTimeout(async () => {
            const q = query.trim();
            list.innerHTML = '<div style="padding:10px 14px;font-size:12px;color:#64748b"><i class="fa-solid fa-spinner fa-spin" style="color:#2563eb;margin-right:6px"></i> Searching Indian places & Surat markets for "' + escapeHtml(q) + '"...</div>';
            list.style.display = 'block';

            let items = [];

            // 1. Photon (Komoot OSM) geocoder with Surat, Gujarat, India geo-bias (Lat: 21.1702, Lon: 72.8311)
            try {
                const photonUrl = `https://photon.komoot.io/api/?q=${encodeURIComponent(q)}&lat=21.1702&lon=72.8311&limit=10`;
                const pRes = await fetch(photonUrl);
                if (pRes.ok) {
                    const data = await pRes.json();
                    if (data && data.features) {
                        data.features.forEach(f => {
                            const p = f.properties || {};
                            const coords = f.geometry ? f.geometry.coordinates : null;
                            if (coords && coords.length >= 2) {
                                const nameParts = [p.name, p.street, p.district, p.city, p.state, p.country].filter(Boolean);
                                const label = nameParts.join(', ');
                                items.push({
                                    lat: parseFloat(coords[1]).toFixed(6),
                                    lon: parseFloat(coords[0]).toFixed(6),
                                    title: p.name || 'Location',
                                    label: label || p.name || 'Location'
                                });
                            }
                        });
                    }
                }
            } catch (e) {}

            // 2. OpenStreetMap Nominatim with India countrycode filter and Surat/Gujarat viewbox priority
            if (items.length < 5) {
                try {
                    const nomUrl = `https://nominatim.openstreetmap.org/search?format=json&countrycodes=in&viewbox=72.6,21.0,73.1,21.4&bounded=0&q=${encodeURIComponent(q)}&limit=8&addressdetails=1`;
                    const nRes = await fetch(nomUrl);
                    if (nRes.ok) {
                        const data = await nRes.json();
                        if (Array.isArray(data)) {
                            data.forEach(r => {
                                const title = r.display_name.split(',')[0] || r.display_name;
                                items.push({
                                    lat: parseFloat(r.lat).toFixed(6),
                                    lon: parseFloat(r.lon).toFixed(6),
                                    title: title,
                                    label: r.display_name
                                });
                            });
                        }
                    }
                } catch (e) {}
            }

            // 3. Fallback: If still few results and user did not type "surat", search with "${q} Surat"
            if (items.length === 0 && !q.toLowerCase().includes('surat')) {
                try {
                    const retryUrl = `https://nominatim.openstreetmap.org/search?format=json&countrycodes=in&q=${encodeURIComponent(q + ' surat')}&limit=8`;
                    const rRes = await fetch(retryUrl);
                    if (rRes.ok) {
                        const data = await rRes.json();
                        if (Array.isArray(data)) {
                            data.forEach(r => {
                                const title = r.display_name.split(',')[0] || r.display_name;
                                items.push({
                                    lat: parseFloat(r.lat).toFixed(6),
                                    lon: parseFloat(r.lon).toFixed(6),
                                    title: title,
                                    label: r.display_name
                                });
                            });
                        }
                    }
                } catch (e) {}
            }

            // Deduplicate items based on coordinates
            const uniqueItems = [];
            const seen = new Set();
            items.forEach(it => {
                const key = it.lat.substring(0, 6) + '_' + it.lon.substring(0, 6);
                if (!seen.has(key)) {
                    seen.add(key);
                    uniqueItems.push(it);
                }
            });

            list.innerHTML = '';
            if (uniqueItems.length > 0) {
                uniqueItems.slice(0, 10).forEach(it => {
                    const item = document.createElement('div');
                    item.style.padding = '8px 12px';
                    item.style.fontSize = '12px';
                    item.style.cursor = 'pointer';
                    item.style.borderBottom = '1px solid #f1f5f9';
                    item.style.lineHeight = '1.35';
                    item.innerHTML = `<div style="display:flex;align-items:flex-start;gap:8px">
                        <i class="fa-solid fa-location-dot" style="color:#ef4444;font-size:13px;margin-top:2px"></i>
                        <div>
                            <b style="color:#0f172a">${escapeHtml(it.title)}</b>
                            <div style="font-size:11px;color:#64748b;margin-top:1px">${escapeHtml(it.label)}</div>
                        </div>
                    </div>`;
                    item.onmouseover = function() { this.style.background = '#f8fafc'; };
                    item.onmouseout = function() { this.style.background = '#fff'; };
                    item.onclick = function () {
                        document.getElementById('fieldLatitude').value = it.lat;
                        document.getElementById('fieldLongitude').value = it.lon;
                        document.getElementById('dispLat').textContent = it.lat;
                        document.getElementById('dispLng').textContent = it.lon;
                        document.getElementById('fieldAddress').value = it.label;
                        if (typeof overviewMap !== 'undefined' && overviewMap) {
                            overviewMap.setView([parseFloat(it.lat), parseFloat(it.lon)], 16);
                        }
                        list.style.display = 'none';
                    };
                    list.appendChild(item);
                });
                list.style.display = 'block';
            } else {
                list.innerHTML = '<div style="padding:10px 14px;font-size:12px;color:#94a3b8;text-align:center">No location found for "' + escapeHtml(q) + '". Try searching with specific market name or Surat area.</div>';
                list.style.display = 'block';
            }
        }, 300);
    }

    function escapeHtml(text) {
        if (!text) return '';
        const map = {'&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#039;'};
        return text.toString().replace(/[&<>"']/g, m => map[m]);
    }

    // Company selection automatically checks all employees of that company
    function handleCompanyCheck(compId, isChecked) {
        document.querySelectorAll('.emp-for-comp-' + compId).forEach(empBox => {
            empBox.checked = isChecked;
        });
        updateCompCount();
        updateEmpCount();
    }

    // Department selection automatically checks all employees of that department
    function handleDepartmentCheck(deptId, isChecked) {
        document.querySelectorAll('.emp-for-dept-' + deptId).forEach(empBox => {
            empBox.checked = isChecked;
        });
        updateDeptCount();
        updateEmpCount();
    }

    function updateCompCount() {
        const count = document.querySelectorAll('.comp-checkbox:checked').length;
        document.getElementById('compSelectedCount').textContent = count;
    }

    function updateDeptCount() {
        const count = document.querySelectorAll('.dept-checkbox:checked').length;
        document.getElementById('deptSelectedCount').textContent = count;
    }

    function updateEmpCount() {
        const count = document.querySelectorAll('.emp-checkbox:checked').length;
        document.getElementById('empSelectedCount').textContent = count;
    }

    function selectAllEmployees(val) {
        document.querySelectorAll('.emp-checkbox').forEach(c => c.checked = val);
        updateEmpCount();
    }

    function filterEmployeesByDept(deptId, pillBtn) {
        document.querySelectorAll('.cat-pill').forEach(p => p.classList.remove('active'));
        pillBtn.classList.add('active');

        document.querySelectorAll('.emp-card').forEach(card => {
            if (deptId === 'all' || String(card.getAttribute('data-dept-id')) === String(deptId)) {
                card.style.display = 'flex';
            } else {
                card.style.display = 'none';
            }
        });
    }

    function searchEmployeesInList(val) {
        const query = val.toLowerCase().trim();
        document.querySelectorAll('.emp-card').forEach(card => {
            const name = card.getAttribute('data-emp-name') || '';
            if (!query || name.includes(query)) {
                card.style.display = 'flex';
            } else {
                card.style.display = 'none';
            }
        });
    }

    function scrollDeptPills(direction) {
        const container = document.getElementById('deptPillsContainer');
        container.scrollBy({ left: direction * 120, behavior: 'smooth' });
    }
</script>
@endsection
