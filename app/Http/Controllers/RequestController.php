<?php

namespace App\Http\Controllers;

use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Http\Request;

class RequestController extends Controller
{
    public function index(Request $request)
    {
        $u = $request->user();
        if (!$u || (!$u->hasPermission('leave.view') && !$u->hasPermission('leave.apply') && !$u->hasPermission('leave.approve'))) {
            return back()->with('error', 'You do not have permission to view leave requests.');
        }

        $status = $request->get('status');
        $q = $request->get('q');
        $month = $request->get('month', now()->format('Y-m'));
        $authUser = $request->user();

        $scopedQuery = LeaveRequest::query()->with(['user', 'updater']);
        $baseCount = LeaveRequest::query();

        if ($authUser && !$authUser->isAdmin()) {
            if ($authUser->hasPermission('leave.approve') || $authUser->isManager()) {
                $compIds = $authUser->accessibleCompanyIds();
                if (!empty($compIds)) {
                    $scopedQuery->whereHas('user', fn($q) => $q->whereIn('company_id', $compIds));
                    $baseCount->whereHas('user', fn($q) => $q->whereIn('company_id', $compIds));
                }
            } else {
                $scopedQuery->where('user_id', $authUser->id);
                $baseCount->where('user_id', $authUser->id);
            }
        }

        $items = $scopedQuery
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
            'total' => (clone $baseCount)->count(),
            'pending' => (clone $baseCount)->where('status', 'pending')->count(),
            'approved' => (clone $baseCount)->where('status', 'approved')->count(),
            'rejected' => (clone $baseCount)->where('status', 'rejected')->count(),
        ];

        $canApprove = $authUser && ($authUser->isAdmin() || $authUser->isManager() || $authUser->hasPermission('leave.approve'));
        $employees = $canApprove
            ? (!$authUser->isAdmin() ? $authUser->scopedEmployeesQuery()->orderBy('first_name')->get() : User::where('status', 'active')->orderBy('first_name')->get())
            : collect([$authUser]);

        return view('requests.index', compact('items', 'counts', 'status', 'q', 'month', 'employees'));
    }

    public function store(Request $request)
    {
        $u = $request->user();
        if (!$u || (!$u->hasPermission('leave.apply') && !$u->hasPermission('leave.approve'))) {
            return back()->with('error', 'You do not have permission to apply for leave requests.');
        }

        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'leave_type' => 'required|string',
            'from_date' => 'required|date',
            'to_date' => 'required|date|after_or_equal:from_date',
            'reason' => 'nullable|string',
            'status' => 'required|in:pending,approved,rejected',
        ]);

        if (!$u->isAdmin() && !$u->isManager() && !$u->hasPermission('leave.approve')) {
            $data['user_id'] = $u->id;
            $data['status'] = 'pending';
        }

        $data['updated_by'] = $request->user()->id;
        LeaveRequest::create($data);

        return back()->with('ok', 'Leave request created by Admin.');
    }

    public function updateStatus(Request $request, LeaveRequest $leaveRequest)
    {
        $u = $request->user();
        if (!$u || !$u->hasPermission('leave.approve')) {
            return back()->with('error', 'You do not have permission to approve or reject leave requests.');
        }

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
