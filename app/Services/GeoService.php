<?php

namespace App\Services;

use App\Models\Setting;
use App\Models\User;

class GeoService
{
    public static function distanceMeters(float $lat1, float $lng1, float $lat2, float $lng2): float
    {
        $earth = 6371000;
        $dLat = deg2rad($lat2 - $lat1);
        $dLng = deg2rad($lng2 - $lng1);
        $a = sin($dLat / 2) ** 2 + cos(deg2rad($lat1)) * cos(deg2rad($lat2)) * sin($dLng / 2) ** 2;

        return 2 * $earth * atan2(sqrt($a), sqrt(1 - $a));
    }

    public static function insideOffice(?float $lat, ?float $lng, ?Setting $setting = null): bool
    {
        if ($lat === null || $lng === null) {
            return false;
        }
        $setting ??= Setting::current();
        $meters = self::distanceMeters($lat, $lng, (float) $setting->office_lat, (float) $setting->office_lng);

        return $meters <= $setting->geofence_radius_m;
    }

    public static function greetingFor(User $user, ?\DateTimeInterface $at = null): string
    {
        $hour = (int) ($at?->format('G') ?? now('Asia/Kolkata')->format('G'));
        $name = strtoupper($user->first_name ?: $user->displayName());
        if ($hour < 12) {
            $part = 'GOOD MORNING';
        } elseif ($hour < 17) {
            $part = 'GOOD AFTERNOON';
        } else {
            $part = 'GOOD EVENING';
        }

        return "{$part} {$name}";
    }
}
