@extends('layouts.app')
@section('content')
<div class="page-head">
    <h1>Day Punches Log</h1>
    <a class="btn light" href="{{ route('employees.show', $staff) }}"><i class="fa-solid fa-arrow-left"></i> Back to Profile</a>
</div>

<div class="card" style="margin-bottom:16px">
    <div style="display:flex;justify-content:space-between;align-items:center">
        <div>
            <h2 style="margin:0">{{ $staff->displayName() }}</h2>
            <div class="muted">{{ $date }} | {{ $staff->department ?: ($staff->company->name ?? 'Staff') }} | Code: {{ $staff->employee_code ?: '-' }}</div>
        </div>
    </div>

    <table class="table" style="margin-top:14px">
        <thead>
            <tr>
                <th>Action Source</th>
                <th>Punch Type</th>
                <th>Time</th>
                <th>Location Details</th>
                <th>Punch Photo</th>
                <th style="width:120px;text-align:right">Actions</th>
            </tr>
        </thead>
        <tbody>
            @forelse($punches as $p)
                @php
                    $isDD = str_contains(strtoupper($p->remarks ?? ''), 'DOUBLE_DUTY') || $p->type === 'double_duty';
                    $isHD = str_contains(strtoupper($p->remarks ?? ''), 'HALF_DAY') || $p->type === 'half_day';
                    $isWOP = str_contains(strtoupper($p->remarks ?? ''), 'WOP');
                    $isHOP = str_contains(strtoupper($p->remarks ?? ''), 'HOP');

                    $punchStatus = 'in';
                    if ($isDD) {
                        $punchStatus = 'double_duty';
                    } elseif ($isHD) {
                        $punchStatus = 'half_day';
                    } elseif ($isWOP) {
                        $punchStatus = 'wop';
                    } elseif ($isHOP) {
                        $punchStatus = 'hop';
                    } else {
                        $punchStatus = strtolower($p->type);
                    }
                @endphp
                <tr>
                    <td><span class="chip" style="background:#f1f5f9;color:#475569">{{ strtoupper($p->source) }}</span></td>
                    <td>
                        @if($isDD)
                            <span class="chip" style="background:#e0e7ff;color:#4338ca;font-weight:700"><i class="fa-solid fa-bolt"></i> DOUBLE DUTY (2x)</span>
                        @elseif($isHD)
                            <span class="chip" style="background:#ffedd5;color:#c2410c;font-weight:700"><i class="fa-solid fa-hourglass-half"></i> HALF DAY (0.5)</span>
                        @elseif($isWOP)
                            <span class="chip" style="background:#e0e7ff;color:#3730a3;font-weight:700"><i class="fa-solid fa-calendar-check"></i> WOP</span>
                        @elseif($isHOP)
                            <span class="chip" style="background:#f3e8ff;color:#6b21a8;font-weight:700"><i class="fa-solid fa-gift"></i> HOP</span>
                        @elseif($p->type === 'in')
                            <span class="chip" style="background:#dcfce7;color:#166534;font-weight:700"><i class="fa-solid fa-arrow-right-to-bracket"></i> PUNCH IN</span>
                        @else
                            <span class="chip" style="background:#fef3c7;color:#92400e;font-weight:700"><i class="fa-solid fa-arrow-right-from-bracket"></i> PUNCH OUT</span>
                        @endif
                    </td>
                    <td><b>{{ $p->punched_at->format('g:i A') }}</b></td>
                    <td><i class="fa-solid fa-location-dot" style="color:#ef4444"></i> {{ $p->location_text ?: 'Office Premises' }}</td>
                    <td>
                        @if($p->photo)
                            <a href="javascript:void(0)" onclick="openPhotoZoomModal('{{ asset('storage/'.$p->photo) }}')">
                                <img src="{{ asset('storage/'.$p->photo) }}" width="44" height="44" style="border-radius:8px;object-fit:cover;border:1px solid #cbd5e1">
                            </a>
                        @else
                            <span class="muted">No photo</span>
                        @endif
                    </td>
                    <td style="text-align:right">
                        @if(auth()->user()->isAdmin() || auth()->user()->isManager() || auth()->user()->hasPermission('attendance.edit'))
                            <button type="button" class="btn light" style="padding:4px 8px;font-size:11px" onclick="openEditPunchModal({{ json_encode([
                                'id' => $p->id,
                                'type' => $punchStatus,
                                'time' => $p->punched_at->format('H:i'),
                                'location' => $p->location_text ?: '',
                                'remarks' => $p->remarks ?: '',
                                'shift_id' => $staff->shift_id,
                                'update_url' => route('punches.update', $p),
                                'delete_url' => route('punches.destroy', $p),
                            ]) }})">
                                <i class="fa-solid fa-pen-to-square"></i> Edit
                            </button>
                        @endif
                        @if(auth()->user()->isAdmin() || auth()->user()->isManager() || auth()->user()->hasPermission('attendance.delete'))
                            <form method="post" action="{{ route('punches.destroy', $p) }}" style="display:inline" onsubmit="return confirm('Are you sure you want to delete this punch?')">
                                @csrf @method('delete')
                                <button type="submit" class="btn light" style="padding:4px 8px;font-size:11px;color:#dc2626"><i class="fa-solid fa-trash"></i></button>
                            </form>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="6" class="muted" style="text-align:center;padding:20px">No punches recorded for this date.</td></tr>
            @endforelse
        </tbody>
    </table>

    <!-- SECTION 1: SINGLE DAY MARK ATTENDANCE -->
    <hr style="margin:16px 0;border:0;border-top:1px solid #e2e8f0">
    <h3 style="margin:0 0 10px 0"><i class="fa-solid fa-camera"></i> Mark Attendance & Photo Upload (Date: {{ $date }})</h3>
    <form method="post" action="{{ route('employees.mark', $staff) }}" enctype="multipart/form-data" class="grid-3" style="align-items:end;gap:12px;background:#f8fafc;padding:14px;border-radius:10px;border:1px solid #e2e8f0">
        @csrf
        <input type="hidden" name="date" value="{{ $date }}">

        <div>
            <label>Punch Type *</label>
            <select name="type" required style="font-weight:600">
                <option value="in" style="color:#22c55e;font-weight:700" {{ optional($punches->last())->type === 'out' || $punches->isEmpty() ? 'selected' : '' }}>IN (Present)</option>
                <option value="out" style="color:#ef4444;font-weight:700" {{ optional($punches->last())->type === 'in' ? 'selected' : '' }}>OUT</option>
                <option value="half_day" style="color:#f97316;font-weight:700">HALF DAY (0.5 Day)</option>
                <option value="double_duty" style="color:#6366f1;font-weight:700">DOUBLE DUTY (2 Days)</option>
                <option value="absent" style="color:#ef4444;font-weight:700">ABSENT</option>
                <option value="leave" style="color:#f59e0b;font-weight:700">LEAVE</option>
                <option value="holiday" style="color:#22c55e;font-weight:700">HOLIDAY</option>
                <option value="week_off" style="color:#f59e0b;font-weight:700">WEEK OFF</option>
                <option value="wop" style="color:#38bdf8;font-weight:700">WEEK OFF PRESENT (WOP)</option>
                <option value="hop" style="color:#a855f7;font-weight:700">HOLIDAY - PRESENT (HOP)</option>
            </select>
        </div>

        <div>
            <label>Punch Time *</label>
            <input type="time" name="time" value="{{ now('Asia/Kolkata')->format('H:i') }}" required>
        </div>

        <div>
            <label>Punch Photo (Camera / File)</label>
            <input type="file" name="photo" accept="image/*">
        </div>

        <div style="grid-column:1/-1;display:flex;justify-content:space-between;align-items:center;margin-top:6px">
            <input name="remarks" placeholder="Manual marking reason..." style="flex:1;margin-right:12px">
            <button class="btn" style="white-space:nowrap"><i class="fa-solid fa-check"></i> Mark Attendance Punch</button>
        </div>
    </form>

    <!-- SECTION 2: MULTIPLE DATES ATTENDANCE FOR THIS SINGLE EMPLOYEE -->
    <hr style="margin:20px 0;border:0;border-top:1px solid #e2e8f0">
    <div style="background:#f0fdf4;border:1px solid #bbf7d0;border-radius:12px;padding:16px">
        <h3 style="margin:0 0 6px 0;color:#166534;font-size:15px;display:flex;align-items:center;gap:8px">
            <i class="fa-solid fa-calendar-days"></i> Mark Date Range Attendance for {{ $staff->displayName() }}
        </h3>
        <p class="muted" style="margin:0 0 12px 0;font-size:12px">
            Select a date range to mark attendance across multiple days (e.g. 15 Oct to 30 Oct) for this employee at once:
        </p>
        <form method="post" action="{{ route('employees.mark-range', $staff) }}" class="grid-3" style="gap:12px;align-items:end">
            @csrf
            <div>
                <label style="font-weight:600;font-size:12px;display:block;margin-bottom:4px">From Date *</label>
                <input type="date" name="from_date" value="{{ $date }}" required style="width:100%;padding:8px 10px;border-radius:8px;border:1px solid #cbd5e1;font-size:13px;background:#fff">
            </div>
            <div>
                <label style="font-weight:600;font-size:12px;display:block;margin-bottom:4px">To Date *</label>
                <input type="date" name="to_date" value="{{ $date }}" required style="width:100%;padding:8px 10px;border-radius:8px;border:1px solid #cbd5e1;font-size:13px;background:#fff">
            </div>
            <div>
                <label style="font-weight:600;font-size:12px;display:block;margin-bottom:4px">Status / Punch Type *</label>
                <select name="type" required style="width:100%;padding:8px 10px;border-radius:8px;border:1px solid #cbd5e1;font-size:13px;font-weight:700;background:#fff">
                    <option value="in" style="color:#16a34a;font-weight:700">PRESENT (IN)</option>
                    <option value="half_day" style="color:#ea580c;font-weight:700">HALF DAY (0.5 Day)</option>
                    <option value="double_duty" style="color:#4f46e5;font-weight:700">DOUBLE DUTY (2 Days)</option>
                    <option value="absent" style="color:#dc2626;font-weight:700">ABSENT</option>
                    <option value="leave" style="color:#d97706;font-weight:700">LEAVE</option>
                    <option value="holiday" style="color:#16a34a;font-weight:700">HOLIDAY</option>
                    <option value="week_off" style="color:#d97706;font-weight:700">WEEK OFF</option>
                    <option value="wop" style="color:#0284c7;font-weight:700">WEEK OFF PRESENT (WOP)</option>
                    <option value="hop" style="color:#9333ea;font-weight:700">HOLIDAY PRESENT (HOP)</option>
                </select>
            </div>
            <div>
                <label style="font-weight:600;font-size:12px;display:block;margin-bottom:4px">Punch Time (optional)</label>
                <input type="time" name="time" value="09:30" style="width:100%;padding:8px 10px;border-radius:8px;border:1px solid #cbd5e1;font-size:13px;background:#fff">
            </div>
            <div>
                <label style="font-weight:600;font-size:12px;display:block;margin-bottom:4px">Remarks</label>
                <input type="text" name="remarks" placeholder="e.g. Tour attendance / Range mark" style="width:100%;padding:8px 10px;border-radius:8px;border:1px solid #cbd5e1;font-size:13px;background:#fff">
            </div>
            <div>
                <button type="submit" class="btn" style="width:100%;padding:10px;background:#166534;justify-content:center"><i class="fa-solid fa-check"></i> Apply Date Range Attendance</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal: Edit Attendance (Matches Screenshot 4 & 5) -->
