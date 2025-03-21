<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreEmployeeRequest;
use App\Http\Requests\UpdateEmployeeRequest;
use App\Models\Employee;
use App\Models\Salary;
use App\Models\User;

class EmployeeController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    public function usersDoesntHaveEmployee()
    {
        $users = User::whereDoesntHave('employee')->whereNull('deleted_at')->get();
        return response()->json($users, 200);
    }
    public function index()
    {
        $employees = Employee::with(['user', 'salary', 'department', 'designation'])->get();

        return response()->json($employees);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreEmployeeRequest $request)
    {
        Employee::firstOrCreate($request->validated());

        $currentTimeStamp = now();
        return response()->json(['message' => "New employee record has been added successfully at $currentTimeStamp"], 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Employee $employee)
    {
        return response()->json(['employee', $employee]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateEmployeeRequest $request, Employee $employee)
    {
        $employee->update($request->validated());

        $currentTimestamp = now();
        return response()->json(['message' => 'Employee updated successfully at ' . $currentTimestamp]);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $employee = Employee::findOrFail($id);
        $employee->delete();

        $salary = Salary::findOrFail($employee->id);
        $salary->delete();

        $currentTimeStamp = now();
        return response()->json(['message' => "Salary deleted successfully $currentTimeStamp"], 200);
    }
}
