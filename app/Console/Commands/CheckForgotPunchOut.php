<?php

namespace App\Console\Commands;

use App\Services\AttendanceService;
use Illuminate\Console\Command;

class CheckForgotPunchOut extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'attendance:check-forgot-punchout';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Check employees whose shift ended 30+ minutes ago and forgot to punch out, and dispatch push notifications';

    /**
     * Execute the console command.
     */
    public function handle(AttendanceService $attendanceService)
    {
        $this->info('Checking for employees who forgot to punch out 30+ minutes after shift end...');

        $remindersSent = $attendanceService->checkAndSendForgotPunchOutReminders();

        $this->info("Completed. Dispatched {$remindersSent} punch out reminder notification(s).");

        return Command::SUCCESS;
    }
}
