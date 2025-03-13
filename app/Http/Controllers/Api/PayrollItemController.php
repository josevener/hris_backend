<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PayrollItem;
use App\Http\Requests\StorePayrollItemRequest;
use App\Http\Requests\UpdatePayrollItemRequest;
use Illuminate\Http\Request;

class PayrollItemController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        return response()->json(PayrollItem::with('payroll', 'employee')->get(), 200);
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
            'payroll_id' => 'nullable|exists:payrolls,id',
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
}
