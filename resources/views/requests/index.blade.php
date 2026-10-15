@extends('layouts.app')
@section('content')
<div class="page-head">
    <h1>Leave Requests Management</h1>
    @php
        $isApprover = auth()->user()->isAdmin() || auth()->user()->isManager() || auth()->user()->hasPermission('leave.approve');
    @endphp
    @if(auth()->user()->hasPermission('leave.apply') || auth()->user()->hasPermission('leave.approve'))
        <button class="btn" onclick="document.getElementById('newLeaveModal').classList.add('open')">
            {{ $isApprover ? '+ Create Leave Request (Admin/Manager)' : '+ Apply Leave Request' }}
        </button>
    @endif
</div>

<div class="card">
    <div class="kpi">
        <div><small>Total</small><b>{{ $counts['total'] }} requests</b></div>
        <div><small>Pending</small><b>{{ $counts['pending'] }} requests</b></div>
        <div><small>Approved</small><b>{{ $counts['approved'] }} requests</b></div>
        <div><small>Rejected</small><b>{{ $counts['rejected'] }} requests</b></div>
    </div>
</div>

<div class="card" style="margin-top:14px">
    <form class="row" method="get">
        <input name="q" value="{{ $q }}" placeholder="Search by name, reason or type..." style="flex:1">
        <input type="month" name="month" value="{{ $month }}" style="width:180px">
        <button class="btn">Filter</button>
    </form>
    <div class="tabs">
        <a class="{{ !$status || $status==='all'?'active':'' }}" href="{{ route('requests.index', ['month'=>$month]) }}">All</a>
        @foreach(['pending','approved','rejected','unapproved'] as $s)
            <a class="{{ $status===$s?'active':'' }}" href="{{ route('requests.index', ['status'=>$s,'month'=>$month]) }}">{{ ucfirst($s) }}</a>
        @endforeach
    </div>
    <table class="table">
        <thead>
            <tr>
                <th>Employee Name</th>
                <th>Leave Type</th>
                <th>Reason</th>
                <th>From - To Date</th>
                <th>Status</th>
                <th>Updated By</th>
                <th>PDF Form</th>
                <th>Action</th>
            </tr>
        </thead>
        <tbody>
        @forelse($items as $item)
            <tr>
                <td><b>{{ $item->user?->displayName() ?? '—' }}</b><div style="font-size:11px;color:#6b7280">{{ $item->user?->employee_code }}</div></td>
                <td><span class="chip">{{ $item->leave_type }}</span></td>
                <td>{{ $item->reason ?: 'No reason given' }}</td>
                <td>{{ $item->from_date->format('d M Y') }} to {{ $item->to_date->format('d M Y') }}</td>
                <td>
                    <span class="badge {{ $item->status==='approved'?'ok':($item->status==='rejected'?'no':'warn') }}">{{ strtoupper($item->status) }}</span>
                </td>
                <td>{{ $item->updater?->displayName() ?? '—' }}</td>
                <td>
                    <a href="{{ route('requests.pdf', $item) }}" target="_blank" class="btn light" style="font-size:11px;padding:3px 6px">
                        <i class="fa-solid fa-file-pdf"></i> PDF Form
                    </a>
                </td>
                <td>
                    @if(auth()->user()->hasPermission('leave.approve'))
                        <form method="post" action="{{ route('requests.status', $item) }}" class="row" style="gap:4px">
                            @csrf
                            <select name="status" style="width:110px;padding:4px">
                                @foreach(['pending','approved','rejected','unapproved'] as $s)
                                    <option value="{{ $s }}" {{ $item->status===$s?'selected':'' }}>{{ ucfirst($s) }}</option>
                                @endforeach
                            </select>
                            <button class="btn light" style="font-size:11px;padding:4px 8px">Update</button>
                        </form>
                    @else
                        <span class="muted" style="font-size:12px">View Only</span>
                    @endif
                </td>
            </tr>
        @empty
            <tr><td colspan="8" class="muted" style="text-align:center;padding:24px">No leave requests found.</td></tr>
        @endforelse
        </tbody>
    </table>
    {{ $items->links() }}
</div>

<!-- Modal: Create / Apply Leave Request -->
<div id="newLeaveModal" class="modal-bg">
    <form class="modal" method="post" action="{{ route('requests.store') }}" style="max-width:520px">
        @csrf
        @php
            $isApprover = auth()->user()->isAdmin() || auth()->user()->isManager() || auth()->user()->hasPermission('leave.approve');
        @endphp
        <h3>{{ $isApprover ? 'Create Leave Request (Admin/Manager)' : 'Apply Leave Request' }}</h3>
        
        @if($isApprover)
            <label>Select Employee *</label>
            <select name="user_id" required>
                <option value="">Select Employee</option>
                @foreach($employees as $e)
                    <option value="{{ $e->id }}" {{ $e->id == auth()->id() ? 'selected' : '' }}>{{ $e->displayName() }} ({{ $e->employee_code ?: 'N/A' }})</option>
                @endforeach
            </select>
        @else
            <input type="hidden" name="user_id" value="{{ auth()->id() }}">
        @endif

        <label>Leave Type *</label>
        <select name="leave_type" required>
            <option value="Casual Leave">Casual Leave</option>
            <option value="Sick Leave">Sick Leave</option>
            <option value="Privilege Leave">Privilege Leave</option>
            <option value="Emergency Leave">Emergency Leave</option>
            <option value="Unpaid Leave">Unpaid Leave</option>
        </select>

        <div class="grid-2">
            <div>
                <label>From Date *</label>
                <input type="date" name="from_date" value="{{ now()->toDateString() }}" required>
            </div>
            <div>
                <label>To Date *</label>
                <input type="date" name="to_date" value="{{ now()->toDateString() }}" required>
            </div>
        </div>

        <label>Reason / Notes</label>
        <textarea name="reason" rows="2" placeholder="Reason for leave..."></textarea>

        @if($isApprover)
            <label>Status *</label>
            <select name="status" required>
                <option value="pending" selected>Pending</option>
                <option value="approved">Approved</option>
                <option value="rejected">Rejected</option>
            </select>
        @else
            <input type="hidden" name="status" value="pending">
        @endif

        <div class="row" style="margin-top:16px;justify-content:flex-end">
            <button type="button" class="btn light" onclick="document.getElementById('newLeaveModal').classList.remove('open')">Cancel</button>
            <button class="btn">{{ $isApprover ? 'Create Request' : 'Submit Application' }}</button>
        </div>
    </form>
</div>
@endsection
