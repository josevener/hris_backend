<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PayrollCycle;
use Illuminate\Http\Request;

class PayrollCyclesController extends Controller
{
    public function index()
    {
        return response()->json(['cycles' => PayrollCycle::all()]);
    }
}
