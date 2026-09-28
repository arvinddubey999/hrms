@extends('layouts.app')
@section('content')
<h1>All Requests</h1>
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
        <thead><tr><th>Name</th><th>Reason</th><th>Leave Type</th><th>Dates</th><th>Status</th><th>Updated By</th><th>Action</th></tr></thead>
        <tbody>
        @foreach($items as $item)
            <tr>
                <td>{{ $item->user?->displayName() ?? '—' }}</td>
                <td>{{ $item->reason }}</td>
                <td>{{ $item->leave_type }}</td>
                <td>{{ $item->from_date->format('d M') }} - {{ $item->to_date->format('d M') }}</td>
                <td>
                    <span class="badge {{ $item->status==='approved'?'ok':($item->status==='rejected'?'no':'warn') }}">- {{ ucfirst($item->status) }} -</span>
                </td>
                <td>{{ $item->updater?->displayName() ?? '—' }}</td>
                <td>
                    <form method="post" action="{{ route('requests.status', $item) }}" class="row">
                        @csrf
                        <select name="status" style="width:140px">
                            @foreach(['pending','approved','rejected','unapproved'] as $s)
                                <option value="{{ $s }}" {{ $item->status===$s?'selected':'' }}>{{ ucfirst($s) }}</option>
                            @endforeach
                        </select>
                        <button class="btn">Update Status</button>
                    </form>
                </td>
            </tr>
        @endforeach
        </tbody>
    </table>
    {{ $items->links() }}
</div>
@endsection
