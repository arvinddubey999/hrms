<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\PayrollService;
use Illuminate\Http\Request;

class PayrollController extends Controller
{
    public function me(Request $request, PayrollService $payroll)
    {
        $user = $request->user();
        if (! $user->view_self_salary) {
            return response()->json(['message' => 'Salary view is disabled'], 403);
        }
        $year = (int) $request->get('year', now()->year);
        $month = (int) $request->get('month', now()->month);
        $pay = $payroll->compute($user, $year, $month);

        return response()->json([
            'pay_type' => $user->pay_type,
            'salary' => (float) $user->salary,
            'daily_rate' => $pay['daily'],
            'present_days' => $pay['summary']['present'],
            'absent_days' => $pay['summary']['absent'],
            'week_offs' => $pay['summary']['weekOff'],
            'payable_days' => $pay['payable_days'],
            'basic' => $pay['basic'],
            'incentives' => $pay['incentive_total'],
            'advances' => $pay['advance_total'],
            'net' => $pay['net'],
            'year' => $year,
            'month' => $month,
        ]);
    }
}
