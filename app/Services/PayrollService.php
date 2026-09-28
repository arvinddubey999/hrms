<?php

namespace App\Services;

use App\Models\User;

class PayrollService
{
    public function compute(User $user, int $year, int $month): array
    {
        $summary = app(AttendanceService::class)->monthSummary($user, $year, $month);
        $daily = $user->dailyRate();
        $payableDays = max(0, $summary['present'] + $summary['weekOff'] + $summary['leave']);
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
        $gross = $basic + $incentiveTotal;
        $net = $gross - $advanceTotal;

        return [
            'summary' => $summary,
            'daily' => $daily,
            'payable_days' => $payableDays,
            'basic' => $basic,
            'advances' => $advances,
            'incentives' => $incentives,
            'advance_total' => $advanceTotal,
            'incentive_total' => $incentiveTotal,
            'gross' => $gross,
            'net' => $net,
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
