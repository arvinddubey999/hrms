<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\AttendancePunch;
use App\Models\User;
use App\Services\AttendanceService;
use App\Services\FaceService;
use App\Services\GeoService;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function punch(Request $request, AttendanceService $attendance)
    {
        $data = $request->validate([
            'type' => 'nullable|in:in,out',
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
            'location_text' => 'nullable|string',
            'face_detected' => 'nullable|boolean',
            'photo' => 'nullable|file|max:12288',
            'employee_id' => 'nullable|exists:users,id',
            'auto_detect' => 'nullable|boolean',
            'battery' => 'nullable',
        ]);

        $actor = $request->user();
        $target = $actor;
        $source = 'mobile';
        $skip = false;

        // 1. Manager Mode Auto-Detect: Detect employee from face scan
        if ($request->boolean('auto_detect')) {
            $canMark = $actor->isSuperAdmin() || $actor->isManager() || $actor->hasPermission('attendance.mark') || $actor->hasPermission('attendance.bulk');
            if (!$canMark) {
                return response()->json(['message' => 'You do not have permission to mark attendance for other staff.'], 403);
            }
            if (!$request->hasFile('photo')) {
                return response()->json(['message' => 'Camera face photo is required for face detection.'], 422);
            }

            $recog = FaceService::recognizeStaffFromFace($request->file('photo'), $actor);
            if (!$recog['recognized']) {
                return response()->json([
                    'success' => false,
                    'message' => $recog['reason'] ?? 'Face not recognized! Please ensure the employee\'s face is registered in Edit Staff Member.',
                ], 422);
            }

            $target = $recog['user'];
            $source = 'manager';
            $skip = true;
        } elseif (!empty($data['employee_id']) && (int)$data['employee_id'] !== $actor->id) {
            // Manager manually selected employee
            $canMark = $actor->isSuperAdmin() || $actor->isManager() || $actor->hasPermission('attendance.mark') || $actor->hasPermission('attendance.bulk');
            if (!$canMark) {
                return response()->json(['message' => 'You do not have permission to mark attendance for other staff.'], 403);
            }
            $target = User::findOrFail($data['employee_id']);
            $source = 'manager';
            $skip = true;
        }

        if (!$target->mobile_attendance && $source === 'mobile') {
            return response()->json(['message' => 'Mobile attendance is disabled for this user.'], 422);
        }

        // 2. Face Verification against registered "AI Selfie Verification" images
        if ($request->hasFile('photo')) {
            $ver = FaceService::verifyUserFace($target, $request->file('photo'));
            if (!$ver['matched']) {
                return response()->json([
                    'success' => false,
                    'message' => $ver['reason'] ?? 'Face verification failed! Face does not match registered employee image in Edit Staff Member.',
                ], 422);
            }
        } elseif ($target->ai_selfie) {
            return response()->json([
                'success' => false,
                'message' => "AI Selfie Verification is enabled for {$target->displayName()}. A live face capture is required.",
            ], 422);
        }

        $type = strtolower($data['type'] ?? 'in');
        $todayStr = now('Asia/Kolkata')->toDateString();
        $lastPunch = AttendancePunch::query()
            ->where('user_id', $target->id)
            ->whereDate('work_date', $todayStr)
            ->orderByDesc('punched_at')
            ->first();

        // Auto-toggle punch: If last punch was IN -> punch OUT; if OUT or no punch -> punch IN
        if ($lastPunch && strtolower($lastPunch->type) === 'in') {
            $type = 'out';
        } elseif (!$lastPunch || strtolower($lastPunch->type) === 'out') {
            $type = 'in';
        }

        $punch = $attendance->punch(
            $target,
            $type,
            (float) $data['lat'],
            (float) $data['lng'],
            $data['location_text'] ?? null,
            $request->file('photo'),
            true, // face is verified
            $source,
            $actor,
            $skip,
        );

        $nextAction = strtolower($punch->type) === 'in' ? 'out' : 'in';

        // Update battery if passed
        if ($request->filled('battery')) {
            $cleanBat = preg_replace('/[^0-9]/', '', (string)$request->input('battery'));
            if (is_numeric($cleanBat)) {
                $lastPing = $target->locationPings()->latest('pinged_at')->first();
                if ($lastPing) {
                    $lastPing->update(['battery' => (int)$cleanBat]);
                }
            }
        }

        return response()->json([
            'success' => true,
            'punch' => $this->serialize($punch),
            'employee' => [
                'id' => $target->id,
                'name' => $target->displayName(),
                'employee_code' => $target->employee_code,
                'department' => $target->department?->name ?? (string)$target->department,
                'avatar' => $target->avatarUrl(),
            ],
            'greeting' => $punch->greeting,
            'speak' => $punch->greeting . ", " . strtoupper($type) . " PUNCH MARKED",
            'next_action' => $nextAction,
            'inside' => GeoService::insideOffice((float) $data['lat'], (float) $data['lng'], null, $target),
        ]);
    }

    public function recognizeAndPunch(Request $request, AttendanceService $attendance)
    {
        $request->merge(['auto_detect' => true, 'type' => 'in']);
        return $this->punch($request, $attendance);
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
        $targetDate = $request->get('date') ? \Carbon\Carbon::parse($request->get('date'))->setTimezone('Asia/Kolkata') : now('Asia/Kolkata');
        $targetDateStr = $targetDate->toDateString();

        $usersQuery = User::query();
        if (!$user->isManager()) {
            $usersQuery->where('id', $user->id);
        }

        $allUsers = $usersQuery->get();
        $activeUsers = $allUsers->where('status', 'active');
        $archivedUsers = $allUsers->where('status', '!=', 'active');

        $punchesToday = AttendancePunch::query()
            ->whereDate('work_date', $targetDateStr)
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

            $isHalfDay = false;
            $otMins = 0;
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

                // Check half day & OT
                if ($firstIn && $lastOut) {
                    $workedMins = $firstIn->punched_at->diffInMinutes($lastOut->punched_at);
                    if ($workedMins > 0 && $workedMins < 420) {
                        $isHalfDay = true;
                    }
                    if ($workedMins > 480) {
                        $otMins = $workedMins - 480;
                    }
                }
            } else {
                $notMarkedUserIds[] = $u->id;
            }

            $todayStatus = $isPresent ? ($isHalfDay ? 'half_day' : ($isLate ? 'late' : 'present')) : 'not_marked';

            $durationFormatted = null;
            if ($firstIn && $lastOut) {
                $mins = $firstIn->punched_at->diffInMinutes($lastOut->punched_at);
                $durationFormatted = number_format($mins / 60, 2) . ' hrs';
            } elseif ($firstIn && $targetDateStr === now('Asia/Kolkata')->toDateString()) {
                $mins = $firstIn->punched_at->diffInMinutes(now('Asia/Kolkata'));
                $durationFormatted = number_format(max(0, $mins) / 60, 2) . ' hrs';
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
                'today_status' => $todayStatus,
                'is_present' => $isPresent,
                'is_half_day' => $isHalfDay,
                'is_not_marked' => $isNotMarked,
                'is_late' => $isLate,
                'is_early' => $isEarly,
                'is_absent' => $isAbsent,
                'is_leave' => $isLeave,
                'ot_minutes' => $otMins,
                'ot_hours' => sprintf('%02d:%02d', intdiv($otMins, 60), $otMins % 60),
                'in_time' => $firstIn ? $firstIn->punched_at->format('h:i A') : null,
                'out_time' => $lastOut ? $lastOut->punched_at->format('h:i A') : null,
                'duration' => $durationFormatted,
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
            'admin' => $activeUsers->filter(fn($u) => in_array(strtolower(trim($u->role ?? '')), ['admin', 'super admin', 'superadmin'], true))->count(),
            'manager' => $activeUsers->filter(fn($u) => strtolower(trim($u->role ?? '')) === 'manager')->count(),
            'employee' => $activeUsers->filter(fn($u) => !in_array(strtolower(trim($u->role ?? '')), ['admin', 'super admin', 'superadmin', 'manager'], true))->count(),
        ];

        return response()->json([
            'date' => $targetDate->format('M d, Y'),
            'raw_date' => $targetDateStr,
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
        $token = $request->input('fcm_token')
            ?: $request->input('fcmToken')
            ?: $request->input('device_token')
            ?: $request->input('token');

        if (empty($token)) {
            return response()->json(['ok' => false, 'message' => 'fcm_token is required.'], 422);
        }

        // Auto-check column exists
        try {
            if (!\Illuminate\Support\Facades\Schema::hasColumn('users', 'fcm_token')) {
                \Illuminate\Support\Facades\Schema::table('users', function (\Illuminate\Database\Schema\Blueprint $table) {
                    $table->string('fcm_token', 500)->nullable();
                });
            }
        } catch (\Throwable $e) {}

        $user = $request->user();
        if (!$user && $request->filled('user_id')) {
            $user = User::find($request->input('user_id'));
        }
        if (!$user) {
            $apiToken = $request->bearerToken() ?: $request->header('X-Api-Token') ?: $request->query('token');
            if ($apiToken) {
                $user = User::where('api_token', $apiToken)->first();
            }
        }

        if ($user) {
            $user->fcm_token = $token;
            $user->save();

            \Illuminate\Support\Facades\Log::info("FCM Token registered for User #{$user->id} ({$user->name}): " . substr($token, 0, 30) . "...");

            return response()->json([
                'ok' => true,
                'message' => 'FCM Token registered successfully.',
                'user_id' => $user->id,
                'fcm_token' => $user->fcm_token,
            ]);
        }

        return response()->json(['ok' => false, 'message' => 'Unauthenticated or user not found.'], 401);
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
        $actor = $request->user();
        $canEdit = $actor->isAdmin() || $actor->isManager() || $actor->hasPermission('attendance.edit');
        if (!$canEdit) {
            return response()->json(['message' => 'Unauthorized to update punch'], 403);
        }

        $data = $request->validate([
            'status' => 'nullable|string',
            'time' => 'nullable',
            'location_text' => 'nullable|string',
            'remarks' => 'nullable|string',
            'shift_id' => 'nullable|exists:shifts,id',
        ]);

        $status = strtolower($data['status'] ?? $punch->type);
        if (in_array($status, ['absent', 'week_off', 'holiday', 'leave'])) {
            $punch->delete();
            return response()->json(['ok' => true, 'message' => 'Punch removed and status recorded as ' . strtoupper($status)]);
        }

        $updates = [];
        if ($status === 'half_day') {
            $updates['type'] = 'in';
            $cleanedRemarks = trim(str_ireplace(['DOUBLE_DUTY', 'HALF_DAY'], '', $data['remarks'] ?? $punch->remarks ?? ''));
            $updates['remarks'] = trim(($cleanedRemarks ? $cleanedRemarks . ' | ' : '') . 'HALF_DAY');
        } elseif ($status === 'double_duty') {
            $updates['type'] = 'in';
            $cleanedRemarks = trim(str_ireplace(['DOUBLE_DUTY', 'HALF_DAY'], '', $data['remarks'] ?? $punch->remarks ?? ''));
            $updates['remarks'] = trim(($cleanedRemarks ? $cleanedRemarks . ' | ' : '') . 'DOUBLE_DUTY');
        } elseif (in_array($status, ['wop', 'hop'])) {
            $updates['type'] = 'in';
            $cleanedRemarks = trim(str_ireplace(['DOUBLE_DUTY', 'HALF_DAY', 'WOP', 'HOP'], '', $data['remarks'] ?? $punch->remarks ?? ''));
            $updates['remarks'] = trim(($cleanedRemarks ? $cleanedRemarks . ' | ' : '') . strtoupper($status));
        } else {
            $updates['type'] = in_array($status, ['in', 'out']) ? $status : 'in';
            $cleanedRemarks = trim(str_ireplace(['DOUBLE_DUTY', 'HALF_DAY', 'WOP', 'HOP'], '', $data['remarks'] ?? $punch->remarks ?? ''));
            $updates['remarks'] = $cleanedRemarks;
        }

        if (!empty($data['time'])) {
            $workDate = $punch->work_date->toDateString();
            $updates['punched_at'] = \Carbon\Carbon::parse($workDate . ' ' . $data['time']);
        }
        if (isset($data['location_text'])) {
            $updates['location_text'] = $data['location_text'];
        }

        $punch->update($updates);

        if (!empty($data['shift_id'])) {
            $punch->user?->update(['shift_id' => $data['shift_id']]);
        }

        return response()->json(['ok' => true, 'message' => 'Attendance punch updated successfully.', 'punch' => $this->serialize($punch)]);
    }

    public function destroyPunch(Request $request, AttendancePunch $punch)
    {
        $actor = $request->user();
        $canDelete = $actor->isAdmin() || $actor->isManager() || $actor->hasPermission('attendance.delete') || $actor->hasPermission('attendance.edit');
        if (!$canDelete) {
            return response()->json(['message' => 'Unauthorized to delete punch'], 403);
        }

        $punch->delete();
        return response()->json(['ok' => true, 'message' => 'Attendance punch deleted successfully.']);
    }

    public function checkForgotPunchout(AttendanceService $attendance)
    {
        $count = $attendance->checkAndSendForgotPunchOutReminders();
        return response()->json([
            'ok' => true,
            'message' => "Dispatched {$count} reminder notification(s) for employees who forgot to punch out.",
            'reminders_sent' => $count,
        ]);
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
