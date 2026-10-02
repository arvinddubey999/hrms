<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Advance;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class RequestController extends Controller
{
    /**
     * Get summary counters & holiday list for Request Dashboard.
     */
    public function dashboard(Request $request)
    {
        $user = $request->user();

        // Leave counts (Paid, Sick, Vacation, Used, Balance)
        $leaveSummary = [
            'paid_leave_balance' => 12,
            'paid_leave_used' => LeaveRequest::where('user_id', $user->id)->where('leave_type', 'Paid Leave')->where('status', 'approved')->count(),
            'sick_leave_balance' => 6,
            'sick_leave_used' => LeaveRequest::where('user_id', $user->id)->where('leave_type', 'Sick Leave')->where('status', 'approved')->count(),
            'vacation_balance' => 10,
            'vacation_used' => LeaveRequest::where('user_id', $user->id)->where('leave_type', 'Vacation')->where('status', 'approved')->count(),
            'active_pending' => LeaveRequest::where('user_id', $user->id)->where('status', 'pending')->count() + Advance::where('user_id', $user->id)->where('status', 'pending')->count(),
        ];

        // Upcoming Holidays List matching Screenshot "Company holidays list show lik this.jpeg"
        $holidays = Holiday::query()
            ->whereDate('date', '>=', now()->startOfYear())
            ->orderBy('date')
            ->get()
            ->map(fn (Holiday $h) => [
                'id' => $h->id,
                'name' => $h->name,
                'date' => $h->date->format('d M Y'),
                'day' => $h->date->format('l'),
                'description' => $h->description ?: 'Company Holiday',
            ]);

        return response()->json([
            'summary' => $leaveSummary,
            'holidays' => $holidays,
        ]);
    }

    /**
     * Get list of all requests (Leaves & Loans) with filter support.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $reqType = $request->get('type', 'all'); // all, leave, loan
        $status = $request->get('status'); // pending, approved, rejected

        $items = collect();

        // 1. Fetch Leave Requests
        if (in_array($reqType, ['all', 'leave'])) {
            $leaveQuery = LeaveRequest::query()->with(['user', 'updater']);
            if (!$user->isManager()) {
                $leaveQuery->where('user_id', $user->id);
            }
            if ($status && $status !== 'all') {
                $leaveQuery->where('status', $status);
            }

            $leaves = $leaveQuery->latest()->get()->map(fn (LeaveRequest $r) => [
                'id' => $r->id,
                'request_category' => 'leave',
                'user_id' => $r->user_id,
                'user_name' => $r->user?->displayName() ?? 'Unknown Staff',
                'user_phone' => $r->user?->phone ?? '',
                'user_photo' => $r->user?->photo ? url('storage/'.$r->user->photo) : null,
                'user_designation' => $r->user?->designation ?? 'Employee',
                'title' => $r->leave_type,
                'leave_type' => $r->leave_type,
                'from_date' => $r->from_date->format('Y-m-d'),
                'to_date' => $r->to_date->format('Y-m-d'),
                'date_range' => $r->from_date->format('d M Y') . ' - ' . $r->to_date->format('d M Y'),
                'days_count' => $r->from_date->diffInDays($r->to_date) + 1,
                'reason' => $r->reason,
                'document_photo' => $r->document_photo ? url('storage/'.$r->document_photo) : null,
                'status' => $r->status,
                'rejection_reason' => $r->rejection_reason,
                'updated_by' => $r->updater?->displayName(),
                'created_at' => $r->created_at->format('d M Y, h:i A'),
            ]);

            $items = $items->concat($leaves);
        }

        // 2. Fetch Loan / Advance Requests
        if (in_array($reqType, ['all', 'loan', 'advance'])) {
            $loanQuery = Advance::query()->with('user');
            if (!$user->isManager()) {
                $loanQuery->where('user_id', $user->id);
            }
            if ($status && $status !== 'all') {
                $loanQuery->where('status', $status);
            }

            $loans = $loanQuery->latest()->get()->map(fn (Advance $a) => [
                'id' => $a->id,
                'request_category' => 'loan',
                'user_id' => $a->user_id,
                'user_name' => $a->user?->displayName() ?? 'Unknown Staff',
                'user_phone' => $a->user?->phone ?? '',
                'user_photo' => $a->user?->photo ? url('storage/'.$a->user->photo) : null,
                'user_designation' => $a->user?->designation ?? 'Employee',
                'title' => 'Loan / Advance Request',
                'amount' => (float) $a->amount,
                'formatted_amount' => '₹' . number_format($a->amount, 2),
                'reason' => $a->reason ?: $a->title,
                'repayment_months' => $a->repayment_months ?? 1,
                'paid_on' => $a->paid_on ? $a->paid_on->format('Y-m-d') : null,
                'status' => $a->status,
                'created_at' => $a->created_at->format('d M Y, h:i A'),
            ]);

            $items = $items->concat($loans);
        }

        // Sort combined requests by created_at descending
        $sorted = $items->sortByDesc('created_at')->values()->all();

        return response()->json(['data' => $sorted]);
    }

    /**
     * Submit a new Leave Request.
     */
    public function storeLeave(Request $request)
    {
        $data = $request->validate([
            'leave_type' => 'required|string',
            'from_date' => 'required|date',
            'to_date' => 'required|date|after_or_equal:from_date',
            'reason' => 'required|string|min:3',
            'document_photo' => 'nullable|image|max:10240',
        ]);

        $path = null;
        if ($request->hasFile('document_photo')) {
            $path = $request->file('document_photo')->store('leave_documents', 'public');
        }

        $leave = LeaveRequest::create([
            'user_id' => $request->user()->id,
            'leave_type' => $data['leave_type'],
            'from_date' => $data['from_date'],
            'to_date' => $data['to_date'],
            'reason' => $data['reason'],
            'document_photo' => $path,
            'status' => 'pending',
        ]);

        return response()->json([
            'ok' => true,
            'message' => 'Leave Request submitted successfully!',
            'data' => $leave,
        ], 201);
    }

    /**
     * Submit a new Loan / Advance Request.
     */
    public function storeLoan(Request $request)
    {
        $data = $request->validate([
            'amount' => 'required|numeric|min:100',
            'reason' => 'required|string|min:3',
            'repayment_months' => 'nullable|integer|min:1|max:36',
        ]);

        $loan = Advance::create([
            'user_id' => $request->user()->id,
            'title' => 'Loan Request - ₹' . number_format($data['amount']),
            'amount' => $data['amount'],
            'reason' => $data['reason'],
            'repayment_months' => $data['repayment_months'] ?? 1,
            'paid_on' => now()->toDateString(),
            'status' => 'pending',
        ]);

        return response()->json([
            'ok' => true,
            'message' => 'Loan Request submitted successfully!',
            'data' => $loan,
        ], 201);
    }

    /**
     * Admin/Manager Approve or Reject Request.
     */
    public function updateStatus(Request $request, $id)
    {
        if (!$request->user()->isManager()) {
            return response()->json(['message' => 'Only managers can approve or reject requests.'], 403);
        }

        $data = $request->validate([
            'category' => 'required|in:leave,loan',
            'status' => 'required|in:approved,rejected',
            'rejection_reason' => 'nullable|string',
        ]);

        if ($data['category'] === 'leave') {
            $req = LeaveRequest::findOrFail($id);
            $req->update([
                'status' => $data['status'],
                'rejection_reason' => $data['rejection_reason'] ?? null,
                'updated_by' => $request->user()->id,
            ]);
        } else {
            $req = Advance::findOrFail($id);
            $req->update([
                'status' => $data['status'],
                'updated_by' => $request->user()->id,
            ]);
        }

        return response()->json([
            'ok' => true,
            'message' => 'Request ' . ucfirst($data['status']) . ' successfully!',
            'data' => $req,
        ]);
    }
}
