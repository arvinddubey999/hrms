<?php

namespace App\Services;

use App\Models\AttendancePunch;
use App\Models\Holiday;
use App\Models\LeaveRequest;
use App\Models\Setting;
use App\Models\User;
use App\Services\FirebaseNotificationService;
use App\Services\GeoService;
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
        ?string $remarks = null,
        ?string $customWorkDate = null,
        ?string $customPunchedAt = null
    ): AttendancePunch {
        $setting = Setting::current();
        $now = $customPunchedAt ? Carbon::parse($customPunchedAt) : now('Asia/Kolkata');
        $workDate = $customWorkDate ?: ($customPunchedAt ? Carbon::parse($customPunchedAt)->toDateString() : $now->toDateString());
        $type = strtolower($type) === 'out' ? 'out' : 'in';

        if ($user->punch_from === 'geofence' && ! $skipGeofence && $source === 'mobile') {
            if (! GeoService::insideOffice($lat, $lng, $setting, $user)) {
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
            ->whereDate('work_date', $workDate)
            ->where('type', 'in')
            ->orderByDesc('punched_at')
            ->first();

        $last = AttendancePunch::query()
            ->where('user_id', $user->id)
            ->whereDate('work_date', $workDate)
            ->orderByDesc('punched_at')
            ->first();

        if (! $user->multiple_attendance && $source !== 'admin') {
            if ($type === 'in' && $openIn && (! $last || $last->type === 'in')) {
                throw ValidationException::withMessages(['type' => 'Already punched IN for this date.']);
            }
            if ($type === 'out' && (! $openIn || ($last && $last->type === 'out'))) {
                throw ValidationException::withMessages(['type' => 'No open IN punch to mark OUT for this date.']);
            }
        }

        $path = null;
        if ($photo) {
            $path = $photo->store('punches', 'public');
        }

        $greeting = $type === 'in' ? GeoService::greetingFor($user, $now) : 'Have a good day '.$user->first_name;

        $punch = AttendancePunch::query()->create([
            'user_id' => $user->id,
            'marked_by' => $markedBy?->id,
            'work_date' => $workDate,
            'type' => $type,
            'source' => $source,
            'punched_at' => $now,
            'lat' => $lat,
            'lng' => $lng,
            'location_text' => $locationText ?: $this->fallbackLocation($lat, $lng, $setting),
            'photo' => $path,
            'face_detected' => $faceDetected,
            'greeting' => $greeting,
            'remarks' => $remarks,
        ]);

        FirebaseNotificationService::sendPunchNotification($user, $type, $punch->location_text);

        return $punch;
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

        $punch = AttendancePunch::query()->create([
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

        FirebaseNotificationService::sendPunchNotification($user, 'out', $punch->location_text);

        return $punch;
    }

    public function isPresentOnDate(User $user, Carbon $date): bool
    {
        return AttendancePunch::query()
            ->where('user_id', $user->id)
            ->whereDate('work_date', $date->toDateString())
            ->where('type', 'in')
            ->exists();
    }

    public function isWeekOffQualified(User $user, Carbon $date): bool
    {
        // Rule: WEEK OFF UNKO HI MILEGA JO SATURDAY YA MONDAY DONO ME SE KOI BHI EK DIN PRESENT HOGA
        $saturday = $date->copy()->startOfWeek()->addDays(5); // Saturday of that week
        $monday = $date->copy()->startOfWeek();              // Monday of that week

        $presentSat = $this->isPresentOnDate($user, $saturday);
        $presentMon = $this->isPresentOnDate($user, $monday);

        return $presentSat || $presentMon;
    }

    public function isHolidayQualified(User $user, Carbon $holidayDate): bool
    {
        // Check preceding working day (skipping week-off days)
        $prevDay = $holidayDate->copy()->subDay();
        while (strcasecmp($user->week_off_day ?: 'Sunday', $prevDay->format('l')) === 0 && $prevDay->gt($holidayDate->copy()->subDays(7))) {
            $prevDay->subDay();
        }

        // Check succeeding working day (skipping week-off days)
        $nextDay = $holidayDate->copy()->addDay();
        while (strcasecmp($user->week_off_day ?: 'Sunday', $nextDay->format('l')) === 0 && $nextDay->lt($holidayDate->copy()->addDays(7))) {
            $nextDay->addDay();
        }

        return $this->isPresentOnDate($user, $prevDay) || $this->isPresentOnDate($user, $nextDay);
    }

    public function dayStatus(User $user, Carbon $date): string
    {
        $hasIn = $this->isPresentOnDate($user, $date);

        // Check Leave
        $leave = LeaveRequest::query()
            ->where('user_id', $user->id)
            ->where('status', 'approved')
            ->whereDate('from_date', '<=', $date)
            ->whereDate('to_date', '>=', $date)
            ->exists();
        if ($leave) {
            return 'leave';
        }

        // Check Holiday
        $holiday = Holiday::query()
            ->whereDate('date', $date->toDateString())
            ->get()
            ->first(fn($h) => $h->isApplicableToCompany($user->company_id));

        if ($holiday) {
            if ($hasIn) {
                return 'hop'; // Holiday Present (HOP)
            }
            if ($this->isHolidayQualified($user, $date)) {
                return 'holiday';
            } else {
                return 'absent'; // Not qualified for holiday credit
            }
        }

        // Check Week Off
        $isWeekOffDay = strcasecmp($user->week_off_day ?: 'Sunday', $date->format('l')) === 0;

        if ($isWeekOffDay) {
            if ($hasIn) {
                // Rule: WEEK OFF HAI AND WEEK OFF KE DIN AGAR KOI BHI EMPLOYEE PRESENT HOTA HAI TO USKO WOP AANA CHAHIYE , ( WEEK OFF PRESENT )
                return 'wop';
            }
            if ($this->isWeekOffQualified($user, $date)) {
                return 'week_off';
            } else {
                return 'absent';
            }
        }

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
        $present = $absent = $leave = $weekOff = $late = $holiday = $half = $wop = $hop = 0;
        $rows = [];

        for ($d = 1; $d <= $days; $d++) {
            $date = Carbon::create($year, $month, $d);
            $status = $this->dayStatus($user, $date);

            $holidayObj = Holiday::query()
                ->whereDate('date', $date->toDateString())
                ->get()
                ->first(fn($h) => $h->isApplicableToCompany($user->company_id));

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

            // Check Punch IN without OUT condition (half-green half-red indicator requirement!)
            $hasInNoOut = ($ins->count() > 0 && $outs->count() === 0);

            if ($status === 'present') {
                if ($hasInNoOut) {
                    $half++;
                }
                $present++;
            } elseif ($status === 'late') {
                $late++;
                $present++;
            } elseif ($status === 'wop') {
                $wop++;
                $present++;
            } elseif ($status === 'hop') {
                $hop++;
                $present++;
            } elseif ($status === 'absent') {
                $absent++;
            } elseif ($status === 'leave') {
                $leave++;
            } elseif ($status === 'week_off') {
                $weekOff++;
            } elseif ($status === 'holiday') {
                $holiday++;
            }

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
                'holiday_title' => $holidayObj ? ($holidayObj->title ?? $holidayObj->name) : null,
                'has_in_no_out' => $hasInNoOut,
                'ins' => $ins,
                'outs' => $outs,
                'hours' => sprintf('%02d:%02d', intdiv($minutes, 60), $minutes % 60),
            ];
        }

        $payable = $present + $weekOff + $leave + $holiday;

        return compact('present', 'absent', 'leave', 'weekOff', 'late', 'holiday', 'half', 'wop', 'hop', 'days', 'rows', 'payable');
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
