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
            <div class="muted">{{ $date }} | {{ $staff->department ?: ($staff->company->name ?? 'Staff') }}</div>
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
            </tr>
        </thead>
        <tbody>
            @forelse($punches as $p)
                <tr>
                    <td><span class="chip" style="background:#f1f5f9;color:#475569">{{ strtoupper($p->source) }}</span></td>
                    <td>
                        @if($p->type === 'in')
                            <span class="chip" style="background:#dcfce7;color:#166534;font-weight:700"><i class="fa-solid fa-arrow-right-to-bracket"></i> PUNCH IN</span>
                        @else
                            <span class="chip" style="background:#fef3c7;color:#92400e;font-weight:700"><i class="fa-solid fa-arrow-right-from-bracket"></i> PUNCH OUT</span>
                        @endif
                    </td>
                    <td><b>{{ $p->punched_at->format('g:i A') }}</b></td>
                    <td><i class="fa-solid fa-location-dot" style="color:#ef4444"></i> {{ $p->location_text ?: 'Office Premises' }}</td>
                    <td>
                        @if($p->photo)
                            <a href="{{ asset('storage/'.$p->photo) }}" target="_blank">
                                <img src="{{ asset('storage/'.$p->photo) }}" width="48" height="48" style="border-radius:8px;object-fit:cover;border:1px solid #cbd5e1">
                            </a>
                        @else
                            <span class="muted">No photo</span>
                        @endif
                    </td>
                </tr>
            @empty
                <tr><td colspan="5" class="muted" style="text-align:center;padding:20px">No punches recorded for this date.</td></tr>
            @endforelse
        </tbody>
    </table>

    <hr style="margin:16px 0;border:0;border-top:1px solid #e2e8f0">
    
    <h3 style="margin:0 0 10px 0"><i class="fa-solid fa-camera"></i> Mark Attendance & Photo Upload</h3>
    <form method="post" action="{{ route('employees.mark', $staff) }}" enctype="multipart/form-data" class="grid-3" style="align-items:end;gap:12px;background:#f8fafc;padding:14px;border-radius:10px;border:1px solid #e2e8f0">
        @csrf
        <div>
            <label>Punch Type *</label>
            <select name="type" required>
                <option value="in" {{ optional($punches->last())->type === 'out' || $punches->isEmpty() ? 'selected' : '' }}>Punch IN</option>
                <option value="out" {{ optional($punches->last())->type === 'in' ? 'selected' : '' }}>Punch OUT</option>
            </select>
        </div>
        <div>
            <label>Punch Photo (Camera / File)</label>
            <input type="file" name="photo" accept="image/*">
        </div>
        <div>
            <label>Remarks / Notes</label>
            <input name="remarks" placeholder="Manual marking reason...">
        </div>
        <div style="grid-column:1/-1;display:flex;justify-content:flex-end">
            <button class="btn"><i class="fa-solid fa-check"></i> Mark Attendance Punch</button>
        </div>
    </form>
</div>
@endsection
