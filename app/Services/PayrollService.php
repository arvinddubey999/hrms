<?php

namespace App\Services;

use App\Models\User;

class PayrollService
{
    public function compute(User $user, int $year, int $month): array
    {
        $daysInMonth = \Carbon\Carbon::createFromDate($year, $month, 1)->daysInMonth;
        $summary = app(AttendanceService::class)->monthSummary($user, $year, $month);
        
        $daily = $user->dailyRate($daysInMonth);
        
        // Payable Days = Present + Week Off + Holiday + Leave
        $payableDays = max(0, ($summary['present'] ?? 0) + ($summary['weekOff'] ?? 0) + ($summary['holiday'] ?? 0) + ($summary['leave'] ?? 0));
        
        // Basic Salary for Month
        $basic = round($daily * $payableDays, 2);
        
        $advances = $user->advances()
            ->whereYear('paid_on', $year)
            ->whereMonth('paid_on', $month)
            ->get();
            
        $incentives = $user->incentives()
            ->whereYear('paid_on', $year)
            ->whereMonth('paid_on', $month)
            ->get();
            
        $advanceTotal = (float) $advances->sum('amount');
        $incentiveTotal = (float) $incentives->sum('amount');
        $gross = round($basic + $incentiveTotal, 2);

        // Statutory Calculations as per Govt Rules:
        // 1. ESI (Employee: 0.75%, Employer: 3.25% - Applicable if Gross <= Rs. 21,000)
        $employeeEsi = 0.00;
        $employerEsi = 0.00;
        if ($user->esi_applicable && $user->salary <= 21000) {
            $employeeEsi = round($gross * 0.0075, 2);
            $employerEsi = round($gross * 0.0325, 2);
        }

        // 2. PF (Employee: 12% capped at Rs. 15,000 basic = max 1800, Employer: 12%)
        $employeePf = 0.00;
        $employerPf = 0.00;
        if (!empty($user->pf_number) || !empty($user->uan)) {
            $pfBasicCap = min($basic, 15000);
            $employeePf = round($pfBasicCap * 0.12, 2);
            $employerPf = round($pfBasicCap * 0.12, 2);
        }

        $totalDeductions = round($advanceTotal + $employeeEsi + $employeePf, 2);
        $net = max(0, round($gross - $totalDeductions, 2));

        $remarksNote = sprintf(
            "Month: %02d/%d (%d days). Daily Rate: Rs. %.2f/day (%s). Payable Days: %d. Basic: Rs. %.2f.",
            $month, $year, $daysInMonth, $daily, "Salary/".$daysInMonth, $payableDays, $basic
        );

        return [
            'summary' => $summary,
            'daily' => $daily,
            'days_in_month' => $daysInMonth,
            'payable_days' => $payableDays,
            'basic' => $basic,
            'advances' => $advances,
            'incentives' => $incentives,
            'advance_total' => $advanceTotal,
            'incentive_total' => $incentiveTotal,
            'employee_esi' => $employeeEsi,
            'employer_esi' => $employerEsi,
            'employee_pf' => $employeePf,
            'employer_pf' => $employerPf,
            'total_deductions' => $totalDeductions,
            'gross' => $gross,
            'net' => $net,
            'remarks' => $remarksNote,
            'year' => $year,
            'month' => $month,
        ];
    }

    public static function inWords(float $amount): string
    {
        $rupees = (int) floor($amount);
        $paise = (int) round(($amount - $rupees) * 100);
        $words = self::numberToWords($rupees).' Rupees';
        if ($paise > 0) {
            $words .= ' and '.self::numberToWords($paise).' Paise';
        }

        return $words.' Only';
    }

    private static function numberToWords(int $number): string
    {
        if ($number === 0) {
            return 'Zero';
        }
        $ones = ['', 'One', 'Two', 'Three', 'Four', 'Five', 'Six', 'Seven', 'Eight', 'Nine', 'Ten', 'Eleven', 'Twelve', 'Thirteen', 'Fourteen', 'Fifteen', 'Sixteen', 'Seventeen', 'Eighteen', 'Nineteen'];
        $tens = ['', '', 'Twenty', 'Thirty', 'Forty', 'Fifty', 'Sixty', 'Seventy', 'Eighty', 'Ninety'];

        $convert = function (int $n) use (&$convert, $ones, $tens): string {
            if ($n < 20) {
                return $ones[$n];
            }
            if ($n < 100) {
                return trim($tens[intdiv($n, 10)].' '.$ones[$n % 10]);
            }
            if ($n < 1000) {
                return trim($ones[intdiv($n, 100)].' Hundred '.$convert($n % 100));
            }
            if ($n < 100000) {
                return trim($convert(intdiv($n, 1000)).' Thousand '.$convert($n % 1000));
            }
            if ($n < 10000000) {
                return trim($convert(intdiv($n, 100000)).' Lakh '.$convert($n % 100000));
            }

            return trim($convert(intdiv($n, 10000000)).' Crore '.$convert($n % 10000000));
        };

        return trim($convert($number));
    }
}
