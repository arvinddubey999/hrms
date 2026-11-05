<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

// Delete any Paid Leave requests
$deleted = App\Models\LeaveRequest::where('leave_type', 'Paid Leave')->delete();
echo "Deleted {$deleted} Paid Leave requests.\n";

