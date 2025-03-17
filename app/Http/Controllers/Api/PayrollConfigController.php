<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\PayrollConfig;
use App\Models\PayrollCycle;
use Carbon\Carbon;
use Illuminate\Http\Request;

class PayrollConfigController extends Controller
{
    /**
     * Display a listing of all payroll configurations with their cycles.
     */
    public function index()
    {
        return response()->json(PayrollConfig::with('cycles')->whereNull('deleted_at')->get(), 200);
    }

    /**
     * Store a newly created payroll configuration and generate cycles.
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'start_year_month' => 'required|string|regex:/^\d{4}-\d{2}$/',
            'first_start_day' => 'required|integer|min:1|max:31',
            'first_end_day' => 'required|integer|min:1|max:31',
            'second_start_day' => 'required|integer|min:1|max:31',
            'second_end_day' => 'required|integer|min:1|max:31',
            'pay_date_offset' => 'required|integer|min:0',
        ]);

        if (
            $validated['first_start_day'] > $validated['first_end_day'] ||
            $validated['second_start_day'] > $validated['second_end_day'] ||
            $validated['first_end_day'] >= $validated['second_start_day']
        ) {
            return response()->json(['error' => 'Invalid day range'], 422);
        }

        // Soft delete the existing payroll configuration and its cycles
        $existingConfig = PayrollConfig::latest()->first();
        if ($existingConfig) {
            $existingConfig->delete();
            $existingConfig->cycles()->delete();
        }

        // Create a new payroll configuration
        $config = PayrollConfig::create($validated);
        $cycles = $this->generateCycles($config);

        foreach ($cycles as $cycle) {
            PayrollCycle::create([
                'payroll_config_id' => $config->id,
                'start_date' => $cycle['start'],
                'end_date' => $cycle['end'],
                'pay_date' => $cycle['pay'],
            ]);
        }

        return response()->json($config->load('cycles'), 201);
    }

    /**
     * Display the specified payroll configuration with its cycles.
     */
    public function show($id)
    {
        $config = PayrollConfig::with('cycles')->findOrFail($id);
        return response()->json($config, 200);
    }

    /**
     * Update the specified payroll configuration and regenerate cycles.
     */
    public function update(Request $request, $id)
    {
        $config = PayrollConfig::findOrFail($id);

        $validated = $request->validate([
            'start_year_month' => 'sometimes|required|string|regex:/^\d{4}-\d{2}$/',
            'first_start_day' => 'sometimes|required|integer|min:1|max:31',
            'first_end_day' => 'sometimes|required|integer|min:1|max:31',
            'second_start_day' => 'sometimes|required|integer|min:1|max:31',
            'second_end_day' => 'sometimes|required|integer|min:1|max:31',
            'pay_date_offset' => 'sometimes|required|integer|min:0',
        ]);

        if (
            (isset($validated['first_start_day']) && isset($validated['first_end_day']) && $validated['first_start_day'] > $validated['first_end_day']) ||
            (isset($validated['second_start_day']) && isset($validated['second_end_day']) && $validated['second_start_day'] > $validated['second_end_day']) ||
            (isset($validated['first_end_day']) && isset($validated['second_start_day']) && $validated['first_end_day'] >= $validated['second_start_day'])
        ) {
            return response()->json(['error' => 'Invalid day range'], 422);
        }

        $config->update($validated);

        // Delete existing cycles and regenerate
        $config->cycles()->delete();
        $cycles = $this->generateCycles($config);

        foreach ($cycles as $cycle) {
            PayrollCycle::create([
                'payroll_config_id' => $config->id,
                'start_date' => $cycle['start'],
                'end_date' => $cycle['end'],
                'pay_date' => $cycle['pay'],
            ]);
        }

        return response()->json($config->load('cycles'), 200);
    }

    /**
     * Remove the specified payroll configuration and its cycles.
     */
    public function destroy($id)
    {
        $config = PayrollConfig::findOrFail($id);
        $config->cycles()->delete();
        $config->delete();

        return response()->json(null, 204);
    }

    /**
     * Generate payroll cycles for a configuration.
     */
    private function generateCycles(PayrollConfig $config)
    {
        $cycles = [];
        [$year, $month] = explode('-', $config->start_year_month);

        for ($i = 0; $i < 12; $i++) {
            $currentMonth = Carbon::create($year, $month + $i, 1);
            $nextMonth = $currentMonth->copy()->addMonth();

            // First cycle
            $firstStart = $currentMonth->copy()->day($config->first_start_day);
            $firstEnd = $currentMonth->copy()->day($config->first_end_day);
            $firstPay = $firstEnd->copy()->addDays($config->pay_date_offset);

            // Second cycle
            $secondStart = $currentMonth->copy()->day($config->second_start_day);
            $secondEnd = $currentMonth->copy()->day($config->second_end_day);
            $secondPay = $secondEnd->copy()->addDays($config->pay_date_offset);

            // Adjust second end to last day of month if needed
            $lastDay = $currentMonth->endOfMonth()->day;
            if ($config->second_end_day > $lastDay) {
                $secondEnd->day($lastDay);
                $secondPay = $secondEnd->copy()->addDays($config->pay_date_offset);
            }

            $cycles[] = [
                'start' => $firstStart->toDateString(),
                'end' => $firstEnd->toDateString(),
                'pay' => $firstPay->toDateString(),
            ];
            $cycles[] = [
                'start' => $secondStart->toDateString(),
                'end' => $secondEnd->toDateString(),
                'pay' => $secondPay->toDateString(),
            ];
        }

        return $cycles;
    }
}
