<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Salary;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class EmployeeController extends Controller
{
    /**
     * Display a listing of the resource.
     */

    public function usersDoesntHaveEmployee()
    {
        $users = User::isActive()->whereDoesntHave('employee', function ($query) {
            $query->where('isActive', 1);
        })->get();
        return response()->json($users, 200);
    }
    public function index()
    {
        $employees = Employee::isActive()->with(['user', 'salary', 'department', 'designation'])->get();
        // $employees = Employee::isActive()->get();

        if ($employees->count() > 0) {
            return response()->json($employees, 201);
        } else {
            return response()->json($employees, status: 201);
        }
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
            'company_id_number' => 'required|string',
            'birthdate' => 'nullable|date',
            'reports_to' => 'nullable|string',
            'gender' => 'nullable|string',
            'user_id' => 'required|numeric',
            'department_id' => 'required|numeric',
            'designation_id' => 'required|numeric',
        ]);

        // Find the existing employee with the same company_id_number
        $existingEmployee = Employee::where('company_id_number', $request->company_id_number)->first();

        if ($existingEmployee) {
            // Update the existing employee's company_id_number to "old-{timestamp}-{company_id_number}"
            $timestamp = now()->format('Y-m-d H:i:s');
            $existingEmployee->update(['company_id_number' => "old-{$request->company_id_number}-{$timestamp}"]);
        }

        /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////
        // This is only for the purpose of creating a new employee record with the original company_id_number and not updating the  /////
        // existing (softly deleted) employee record and update the company_id_number to "old-{timestamp}-{company_id_number}"      /////
        // To keep the record of the softly deleted employee record                                                                 /////
        /////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////////

        // Create a new employee record with the original company_id_number
        $employee = Employee::create($request->all());

        return response()->json($employee, 201);
    }

    /**
     * Display the specified resource.
     */
    public function show(Employee $employee)
    {
        return response()->json(['employee', $employee]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Employee $employee)
    {
        //
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Employee $employee)
    {
        $request->validate([
            'company_id_number' => 'required|string',
            'birthdate' => 'nullable|date',
            'reports_to' => 'nullable|string',
            'gender' => 'nullable|string',
            'user_id' => 'required|numeric',
            'department_id' => 'required|numeric',
            'designation_id' => 'required|numeric',
        ]);

        $employee->update($request->all());
        return response()->json(['message' => 'Employee updated successfully']);
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $employee = Employee::findOrFail($id);
        $employee->isActive = 0;
        $employee->save();

        $salary = Salary::findOrFail($employee->id);
        $salary->isActive = 0;
        $salary->save();


        return response()->json(['message', 'Employee has been successfully deleted!']);
    }
}
