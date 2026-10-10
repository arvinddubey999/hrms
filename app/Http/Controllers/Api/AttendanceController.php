<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AttendancePunch;
use App\Models\User;
use App\Services\AttendanceService;
use App\Services\GeoService;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function punch(Request $request, AttendanceService $attendance)
    {
        $data = $request->validate([
            'type' => 'required|in:in,out',
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
            'location_text' => 'nullable|string',
            'face_detected' => 'nullable|boolean',
            'photo' => 'nullable|image|max:8192',
            'employee_id' => 'nullable|exists:users,id',
        ]);

        $actor = $request->user();
        $target = $actor;
        $source = 'mobile';
        $skip = false;

        if (! empty($data['employee_id']) && (int) $data['employee_id'] !== $actor->id) {
            if (! $actor->isManager()) {
                return response()->json(['message' => 'Only managers can mark attendance for staff'], 403);
            }
            $target = User::findOrFail($data['employee_id']);
            $source = 'manager';
            $skip = true;
        }

        if (! $target->mobile_attendance && $source === 'mobile') {
            return response()->json(['message' => 'Mobile attendance is disabled for this user'], 422);
        }

        $type = strtolower($data['type']);
        $todayStr = now('Asia/Kolkata')->toDateString();
        $lastPunch = AttendancePunch::query()
            ->where('user_id', $target->id)
            ->whereDate('work_date', $todayStr)
            ->orderByDesc('punched_at')
            ->first();

        // Auto toggle Punch OUT if user is already IN, avoiding "Already punched IN" error!
        if ($type === 'in' && $lastPunch && strtolower($lastPunch->type) === 'in') {
            $type = 'out';
        }

        $punch = $attendance->punch(
            $target,
            $type,
            (float) $data['lat'],
            (float) $data['lng'],
            $data['location_text'] ?? null,
            $request->file('photo'),
            $request->boolean('face_detected'),
            $source,
            $actor,
            $skip,
        );

        $nextAction = strtolower($punch->type) === 'in' ? 'out' : 'in';

        return response()->json([
            'punch' => $this->serialize($punch),
            'greeting' => $punch->greeting,
            'speak' => $punch->greeting,
            'next_action' => $nextAction,
            'inside' => GeoService::insideOffice((float) $data['lat'], (float) $data['lng']),
        ]);
    }

    public function today(Request $request)
    {
        $userId = $request->user()->id;
        $todayStr = now('Asia/Kolkata')->toDateString();

        $punches = AttendancePunch::query()
            ->where('user_id', $userId)
            ->whereDate('work_date', $todayStr)
            ->orderBy('punched_at')
            ->get();

        $last = $punches->last();

        return response()->json([
            'punches' => $punches->map(fn ($p) => $this->serialize($p)),
            'next_action' => ($last && strtolower($last->type) === 'in') ? 'out' : 'in',
        ]);
    }

    public function statistics(Request $request)
    {
        $user = $request->user();
        $todayStr = now('Asia/Kolkata')->toDateString();

        $usersQuery = User::query();
        if (!$user->isManager()) {
            $usersQuery->where('id', $user->id);
        }

        $allUsers = $usersQuery->get();
        $activeUsers = $allUsers->where('status', 'active');
        $archivedUsers = $allUsers->where('status', '!=', 'active');

        $punchesToday = AttendancePunch::query()
            ->whereDate('work_date', $todayStr)
            ->orderBy('punched_at')
            ->get()
            ->groupBy('user_id');

        $presentUserIds = [];
        $notMarkedUserIds = [];
        $lateUserIds = [];
        $earlyUserIds = [];
        $absentUserIds = [];
        $leaveUserIds = [];

        $employeesList = [];

        foreach ($activeUsers as $u) {
            $uPunches = $punchesToday->get($u->id, collect());
            $firstIn = $uPunches->firstWhere('type', 'in');
            $lastOut = $uPunches->where('type', 'out')->last();

            $isPresent = $uPunches->where('type', 'in')->count() > 0;
            $isNotMarked = !$isPresent;
            $isLate = false;
            $isEarly = false;
            $isAbsent = false;
            $isLeave = false;

            if ($isPresent) {
                $presentUserIds[] = $u->id;
                $shiftStart = $u->shift?->start_time;
                if ($shiftStart && $firstIn && $firstIn->punched_at->format('H:i:s') > $shiftStart) {
                    $isLate = true;
                    $lateUserIds[] = $u->id;
                }
                $shiftEnd = $u->shift?->end_time;
                if ($shiftEnd && $lastOut && $lastOut->punched_at->format('H:i:s') < $shiftEnd) {
                    $isEarly = true;
                    $earlyUserIds[] = $u->id;
                }
            } else {
                $notMarkedUserIds[] = $u->id;
            }

            $employeesList[] = [
                'id' => $u->id,
                'name' => $u->displayName(),
                'first_name' => $u->first_name,
                'last_name' => $u->last_name,
                'phone' => $u->phone,
                'email' => $u->email,
                'role' => $u->role,
                'company_id' => $u->company_id,
                'company_name' => $u->company?->name ?? 'Default Company',
                'department' => $u->department ?: 'General',
                'designation' => $u->designation ?: 'Employee',
                'photo' => $u->profile_photo ? url('storage/'.$u->profile_photo) : null,
                'status' => $u->status,
                'today_status' => $isPresent ? ($isLate ? 'late' : 'present') : 'not_marked',
                'is_present' => $isPresent,
                'is_not_marked' => $isNotMarked,
                'is_late' => $isLate,
                'is_early' => $isEarly,
                'is_absent' => $isAbsent,
                'is_leave' => $isLeave,
                'in_time' => $firstIn ? $firstIn->punched_at->format('h:i A') : null,
                'out_time' => $lastOut ? $lastOut->punched_at->format('h:i A') : null,
                'in_photo' => $firstIn && $firstIn->photo ? url('storage/'.$firstIn->photo) : null,
                'out_photo' => $lastOut && $lastOut->photo ? url('storage/'.$lastOut->photo) : null,
            ];
        }

        $todayObj = now('Asia/Kolkata')->startOfDay();
        $todayBirthdays = User::whereNotNull('birthday')
            ->where('status', 'active')
            ->get()
            ->filter(function ($b) use ($todayObj) {
                if (!$b->birthday) return false;
                $bdayThisYear = \Carbon\Carbon::createFromDate($todayObj->year, $b->birthday->month, $b->birthday->day)->startOfDay();
                if ($bdayThisYear->lt($todayObj)) {
                    $bdayThisYear->addYear();
                }
                $diffDays = $todayObj->diffInDays($bdayThisYear, false);
                return $diffDays >= 0 && $diffDays <= 7;
            })
            ->map(function ($b) use ($todayObj) {
                $bdayThisYear = \Carbon\Carbon::createFromDate($todayObj->year, $b->birthday->month, $b->birthday->day)->startOfDay();
                if ($bdayThisYear->lt($todayObj)) {
                    $bdayThisYear->addYear();
                }
                $diffDays = (int) $todayObj->diffInDays($bdayThisYear, false);
                return [
                    'id' => $b->id,
                    'name' => $b->displayName(),
                    'photo' => $b->profile_photo ? url('storage/'.$b->profile_photo) : null,
                    'age' => \Carbon\Carbon::parse($b->birthday)->age,
                    'department' => $b->department ?: ($b->company?->name ?? 'Staff'),
                    'days_until' => $diffDays,
                    'date_formatted' => \Carbon\Carbon::parse($b->birthday)->format('d-m-Y'),
                    'is_today' => $diffDays === 0,
                ];
            })
            ->values();

        $counts = [
            'not_marked' => count($notMarkedUserIds),
            'present' => count($presentUserIds),
            'absence' => count($absentUserIds),
            'late' => count($lateUserIds),
            'leave' => count($leaveUserIds),
            'early' => count($earlyUserIds),
            'heads' => $activeUsers->count(),
            'archived' => $archivedUsers->count(),
            'admin' => $activeUsers->where('role', 'admin')->count(),
            'manager' => $activeUsers->where('role', 'manager')->count(),
            'employee' => $activeUsers->where('role', 'employee')->count(),
        ];

        return response()->json([
            'date' => now('Asia/Kolkata')->format('M d, Y'),
            'counts' => $counts,
            'employees' => $employeesList,
            'birthdays' => $todayBirthdays,
        ]);
    }

    public function history(Request $request, AttendanceService $attendance)
    {
        $userId = $request->get('user_id') ?: $request->user()->id;
        $targetUser = User::find($userId) ?: $request->user();

        $year = (int) $request->get('year', now('Asia/Kolkata')->year);
        $month = (int) $request->get('month', now('Asia/Kolkata')->month);
        $summary = $attendance->monthSummary($targetUser, $year, $month);

        return response()->json([
            'employee' => [
                'id' => $targetUser->id,
                'name' => $targetUser->displayName(),
                'phone' => $targetUser->phone ?: '+918169426418',
                'email' => $targetUser->email ?: 'arvinddubey999@gmail.com',
                'designation' => $targetUser->designation ?: 'Staff Member',
                'photo' => $targetUser->photo ? url('storage/'.$targetUser->photo) : '',
            ],
            'present' => $summary['present'],
            'absent' => $summary['absent'],
            'leave' => $summary['leave'],
            'week_off' => $summary['weekOff'],
            'late' => $summary['late'],
            'half' => $summary['half'],
            'days' => collect($summary['rows'])->map(fn ($row) => [
                'date' => $row['date'],
                'status' => $row['status'],
                'has_in_no_out' => $row['has_in_no_out'] ?? false,
                'hours' => $row['hours'],
                'in' => optional($row['ins']->first())->punched_at?->format('h:i A'),
                'out' => optional($row['outs']->last())->punched_at?->format('h:i A'),
                'ins_list' => $row['ins']->map(fn ($p) => $this->serialize($p)),
                'outs_list' => $row['outs']->map(fn ($p) => $this->serialize($p)),
            ]),
        ]);
    }

    public function monthlyReport(Request $request, AttendanceService $attendance)
    {
        $userId = $request->get('user_id') ?: $request->user()->id;
        $targetUser = User::findOrFail($userId);

        $year = (int) $request->get('year', now('Asia/Kolkata')->year);
        $month = (int) $request->get('month', now('Asia/Kolkata')->month);

        return view('employees.monthly-print', [
            'staff' => $targetUser,
            'summary' => $attendance->monthSummary($targetUser, $year, $month),
            'year' => $year,
            'month' => $month,
        ]);
    }

    public function registerFcmToken(Request $request)
    {
        $data = $request->validate([
            'fcm_token' => 'required|string',
        ]);

        $user = $request->user();
        if ($user) {
            $user->forceFill(['fcm_token' => $data['fcm_token']])->save();
        }

        return response()->json([
            'ok' => true,
            'message' => 'FCM Token registered successfully.',
            'fcm_token' => $user?->fcm_token,
        ]);
    }

    public function manualStore(Request $request)
    {
        $data = $request->validate([
            'user_id' => 'required|exists:users,id',
            'status' => 'required|string',
            'shift_id' => 'nullable|exists:shifts,id',
            'work_date' => 'required|date',
            'time' => 'nullable',
            'location_text' => 'nullable|string',
            'remarks' => 'nullable|string',
        ]);

        $actor = $request->user();
        $targetUser = User::findOrFail($data['user_id']);

        if (!$actor->isAdmin() && !$actor->isManager() && $actor->id !== $targetUser->id) {
            return response()->json(['message' => 'Unauthorized to edit attendance'], 403);
        }

        $type = strtolower($data['status']);
        if (in_array($type, ['in', 'out'])) {
            $timeStr = !empty($data['time']) ? $data['time'] : '09:45:00';
            $punchedAt = \Carbon\Carbon::parse($data['work_date'] . ' ' . $timeStr);

            $punch = AttendancePunch::create([
                'user_id' => $targetUser->id,
                'work_date' => $data['work_date'],
                'type' => $type,
                'source' => 'manual',
                'punched_at' => $punchedAt,
                'location_text' => $data['location_text'] ?: 'Manual Entry',
                'remarks' => $data['remarks'] ?? null,
                'face_detected' => true,
            ]);
            return response()->json(['ok' => true, 'message' => 'Attendance punch created successfully.', 'punch' => $this->serialize($punch)]);
        }

        return response()->json(['ok' => true, 'message' => "Attendance status marked as {$data['status']}."]);
    }

    public function updatePunch(Request $request, AttendancePunch $punch)
    {
        $data = $request->validate([
            'status' => 'nullable|string',
            'time' => 'nullable',
            'location_text' => 'nullable|string',
            'remarks' => 'nullable|string',
        ]);

        $actor = $request->user();
        if (!$actor->isAdmin() && !$actor->isManager() && $actor->id !== $punch->user_id) {
            return response()->json(['message' => 'Unauthorized to update punch'], 403);
        }

        $updates = [];
        if (!empty($data['status'])) {
            $updates['type'] = strtolower($data['status']);
        }
        if (!empty($data['time'])) {
            $workDate = $punch->work_date->toDateString();
            $updates['punched_at'] = \Carbon\Carbon::parse($workDate . ' ' . $data['time']);
        }
        if (isset($data['location_text'])) {
            $updates['location_text'] = $data['location_text'];
        }
        if (isset($data['remarks'])) {
            $updates['remarks'] = $data['remarks'];
        }

        $punch->update($updates);

        return response()->json(['ok' => true, 'message' => 'Attendance punch updated successfully.', 'punch' => $this->serialize($punch)]);
    }

    public function destroyPunch(Request $request, AttendancePunch $punch)
    {
        $actor = $request->user();
        if (!$actor->isAdmin() && !$actor->isManager() && $actor->id !== $punch->user_id) {
            return response()->json(['message' => 'Unauthorized to delete punch'], 403);
        }

        $punch->delete();
        return response()->json(['ok' => true, 'message' => 'Attendance punch deleted successfully.']);
    }

    private function serialize(AttendancePunch $punch): array
    {
        return [
            'id' => $punch->id,
            'type' => strtoupper($punch->type),
            'source' => $punch->source,
            'time' => $punch->punched_at->format('h:i A'),
            'at' => $punch->punched_at->toIso8601String(),
            'location' => $punch->location_text ?: 'Office Location',
            'photo' => $punch->photo ? url('storage/'.$punch->photo) : null,
            'greeting' => $punch->greeting,
        ];
    }
}
