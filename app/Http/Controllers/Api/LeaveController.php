<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\LeaveRequest;
use Illuminate\Http\Request;

class LeaveController extends Controller
{
    public function index(Request $request)
    {
        $items = LeaveRequest::query()
            ->where('user_id', $request->user()->id)
            ->latest()
            ->get()
            ->map(fn (LeaveRequest $r) => [
                'id' => $r->id,
                'leave_type' => $r->leave_type,
                'from_date' => $r->from_date->toDateString(),
                'to_date' => $r->to_date->toDateString(),
                'reason' => $r->reason,
                'status' => $r->status,
            ]);

        return response()->json(['data' => $items]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'leave_type' => 'required|string',
            'from_date' => 'required|date',
            'to_date' => 'required|date|after_or_equal:from_date',
            'reason' => 'nullable|string',
        ]);
        $data['user_id'] = $request->user()->id;
        $data['status'] = 'pending';
        $leave = LeaveRequest::query()->create($data);

        return response()->json(['data' => $leave], 201);
    }
}
