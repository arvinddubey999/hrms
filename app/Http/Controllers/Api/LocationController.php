<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AttendanceService;
use App\Services\GeoService;
use Illuminate\Http\Request;

class LocationController extends Controller
{
    public function ping(Request $request, AttendanceService $attendance)
    {
        $data = $request->validate([
            'lat' => 'required|numeric',
            'lng' => 'required|numeric',
            'accuracy' => 'nullable|integer',
            'address' => 'nullable|string',
        ]);
        $user = $request->user();
        $inside = GeoService::insideOffice((float) $data['lat'], (float) $data['lng']);

        $user->forceFill([
            'last_lat' => $data['lat'],
            'last_lng' => $data['lng'],
            'last_location_at' => now(),
        ])->save();

        $user->locationPings()->create([
            'lat' => $data['lat'],
            'lng' => $data['lng'],
            'accuracy' => $data['accuracy'] ?? null,
            'address' => $data['address'] ?? null,
            'inside_geofence' => $inside,
            'pinged_at' => now(),
        ]);

        $autoOut = null;
        if (! $inside) {
            $autoOut = $attendance->autoOutIfOutside($user, (float) $data['lat'], (float) $data['lng'], $data['address'] ?? null);
        }

        return response()->json([
            'inside' => $inside,
            'auto_out' => $autoOut ? [
                'id' => $autoOut->id,
                'greeting' => $autoOut->greeting,
            ] : null,
        ]);
    }
}
