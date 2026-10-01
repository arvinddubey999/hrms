<?php

namespace App\Http\Controllers;

use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Http\Request;

class RequestController extends Controller
{
    public function index(Request $request)
    {
        $status = $request->get('status');
        $q = $request->get('q');
        $month = $request->get('month', now()->format('Y-m'));

        $items = LeaveRequest::query()
            ->with(['user', 'updater'])
            ->when($status && $status !== 'all', fn ($query) => $query->where('status', $status))
            ->when($q, function ($query) use ($q) {
                $query->where(function ($inner) use ($q) {
                    $inner->where('reason', 'like', "%{$q}%")
                        ->orWhere('leave_type', 'like', "%{$q}%")
                        ->orWhereHas('user', fn ($u) => $u->where('first_name', 'like', "%{$q}%")->orWhere('last_name', 'like', "%{$q}%"));
                });
            })
            ->when($month, function ($query) use ($month) {
                if (str_contains($month, '-')) {
                    [$y, $m] = explode('-', $month);
                    $query->whereYear('from_date', $y)->whereMonth('from_date', $m);
                }
            })
            ->latest()
            ->paginate(15)
            ->withQueryString();

        $counts = [
            'total' => LeaveRequest::count(),
            'pending' => LeaveRequest::where('status', 'pending')->count(),
            'approved' => LeaveRequest::where('status', 'approved')->count(),
            'rejected' => LeaveRequest::where('status', 'rejected')->count(),
        ];

        $employees = User::where('status', 'active')->orderBy('first_name')->get();

        return view('requests.index', compact('items', 'counts', 'status', 'q', 'month', 'employees'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'leave_type' => 'required|string',
            'from_date' => 'required|date',
            'to_date' => 'required|date|after_or_equal:from_date',
            'reason' => 'nullable|string',
            'status' => 'required|in:pending,approved,rejected',
        ]);

        $data['updated_by'] = $request->user()->id;
        LeaveRequest::create($data);

        return back()->with('ok', 'Leave request created by Admin.');
    }

    public function updateStatus(Request $request, LeaveRequest $leaveRequest)
    {
        $data = $request->validate([
            'status' => 'required|in:pending,approved,rejected,unapproved',
        ]);
        $leaveRequest->update([
            'status' => $data['status'],
            'updated_by' => $request->user()->id,
        ]);

        return back()->with('ok', 'Leave status updated to '.strtoupper($data['status']).'.');
    }

    public function pdf(LeaveRequest $leaveRequest)
    {
        $leaveRequest->load(['user', 'updater']);
        return view('requests.pdf', ['req' => $leaveRequest]);
    }
}
