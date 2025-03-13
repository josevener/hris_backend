<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\User;
use Exception;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $users = User::isActive()->paginate(10);

        return response()->json($users, 200);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request)
    {
        try {
            $request->validate([
                'lastname' => 'required|string|max:255',
                'firstname' => 'required|string|max:255',
                'middlename' => 'nullable|string|max:255',
                'extension' => 'nullable|string|max:255',
                'role_name' => 'required|string',
                'profile_image' => 'nullable|string',
                'phone_number' => 'nullable|string|max:255',
                'email' => 'required|email|unique:users',
                'password' => 'required|min:8',
            ]);

            // Generate a new employee number
            $companyIdNumber = $this->generateNewEmployeeNo();

            $user = User::create([
                'lastname' => $request->lastname,
                'firstname' => $request->firstname,
                'middlename' => $request->middlename,
                'extension' => $request->extension,
                'company_id_number' => $companyIdNumber, // Use generated employee number
                'role_name' => $request->role_name,
                'profile_image' => $request->profile_image,
                'phone_number' => $request->phone_number,
                'email' => $request->email,
                'password' => bcrypt($request->password),
            ]);

            return response()->json($user, 201);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error creating user', 'error' => $e->getMessage()]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        try {
            return response()->json(['user', $user]);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error fetching user', 'error' => $e->getMessage()]);
        }
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        try {
            return response()->json(['user' => $user]);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error editing user', 'error' => $e->getMessage()]);
        }
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, User $user)
    {
        try {
            $request->validate([
                'lastname' => 'required|string|max:255',
                'firstname' => 'required|string|max:255',
                'middlename' => 'nullable|string|max:255',
                'extension' => 'nullable|string|max:255',
                'role_name' => 'required|string',
                'profile_image' => 'nullable|string',
                'phone_number' => 'nullable|string|max:255',
                'email' => 'required|email|unique:users,email,' . $user->id,
                'password' => 'nullable|min:8',
            ]);

            $user->update($request->all());
            return response()->json($user, 201);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error updating user', 'error' => $e->getMessage()], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy($id)
    {
        $user = User::findOrFail($id);
        $user->isActive = 0;
        $user->save();

        // Find the employee by user_id instead of primary key
        $employee = Employee::where('user_id', $id)->first();

        if ($employee) {
            $employee->isActive = 0;
            $employee->save();
        }

        return response()->json(['message', 'User has been successfully deleted!']);
    }

    public function userProfile()
    {
        $userProfile = User::findOrFail(1);
        return response()->json($userProfile);
    }

    public function getCurrentUser()
    {
        $currentUser = User::findOrFail(1);
        $currentUser->createToken('authToken')->plainTextToken;
        return response()->json($currentUser);
    }

    public function checkEmail(Request $request)
    {
        try {
            $request->validate([
                'email' => 'required|email',
            ]);

            $exists = User::where('email', $request->email)->exists();
            return response()->json(['exists' => $exists], 200);
        } catch (Exception $e) {
            return response()->json(['message' => 'Error checking email', 'error' => $e->getMessage()], 500);
        }
    }

    private function generateNewEmployeeNo()
    {
        // Get the latest employee ID from 'employee_id' column
        $getLatestUser = User::whereNotNull('company_id_number')->orderBy('company_id_number', 'desc')->first();

        // Extract the numeric part of the employee ID and increment
        if ($getLatestUser && preg_match('/BFD-(\d+)/', $getLatestUser->employee_id, $matches)) {
            $getNextEmployeeId = intval($matches[1]) + 1;
        } else {
            $getNextEmployeeId = 1;
        }

        // Generate new Employee ID
        $generatedEmployeeId = 'BFD-' . sprintf('%04d', $getNextEmployeeId);

        // Ensure uniqueness
        while (User::where('company_id_number', $generatedEmployeeId)->exists()) {
            $getNextEmployeeId++;
            $generatedEmployeeId = 'BFD-' . sprintf('%04d', $getNextEmployeeId);
        }

        return $generatedEmployeeId;
    }
}
