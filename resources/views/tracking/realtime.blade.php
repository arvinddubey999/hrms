@extends('layouts.app')
@section('content')
<h1>Realtime</h1>
<div class="card">
    <table class="table">
        <thead><tr><th>Employee</th><th>Last seen</th><th>Lat</th><th>Lng</th></tr></thead>
        <tbody>
        @forelse($staff as $u)
            <tr>
                <td>{{ $u->displayName() }}</td>
                <td>{{ optional($u->last_location_at)->diffForHumans() }}</td>
                <td>{{ $u->last_lat }}</td>
                <td>{{ $u->last_lng }}</td>
            </tr>
        @empty
            <tr><td colspan="4">No live locations yet.</td></tr>
        @endforelse
        </tbody>
    </table>
</div>
@endsection
