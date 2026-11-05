<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$s = App\Models\Setting::current();
echo "Setting: office_lat={$s->office_lat}, office_lng={$s->office_lng}, radius={$s->geofence_radius_m}\n";

$fences = App\Models\CompanyGeofence::all();
echo "Total geofences: " . $fences->count() . "\n";
foreach ($fences as $f) {
    echo "Fence: {$f->name}, lat={$f->latitude}, lng={$f->longitude}, radius={$f->radius}, status={$f->status}\n";
}