<div id="editPunchModal" class="modal-bg" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.65);z-index:9999;align-items:center;justify-content:center">
    <div class="modal" style="background:#111827;color:#f9fafb;border-radius:16px;padding:24px;width:420px;max-width:92%;box-shadow:0 25px 50px -12px rgba(0,0,0,0.5)">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:18px">
            <h3 style="margin:0;font-size:18px;color:#fff"><i class="fa-solid fa-pen-to-square"></i> Edit Attendance</h3>
            <button type="button" onclick="closeEditPunchModal()" style="border:0;background:none;color:#9ca3af;font-size:20px;cursor:pointer">&times;</button>
        </div>
        <form id="editPunchForm" method="post">
            @csrf @method('put')

            <div style="margin-bottom:14px">
                <label style="display:block;margin-bottom:6px;font-size:12px;font-weight:600;color:#9ca3af">Status</label>
                <select id="punch_status" name="status" style="width:100%;background:#1f2937;border:1px solid #374151;color:#fff;border-radius:8px;padding:10px 12px">
                    <option value="in" style="color:#22c55e;font-weight:700">IN (Present)</option>
                    <option value="out" style="color:#ef4444;font-weight:700">OUT</option>
                    <option value="half_day" style="color:#f97316;font-weight:700">HALF DAY (0.5 Day)</option>
                    <option value="double_duty" style="color:#6366f1;font-weight:700">DOUBLE DUTY (2 Days)</option>
                    <option value="absent" style="color:#ef4444;font-weight:700">ABSENT</option>
                    <option value="leave" style="color:#f59e0b;font-weight:700">LEAVE</option>
                    <option value="holiday" style="color:#22c55e;font-weight:700">HOLIDAY</option>
                    <option value="week_off" style="color:#f59e0b;font-weight:700">WEEK OFF</option>
                    <option value="wop" style="color:#38bdf8;font-weight:700">WEEK OFF PRESENT (WOP)</option>
                    <option value="hop" style="color:#a855f7;font-weight:700">HOLIDAY - PRESENT (HOP)</option>
                </select>
            </div>

            <div style="margin-bottom:14px">
                <label style="display:block;margin-bottom:6px;font-size:12px;font-weight:600;color:#9ca3af">Timestamp</label>
                <input type="time" id="punch_time" name="time" style="width:100%;background:#1f2937;border:1px solid #374151;color:#fff;border-radius:8px;padding:10px 12px">
            </div>

            <div style="margin-bottom:14px">
                <label style="display:block;margin-bottom:6px;font-size:12px;font-weight:600;color:#9ca3af">Shift</label>
                <select id="punch_shift" name="shift_id" style="width:100%;background:#1f2937;border:1px solid #374151;color:#fff;border-radius:8px;padding:10px 12px">
                    <option value="">Select Shift</option>
                    @foreach($shifts as $s)
                        <option value="{{ $s->id }}" {{ $staff->shift_id == $s->id ? 'selected' : '' }}>{{ $s->name }} ({{ $s->start_time }} - {{ $s->end_time }})</option>
                    @endforeach
                </select>
            </div>

            <div style="margin-bottom:14px">
                <label style="display:block;margin-bottom:6px;font-size:12px;font-weight:600;color:#9ca3af">Geo Address</label>
                <input type="text" id="punch_location" name="location_text" placeholder="Enter geo address..." style="width:100%;background:#1f2937;border:1px solid #374151;color:#fff;border-radius:8px;padding:10px 12px">
            </div>

            <div style="margin-bottom:20px">
                <label style="display:block;margin-bottom:6px;font-size:12px;font-weight:600;color:#9ca3af">Remarks</label>
                <input type="text" id="punch_remarks" name="remarks" placeholder="Enter remarks..." style="width:100%;background:#1f2937;border:1px solid #374151;color:#fff;border-radius:8px;padding:10px 12px">
            </div>

            <div style="display:flex;flex-direction:column;gap:10px">
                <button type="submit" style="width:100%;padding:12px;background:#000;color:#fff;border:1px solid #374151;border-radius:10px;font-weight:700;cursor:pointer">Update</button>
            </div>
        </form>

        <form id="deletePunchForm" method="post" style="margin-top:10px" onsubmit="return confirm('Are you sure you want to delete this punch record?')">
            @csrf @method('delete')
            <button type="submit" style="width:100%;padding:12px;background:#fee2e2;color:#ef4444;border:0;border-radius:10px;font-weight:700;cursor:pointer">Delete</button>
        </form>
    </div>
