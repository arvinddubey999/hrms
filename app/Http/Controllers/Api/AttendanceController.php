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
        $year = (int) $request->get('year', now()->year);
        $month = (int) $request->get('month', now()->month);
        $summary = $attendance->monthSummary($request->user(), $year, $month);

        return response()->json([
            'present' => $summary['present'],
            'absent' => $summary['absent'],
            'leave' => $summary['leave'],
            'week_off' => $summary['weekOff'],
            'late' => $summary['late'],
            'days' => collect($summary['rows'])->map(fn ($row) => [
                'date' => $row['date'],
                'status' => $row['status'],
                'hours' => $row['hours'],
                'in' => optional($row['ins']->first())->punched_at?->format('h:i A'),
                'out' => optional($row['outs']->last())->punched_at?->format('h:i A'),
            ]),
        ]);
    }

    private function serialize(AttendancePunch $punch): array
    {
        return [
            'id' => $punch->id,
            'type' => $punch->type,
            'source' => $punch->source,
            'time' => $punch->punched_at->format('h:i A'),
            'at' => $punch->punched_at->toIso8601String(),
            'location' => $punch->location_text,
            'photo' => $punch->photo ? url('storage/'.$punch->photo) : null,
            'greeting' => $punch->greeting,
        ];
    }
}
