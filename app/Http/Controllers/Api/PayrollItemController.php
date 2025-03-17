<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Payroll;
use App\Models\PayrollConfig;
use App\Models\PayrollCycle;
use App\Models\PayrollItem;
use App\Http\Requests\StorePayrollItemRequest;
use App\Http\Requests\UpdatePayrollItemRequest;
use Illuminate\Http\Request;

class PayrollItemController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        // Check if filtering by active payroll config
        if ($request->query('active_config')) {
            $activeConfig = PayrollConfig::latest()->first(); // Get latest active config
            if (!$activeConfig) {
                return response()->json([], 200);
            }

            // Fetch payroll items linked to the active config's cycles
            $items = PayrollItem::whereIn(
                'payroll_id',
                Payroll::whereIn('id', PayrollCycle::where('payroll_config_id', $activeConfig->id)->pluck('id'))
                    ->pluck('id')
            )->get();

            return response()->json($items, 200);
        }

        return response()->json(PayrollItem::all(), 200);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        $request->validate([
            'employee_id' => 'nullable|exists:employees,id|required_if:scope,specific',
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'scope' => 'required|in:specific,global',
            'type' => 'required|in:earning,deduction,contribution',
            'category' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
        ]);

        $payrollItem = PayrollItem::create($request->all());
        return response()->json($payrollItem->load('employee.user', 'payroll'), 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(PayrollItem $payrollItem)
    {
        return response()->json($payrollItem->load('payroll', 'employee'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(PayrollItem $payrollItem)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        $payrollItem = PayrollItem::findOrFail($id);
        $request->validate([
            'employee_id' => 'nullable|exists:employees,id|required_if:scope,specific',
            'payroll_id' => 'nullable|exists:payrolls,id',
            'scope' => 'required|in:specific,global',
            'type' => 'required|in:earning,deduction,contribution',
            'category' => 'required|string|max:255',
            'amount' => 'required|numeric|min:0',
        ]);

        $payrollItem->update($request->all());
        return response()->json($payrollItem->load('employee.user', 'payroll'), 200);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $payrollItem = PayrollItem::findOrFail($id);
        $payrollItem->delete();
        return response()->json(null, 204);
    }

    public function processPayroll($cycleId)
    {
        $cycle = PayrollCycle::findOrFail($cycleId);
        $activeConfig = PayrollConfig::latest()->first();

        if (!$activeConfig || $cycle->payroll_config_id !== $activeConfig->id) {
            return response()->json(['error' => 'Invalid or outdated payroll cycle'], 422);
        }

        $payrolls = Payroll::whereBetween('pay_date', [$cycle->start_date, $cycle->end_date])->get();

        foreach ($payrolls as $payroll) {
            // Assign active payroll items for this payroll
            $payrollItems = PayrollItem::whereNull('payroll_id')
                ->orWhere('payroll_id', $payroll->id)
                ->get();

            foreach ($payrollItems as $item) {
                $item->update(['payroll_id' => $payroll->id]); // Link item to payroll
            }
        }

        return response()->json(['message' => 'Payroll processed with items'], 200);
    }
}
