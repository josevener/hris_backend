<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePayrollRequest;
use App\Http\Requests\UpdatePayrollRequest;
use App\Models\Employee;
use App\Models\Payroll;
use App\Models\PayrollItem;
use App\Models\Salary;
use Carbon\Carbon;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PayrollController extends Controller
{
    /**
     * Display a listing of payrolls.
     */
    public function index()
    {
        $payrolls = Payroll::with([
            'employee.user',
            'salary',
            'payroll_items' => function ($query) {
                $query->where(function ($q) {
                    // Specific scope: payroll_id and employee_id are not null
                    $q->where('scope', 'specific')
                        ->whereNotNull('payroll_id')
                        ->whereNotNull('employee_id');
                })->orWhere(function ($q) {
                    // Global scope: payroll_id and employee_id are null
                    $q->where('scope', 'global')
                        ->whereNull('payroll_id')
                        ->whereNull('employee_id');
                });
            }
        ])
            ->whereNull('deleted_at') // Only active payrolls
            ->paginate(6);
        return response()->json($payrolls, 200);
    }

    /**
     * Preview payroll for an employee.
     */
    public function preview(Request $request)
    {
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
        ]);

        $salary = Salary::where('employee_id', $request->employee_id)->firstOrFail();
        $basicSalary = $salary->basic_salary;

        $items = [
            ['type' => 'earning', 'category' => 'Basic Salary', 'amount' => $basicSalary],
            ['type' => 'deduction', 'category' => 'Tax', 'amount' => $basicSalary * 0.1],
        ];

        $totalEarnings = collect($items)->where('type', 'earning')->sum('amount');
        $totalDeductions = collect($items)->where('type', 'deduction')->sum('amount');
        $netSalary = $totalEarnings - $totalDeductions;

        return response()->json([
            'basic_salary' => $basicSalary,
            'total_earnings' => $totalEarnings,
            'total_deductions' => $totalDeductions,
            'net_salary' => $netSalary,
            'items' => $items,
        ], 200);
    }

    /**
     * Show payroll details.
     */
    public function show(Payroll $payroll)
    {
        $payroll->load('employee.user', 'salary');
        return response()->json($payroll, 200);
    }

    /**
     * Store a new payroll.
     */
    public function store(StorePayrollRequest $request)
    {
        // try {
        //     $payroll = Payroll::create($request->validated());

        //     $payroll_item = PayrollItem::where('employee_id', $payroll->employee_id)->first();
        //     $payroll_item->update([
        //         'payroll_id'  => $payroll->id
        //     ]);
        //     return response()->json(['payroll' => $payroll->load('employee')], 201);
        // } catch (Exception $e) {
        //     return response()->json('Error creating payroll for employee: ' . $e->getMessage());
        // }

        try {
            return DB::transaction(function () use ($request) {
                // Create the payroll record
                $payroll = Payroll::create($request->validated());
                // Update all payroll_items with matching employee_id
                $updatedCount = PayrollItem::where('employee_id', $payroll->employee_id)
                    ->whereNull('payroll_id') // Optional: Only update items not already assigned
                    ->update(['payroll_id' => $payroll->id]);

                if ($updatedCount === 0) {
                    // Optional: Log or handle case where no items were updated
                }

                return response()->json($payroll->load('employee'), 201);
            });
        } catch (Exception $e) {
            return response()->json([
                'error' => 'Error creating payroll or updating payroll items: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Update payroll details.
     */
    public function update(UpdatePayrollRequest $request, $id)
    {
        $payroll = Payroll::findOrFail($id);

        $payroll->update($request->validated());

        return response()->json(['payroll' => $payroll->load('employee.user', 'salary')], 200);
    }

    /**
     * Delete payroll.
     */
    public function destroy(Payroll $payroll)
    {
        $payroll->delete();
        return response()->json(['message' => 'Payroll deleted successfully'], 200);
    }

    /**
     * Generate payroll automatically.
     */
    public function generate()
    {
        $salaries = Salary::where('isActive', 1)->with('employee')->get();
        $generatedCount = 0;

        foreach ($salaries as $salary) {
            $payPeriodEnd = $this->calculatePayPeriodEnd($salary);
            if (Carbon::now()->gte($payPeriodEnd) && !$this->payrollExists($salary, $payPeriodEnd)) {
                $this->createPayroll($salary, $payPeriodEnd);
                $generatedCount++;
            }
        }

        return response()->json([
            'message' => "Payroll generation completed. Generated $generatedCount payrolls."
        ], 200);
    }

    /**
     * Calculate payroll period end date.
     */
    public function calculatePayPeriodEnd(Salary $salary)
    {
        $today = Carbon::now();
        $startDate = Carbon::parse($salary->start_date);

        return match ($salary->pay_period) {
            'monthly' => $today->endOfMonth(),
            'bi-weekly' => $startDate->copy()->addWeeks(floor($today->diffInDays($startDate) / 2)),
            'weekly' => $startDate->copy()->addWeeks(floor($today->diffInDays($startDate) / 7)),
            'daily' => $today,
            default => throw new \Exception("Invalid pay period"),
        };
    }

    /**
     * Check if payroll already exists.
     */
    private function payrollExists(Salary $salary, Carbon $payPeriodEnd): bool
    {
        return Payroll::where('employee_id', $salary->employee_id)
            ->where('salary_id', $salary->id)
            ->whereDate('pay_date', $payPeriodEnd)
            ->exists();
    }

    /**
     * Create payroll entry.
     */
    private function createPayroll(Salary $salary, Carbon $payPeriodEnd)
    {
        $payroll = Payroll::create([
            'employee_id' => $salary->employee_id,
            'salary_id' => $salary->id,
            'pay_date' => $payPeriodEnd,
            'status' => 'pending',
            'total_earnings' => 0,
            'total_deductions' => 0,
            'net_salary' => 0,
        ]);

        // Calculate totals
        $totalEarnings = PayrollItem::where('payroll_id', $payroll->id)->where('type', 'earning')->sum('amount');
        $totalDeductions = PayrollItem::where('payroll_id', $payroll->id)->where('type', 'deduction')->sum('amount');
        $netSalary = $totalEarnings - $totalDeductions;

        $payroll->update([
            'total_earnings' => $totalEarnings,
            'total_deductions' => $totalDeductions,
            'net_salary' => $netSalary,
        ]);
    }
}
