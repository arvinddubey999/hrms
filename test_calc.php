<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$user = \App\Models\User::where('employee_code', 'TFIPL0116')->orWhere('id', 192)->first();
if (!$user) {
    echo "User not found, taking first active user\n";
    $user = \App\Models\User::where('status', 'active')->first();
}

if ($user) {
    echo "Testing User: " . $user->displayName() . " (ID: {$user->id})\n";
    echo "wop_applicable: " . var_export($user->wop_applicable, true) . "\n";
    echo "hop_applicable: " . var_export($user->hop_applicable, true) . "\n";
    
    $attendance = app(\App\Services\AttendanceService::class);
    $sum = $attendance->monthSummary($user, 2026, 10);
    echo "Summary:\n";
    echo "Present: " . $sum['present'] . "\n";
    echo "Absent: " . $sum['absent'] . "\n";
    echo "Holiday: " . $sum['holiday'] . "\n";
    echo "WeekOff: " . $sum['weekOff'] . "\n";
    echo "WOP: " . $sum['wop'] . "\n";
    echo "HOP: " . $sum['hop'] . "\n";
    echo "Payable: " . $sum['payable'] . "\n";

    $pay = app(\App\Services\PayrollService::class)->compute($user, 2026, 10);
    echo "Payroll:\n";
    echo "Daily Rate: " . $pay['daily'] . "\n";
    echo "Payable Days: " . $pay['payable_days'] . "\n";
    echo "Gross: " . $pay['gross'] . "\n";
    echo "Net: " . $pay['net'] . "\n";
} else {
    echo "No user in database.\n";
}

