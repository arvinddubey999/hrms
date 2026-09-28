<?php

namespace App\Services;

use App\Models\AttendancePunch;
use App\Models\LeaveRequest;
use App\Models\Setting;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Validation\ValidationException;

class AttendanceService
{
    public function punch(
        User $user,
        string $type,
        ?float $lat,
        ?float $lng,
        ?string $locationText,
        ?UploadedFile $photo,
        bool $faceDetected,
        string $source = 'mobile',
        ?User $markedBy = null,
        bool $skipGeofence = false,
    ): AttendancePunch {
        $setting = Setting::current();
        $now = now('Asia/Kolkata');
        $type = strtolower($type) === 'out' ? 'out' : 'in';

        if ($user->punch_from === 'geofence' && ! $skipGeofence && $source === 'mobile') {
            if (! GeoService::insideOffice($lat, $lng, $setting)) {
                throw ValidationException::withMessages(['lat' => 'You are outside the company location. Attendance can be marked only at office.']);
            }
        }

        if ($user->ai_selfie && $source === 'mobile' && ! $skipGeofence) {
            if (! $faceDetected) {
                throw ValidationException::withMessages(['photo' => 'Face not detected. Please take a clear selfie.']);
            }
        }

        $openIn = AttendancePunch::query()
            ->where('user_id', $user->id)
            ->whereDate('work_date', $now->toDateString())
            ->where('type', 'in')
            ->orderByDesc('punched_at')
            ->first();

        $last = AttendancePunch::query()
            ->where('user_id', $user->id)
            ->whereDate('work_date', $now->toDateString())
            ->orderByDesc('punched_at')
            ->first();

        if (! $user->multiple_attendance) {
            if ($type === 'in' && $openIn && (! $last || $last->type === 'in')) {
                throw ValidationException::withMessages(['type' => 'Already punched IN today.']);
            }
            if ($type === 'out' && (! $openIn || ($last && $last->type === 'out'))) {
                throw ValidationException::withMessages(['type' => 'No open IN punch to mark OUT.']);
            }
        }

        $path = null;
        if ($photo) {
            $path = $photo->store('punches', 'public');
        }

        $greeting = $type === 'in' ? GeoService::greetingFor($user, $now) : 'Have a good day '.$user->first_name;

        return AttendancePunch::query()->create([
            'user_id' => $user->id,
            'marked_by' => $markedBy?->id,
            'work_date' => $now->toDateString(),
            'type' => $type,
            'source' => $source,
            'punched_at' => $now,
            'lat' => $lat,
            'lng' => $lng,
            'location_text' => $locationText ?: $this->fallbackLocation($lat, $lng, $setting),
            'photo' => $path,
            'face_detected' => $faceDetected,
            'greeting' => $greeting,
        ]);
    }

    public function autoOutIfOutside(User $user, float $lat, float $lng, ?string $address = null): ?AttendancePunch
    {
        if ($user->punch_from !== 'geofence') {
            return null;
        }
        if (GeoService::insideOffice($lat, $lng)) {
            return null;
        }

        $today = now('Asia/Kolkata')->toDateString();
        $last = AttendancePunch::query()
            ->where('user_id', $user->id)
            ->whereDate('work_date', $today)
            ->orderByDesc('punched_at')
            ->first();

        if (! $last || $last->type !== 'in') {
            return null;
        }

        return AttendancePunch::query()->create([
            'user_id' => $user->id,
            'work_date' => $today,
            'type' => 'out',
            'source' => 'geofence',
            'punched_at' => now('Asia/Kolkata'),
            'lat' => $lat,
            'lng' => $lng,
            'location_text' => $address ?: 'Auto OUT — left company location',
            'face_detected' => false,
            'greeting' => 'You left the company location. Attendance marked OUT.',
        ]);
    }

    public function dayStatus(User $user, Carbon $date): string
    {
        $leave = LeaveRequest::query()
            ->where('user_id', $user->id)
            ->where('status', 'approved')
            ->whereDate('from_date', '<=', $date)
            ->whereDate('to_date', '>=', $date)
            ->exists();
        if ($leave) {
            return 'leave';
        }
        if (strcasecmp($user->week_off_day, $date->format('l')) === 0) {
            return 'week_off';
        }
        $hasIn = AttendancePunch::query()
            ->where('user_id', $user->id)
            ->whereDate('work_date', $date->toDateString())
            ->where('type', 'in')
            ->exists();
        if ($hasIn) {
            $firstIn = AttendancePunch::query()
                ->where('user_id', $user->id)
                ->whereDate('work_date', $date->toDateString())
                ->where('type', 'in')
                ->orderBy('punched_at')
                ->first();
            $shiftStart = $user->shift?->start_time;
            if ($shiftStart && $firstIn && $firstIn->punched_at->format('H:i:s') > $shiftStart) {
                return 'late';
            }

            return 'present';
        }
        if ($date->isToday() || $date->isFuture()) {
            return 'not_marked';
        }

        return 'absent';
    }

    public function monthSummary(User $user, int $year, int $month): array
    {
        $start = Carbon::create($year, $month, 1);
        $days = $start->daysInMonth;
        $present = $absent = $leave = $weekOff = $late = $holiday = $half = 0;
        $rows = [];

        for ($d = 1; $d <= $days; $d++) {
            $date = Carbon::create($year, $month, $d);
            $status = $this->dayStatus($user, $date);
            if ($status === 'present') {
                $present++;
            } elseif ($status === 'late') {
                $late++;
                $present++;
            } elseif ($status === 'absent') {
                $absent++;
            } elseif ($status === 'leave') {
                $leave++;
            } elseif ($status === 'week_off') {
                $weekOff++;
            }
            $ins = AttendancePunch::query()
                ->where('user_id', $user->id)
                ->whereDate('work_date', $date->toDateString())
                ->where('type', 'in')
                ->orderBy('punched_at')
                ->get();
            $outs = AttendancePunch::query()
                ->where('user_id', $user->id)
                ->whereDate('work_date', $date->toDateString())
                ->where('type', 'out')
                ->orderBy('punched_at')
                ->get();

            $minutes = 0;
            $pairs = max($ins->count(), $outs->count());
            for ($i = 0; $i < $pairs; $i++) {
                if (isset($ins[$i], $outs[$i])) {
                    $minutes += $ins[$i]->punched_at->diffInMinutes($outs[$i]->punched_at);
                }
            }
            $rows[] = [
                'date' => $date->toDateString(),
                'status' => $status,
                'ins' => $ins,
                'outs' => $outs,
                'hours' => sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60),
            ];
        }

        $payable = $present + $weekOff + $leave;

        return compact('present', 'absent', 'leave', 'weekOff', 'late', 'holiday', 'half', 'days', 'rows', 'payable');
    }

    private function fallbackLocation(?float $lat, ?float $lng, Setting $setting): string
    {
        if ($lat && $lng && GeoService::insideOffice($lat, $lng, $setting)) {
            return $setting->company_name.' office';
        }
        if ($lat && $lng) {
            return round($lat, 5).', '.round($lng, 5);
        }

        return 'Unknown';
    }
}
