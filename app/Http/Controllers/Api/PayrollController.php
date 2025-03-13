<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Payroll;
use App\Http\Requests\StorePayrollRequest;
use App\Http\Requests\UpdatePayrollRequest;
use App\Models\PayrollItem;
use App\Models\Salary;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PayrollController extends Controller
{
    /**
     * Display a listing of the resource.
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

    public function items(Payroll $payroll)
    {
        $items = PayrollItem::where('payroll_id', $payroll->id)->get();
        return response()->json($items, 200);
    }

    public function index()
    {
        $payrolls = Payroll::with('employee.user', 'salary')->paginate(6);
        return response()->json($payrolls, 200);
    }

    /**
     * Show the form for creating a new resource.
     */

    public function create()
    {
        $employees = Employee::all();
        $salaries = Salary::all();
        return view('payrolls.create', compact('employees', 'salaries'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'employee_id' => 'nullable|exists:employees,id',
            'salary_id' => 'nullable|exists:salaries,id',
            'pay_date' => 'required|date',
            'start_date' => 'required|date|before_or_equal:pay_date',
            'end_date' => 'required|date|after_or_equal:start_date|before_or_equal:pay_date',
            'status' => 'required|in:pending,processed,paid',
        ]);

        $payroll = Payroll::create($request->all());
        $salary = Salary::findOrFail($request->salary_id);

        PayrollItem::create([
            'payroll_id' => $payroll->id,
            'employee_id' => $payroll->employee_id,
            'scope' => 'specific',
            'type' => 'earning',
            'category' => 'Basic Salary',
            'amount' => $salary->basic_salary,
        ]);

        $payroll->refresh();
        return response()->json(['payroll' => $payroll->load('employee', 'salary')], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Payroll $payroll)
    {
        $payroll->load('employee.user', 'salary');
        return response()->json($payroll, 200);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Payroll $payroll)
    {
        $employees = Employee::all();
        $salaries = Salary::all();
        return response()->json($payroll, 200);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $payroll = Payroll::findOrFail($id);
        $request->validate([
            'employee_id' => 'required|exists:employees,id',
            'salary_id' => 'required|exists:salaries,id',
            'pay_date' => 'required|date',
            'start_date' => 'required|date|before_or_equal:pay_date',
            'end_date' => 'required|date|after_or_equal:start_date|before_or_equal:pay_date',
            'status' => 'required|in:pending,processed,paid',
        ]);

        $payroll->update($request->all());

        return response()->json(['payroll' => $payroll->load('employee.user', 'salary')], 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Payroll $payroll)
    {
        $payroll->delete();
        return response()->json(['message' => 'Payroll deleted successfully'], 200);
    }

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
            'message' => `Payroll generation completed. Generated $generatedCount payrolls.`
        ], 200);
    }

    public function calculatePayPeriodEnd(Salary $salary)
    {
        $startDate = Carbon::parse($salary->start_date);
        $today = Carbon::now();

        switch ($salary->pay_period) {
            case 'monthly':
                $periodEnd = $startDate->copy()->addMonths(
                    (int) floor($today->diffInMonths($startDate))
                )->endOfMonth();
                return $periodEnd;
            case 'bi-weekly':
                $daysSinceStart = $startDate->diffInDays($today);
                $periods = (int) floor($daysSinceStart / 14);
                return $startDate->copy()->addDays($periods * 14);
            case 'weekly':
                $daysSinceStart = $startDate->diffInDays($today);
                $periods = (int) floor($daysSinceStart / 7);
                return $startDate->copy()->addDays($periods * 7);
            case 'daily':
                return $today->copy();
            default:
                throw new \Exception("Invalid pay period");
        }
    }

    private function payrollExists(Salary $salary, Carbon $payPeriodEnd): bool
    {
        return Payroll::where('employee_id', $salary->employee_id)
            ->where('salary_id', $salary->id)
            ->whereDate('pay_date', $payPeriodEnd)
            ->exists();
    }

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
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        // Add basic salary as an earning
        PayrollItem::create([
            'payroll_id' => $payroll->id,
            'employee_id' => $salary->employee_id,
            'type' => 'earning',
            'category' => 'Basic Salary',
            'amount' => $salary->basic_salary,
        ]);

        // Example deduction: 10% tax (customize as needed)
        PayrollItem::create([
            'payroll_id' => $payroll->id,
            'employee_id' => $salary->employee_id,
            'type' => 'deduction',
            'category' => 'Tax',
            'amount' => $salary->basic_salary * 0.1,
        ]);

        // Placeholder for attendance-based items (future)
        // Example: if ($attendance) { addOvertimeItem($payroll, $attendance); }

        // Calculate totals
        $totalEarnings = $payroll->items()->where('type', 'earning')->sum('amount');
        $totalDeductions = $payroll->items()->where('type', 'deduction')->sum('amount');
        $netSalary = $totalEarnings - $totalDeductions;

        $payroll->update([
            'total_earnings' => $totalEarnings,
            'total_deductions' => $totalDeductions,
            'net_salary' => $netSalary,
        ]);
    }
}
