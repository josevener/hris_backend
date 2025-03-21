<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Salary;
use App\Http\Requests\StoreSalaryRequest;
use App\Http\Requests\UpdateSalaryRequest;
use App\Models\Employee;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Date;

class SalaryController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    public function employeesDoesntHaveSalary()
    {
        $employees = Employee::whereDoesntHave('salary')->whereNull('deleted_at')->get();

        return response()->json($employees);
    }

    public function index()
    {
        $salaries = Salary::with('employee.user')->whereNull('deleted_at')->get();

        return response()->json($salaries);
    }

    /**
     * Store a newly created resource in storage.
     */

    public function store(StoreSalaryRequest $request)
    {
        try {
            // Check if an active salary record already exists for the employee
            $existingSalary = Salary::where('employee_id', $request->employee_id)
                ->whereNull('deleted_at') // Only count non-soft-deleted records
                ->first();

            if ($existingSalary) {
                return response()->json(['message' => 'A salary record already exists for this employee.'], 422);
            }

            // Create the new salary record
            $salary = Salary::create($request->validated());

            // Update the Employee record with the new salary_id
            $employee = Employee::find($request->employee_id);
            if ($employee) {
                $employee->salary_id = $salary->id; // Use the newly created salary ID
                $employee->save();
            }

            $currentTimeStamp = now();
            return response()->json(['message' => 'Salary created successfully ' . $currentTimeStamp], 201);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error creating salary: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Salary $salary)
    {
        $activeSalary = $salary->with('employee.user')->whereNull('deleted_at')->get();
        return response()->json($activeSalary);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSalaryRequest $request, Salary $salary)
    {
        try {
            // Update the existing salary's end_date and soft-delete it
            $salary->update(['end_date' => $request->end_date]);
            $salary->delete(); // Soft delete the old salary record

            // Create a new salary record
            $newSalary = Salary::create([
                'employee_id' => $request->employee_id,
                'basic_salary' => $request->basic_salary,
                'pay_period' => $request->pay_period,
                'start_date' => $request->end_date, // New salary starts where old one ends
                'end_date' => null, // Optionally set this if needed
            ]);

            // Update the employee's salary_id with the new salary
            $employee = Employee::findOrFail($request->employee_id);
            $employee->salary_id = $newSalary->id; // Use the new salary ID
            $employee->save();

            $currentTimestamp = now();
            return response()->json(['message' => "Salary updated successfully at $currentTimestamp"], 200);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error updating salary: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        try {

            $salary = Salary::findOrFail($id);
            $salary->delete();

            $currentTimeStamp = now();

            return response()->json(['message' => "Salary deleted successfully at $currentTimeStamp"], 200);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error deleting salary: ' . $e->getMessage()], 500);
        }
    }
}
