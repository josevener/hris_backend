<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Salary;
use App\Http\Requests\StoreSalaryRequest;
use App\Http\Requests\UpdateSalaryRequest;
use App\Models\Employee;
use Exception;
use Illuminate\Http\Request;


class SalaryController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    public function employeesDoesntHaveSalary()
    {
        $employees = Employee::whereDoesntHave('salary')->isActive()->get();

        return response()->json($employees);
    }

    public function index()
    {
        $salaries = Salary::with('employee')->isActive()->get();

        return response()->json($salaries);
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

    public function store(StoreSalaryRequest $request)
    {
        try {

            $salary = Salary::create($request->validated());

            // Update the Employee record with the new salary_id
            $employee = Employee::find($request->employee_id);
            if ($employee) {
                $employee->salary_id = $salary->id; // Use the newly created salary ID
                $employee->save();
            }

            return response()->json(['message' => 'Salary created successfully'], 201);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error creating salary: ' . $e->getMessage()], 500);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Salary $salary)
    {
        return response()->json($salary);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Salary $salary)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Salary $salary)
    {
        try {
            $salary->update($request->all());
            return response()->json(['message' => 'Salary updated successfully'], 200);
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
            $salary->isActive = 0; // Set the isActive value to 0 for soft delete
            $salary->save();

            return response()->json(['message' => 'Salary deleted successfully'], 200);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error deleting salary'], 500);
        }
    }
}
