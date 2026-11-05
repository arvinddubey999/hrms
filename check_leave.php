<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

dump(config('database.connections.mysql'));
echo "Total users: " . App\Models\User::count() . "\n";
foreach (App\Models\User::take(5)->get() as $u) {
    echo "User: {$u->name}, Code: {$u->employee_code}\n";
}
