@extends('layouts.app')
@section('content')
<div class="page-head"><h1>Day punches</h1><a class="btn light" href="{{ route('employees.show', $staff) }}">Back</a></div>
<div class="card">
    <h2>{{ $staff->displayName() }}</h2>
    <p class="muted">{{ $date }}</p>
    <table class="table">
        <thead><tr><th>Action</th><th>Status</th><th>Time</th><th>Location</th><th>Photo</th></tr></thead>
        <tbody>
        @forelse($punches as $p)
            <tr>
                <td>{{ $p->source }}</td>
                <td><span class="badge {{ $p->type }}">{{ strtoupper($p->type) }}</span></td>
                <td>{{ $p->punched_at->format('g:i A') }}</td>
                <td>{{ $p->location_text }}</td>
                <td>@if($p->photo)<img src="{{ asset('storage/'.$p->photo) }}" width="48" style="border-radius:50%">@endif</td>
            </tr>
        @empty
            <tr><td colspan="5">No punches</td></tr>
        @endforelse
        </tbody>
    </table>
    <form method="post" action="{{ route('employees.mark', $staff) }}" class="row" style="margin-top:12px">
        @csrf
        <input type="hidden" name="type" value="{{ optional($punches->last())->type==='in' ? 'out' : 'in' }}">
        <button class="btn">Mark Attendance</button>
    </form>
</div>
@endsection