</div>

<!-- Modal: Photo Zoom Preview -->
<div id="photoZoomModal" class="modal-bg" onclick="closePhotoZoomModal()" style="display:none;position:fixed;top:0;left:0;right:0;bottom:0;background:rgba(0,0,0,0.85);z-index:9999;align-items:center;justify-content:center">
    <div class="modal" style="max-width:540px;text-align:center;background:#111827;padding:16px;border-radius:12px" onclick="event.stopPropagation()">
        <div style="display:flex;justify-content:space-between;align-items:center;margin-bottom:12px">
            <h4 style="margin:0;color:#fff"><i class="fa-solid fa-image"></i> Punch Photo Clear View</h4>
            <button type="button" onclick="closePhotoZoomModal()" class="btn light" style="padding:4px 10px;font-size:12px">&times; Close</button>
        </div>
        <img id="photoZoomImg" src="" style="width:100%;max-height:480px;object-fit:contain;border-radius:12px;box-shadow:0 4px 12px rgba(0,0,0,0.5)">
    </div>
</div>

<script>
function openPhotoZoomModal(url) {
    document.getElementById('photoZoomImg').src = url;
    var m = document.getElementById('photoZoomModal');
    m.style.display = 'flex';
}
function closePhotoZoomModal() {
    var m = document.getElementById('photoZoomModal');
    m.style.display = 'none';
}

function openEditPunchModal(data) {
    var form = document.getElementById('editPunchForm');
    form.action = data.update_url;
    document.getElementById('deletePunchForm').action = data.delete_url;
    document.getElementById('punch_status').value = (data.type || 'in').toLowerCase();
    document.getElementById('punch_time').value = data.time || '';
    document.getElementById('punch_location').value = data.location || '';
    document.getElementById('punch_remarks').value = data.remarks || '';
    if (data.shift_id) {
        document.getElementById('punch_shift').value = data.shift_id;
    }
    document.getElementById('editPunchModal').style.display = 'flex';
}
function closeEditPunchModal() {
    document.getElementById('editPunchModal').style.display = 'none';
}
</script>
@endsection
