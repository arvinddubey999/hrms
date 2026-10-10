<?php

namespace App\Http\Controllers;

use App\Models\Advance;
use App\Models\Category;
use App\Models\Incentive;
use App\Models\User;
use App\Services\PayrollService;
use Illuminate\Http\Request;

class PayrollController extends Controller
{
    public function index(Request $request, PayrollService $payroll)
    {
        $u = $request->user();
        if (!$u || (!$u->hasPermission('payroll.view') && !$u->hasPermission('payroll.process'))) {
            return back()->with('error', 'You do not have permission to view payroll.');
        }

        $month = (int) $request->get('m', now()->month);
        $year = (int) $request->get('y', now()->year);
        $categoryId = $request->get('category');

        $employees = User::query()
            ->where('status', 'active')
            ->when($categoryId, fn ($q) => $q->where('category_id', $categoryId))
            ->orderBy('first_name')
            ->get();

        $rows = $employees->map(function (User $user) use ($payroll, $year, $month) {
            return ['user' => $user, 'pay' => $payroll->compute($user, $year, $month)];
        });

        return view('payroll.index', [
            'rows' => $rows,
            'month' => $month,
            'year' => $year,
            'categories' => Category::orderBy('name')->get(),
            'categoryId' => $categoryId,
        ]);
    }

    public function payslip(User $employee, Request $request, PayrollService $payroll)
    {
        $u = $request->user();
        if (!$u || !$u->hasPermission('payroll.view')) {
            return back()->with('error', 'You do not have permission to view payslips.');
        }
        $month = (int) $request->get('m', now()->month);
        $year = (int) $request->get('y', now()->year);
        $pay = $payroll->compute($employee, $year, $month);

        return view('payroll.payslip', [
            'staff' => $employee,
            'pay' => $pay,
            'words' => PayrollService::inWords($pay['net']),
        ]);
    }

    public function addAdvance(Request $request, User $employee)
    {
        $data = $request->validate([
            'title' => 'required|string',
            'amount' => 'required|numeric',
            'paid_on' => 'required|date',
        ]);
        $employee->advances()->create($data + ['status' => 'paid']);

        return back()->with('ok', 'Advance added.');
    }

    public function addIncentive(Request $request, User $employee)
    {
        $data = $request->validate([
            'title' => 'required|string',
            'amount' => 'required|numeric',
            'paid_on' => 'required|date',
        ]);
        $employee->incentives()->create($data + ['status' => 'paid']);

        return back()->with('ok', 'Incentive added.');
    }

    public function export(Request $request, PayrollService $payroll)
    {
        $month = (int) $request->get('m', now()->month);
        $year = (int) $request->get('y', now()->year);
        $ids = collect($request->get('employees', []))->filter();
        $employees = User::query()
            ->where('status', 'active')
            ->when($ids->isNotEmpty(), fn ($q) => $q->whereIn('id', $ids))
            ->orderBy('first_name')
            ->get();

        $filename = "payslips-{$year}-{$month}.csv";
        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => "attachment; filename=\"{$filename}\"",
        ];

        $callback = function () use ($employees, $payroll, $year, $month) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['Employee', 'Phone', 'Present', 'Payable Days', 'Gross', 'Deductions', 'Net']);
            foreach ($employees as $user) {
                $pay = $payroll->compute($user, $year, $month);
                fputcsv($out, [
                    $user->displayName(),
                    $user->phone,
                    $pay['summary']['present'],
                    $pay['payable_days'],
                    $pay['gross'],
                    $pay['advance_total'],
                    $pay['net'],
                ]);
            }
            fclose($out);
        };

        return response()->stream($callback, 200, $headers);
    }
}
