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
                return response()->json(['message' => 'Only managers can mark attendance for keypad staff'], 403);
            }
            $target = User::findOrFail($data['employee_id']);
            $source = 'manager';
            $skip = true;
        }

        if (! $target->mobile_attendance && $source === 'mobile') {
            return response()->json(['message' => 'Mobile attendance is disabled for this user'], 422);
        }

        $punch = $attendance->punch(
            $target,
            $data['type'],
            (float) $data['lat'],
            (float) $data['lng'],
            $data['location_text'] ?? null,
            $request->file('photo'),
            $request->boolean('face_detected'),
            $source,
            $actor,
            $skip,
        );

        return response()->json([
            'punch' => $this->serialize($punch),
            'greeting' => $punch->greeting,
            'speak' => $punch->greeting,
            'inside' => GeoService::insideOffice((float) $data['lat'], (float) $data['lng']),
        ]);
    }

    public function today(Request $request)
    {
        $punches = AttendancePunch::query()
            ->where('user_id', $request->user()->id)
            ->whereDate('work_date', now('Asia/Kolkata')->toDateString())
            ->orderBy('punched_at')
            ->get()
            ->map(fn ($p) => $this->serialize($p));

        $last = $punches->last();

        return response()->json([
            'punches' => $punches,
            'next_action' => ($last && $last['type'] === 'in') ? 'out' : 'in',
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

    public function registerFcmToken(Request $request)
    {
        $data = $request->validate([
            'fcm_token' => 'required|string',
        ]);

        $request->user()->update(['fcm_token' => $data['fcm_token']]);

        return response()->json([
            'ok' => true,
            'message' => 'FCM Token registered successfully.',
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
