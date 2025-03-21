<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StorePayrollConfigRequest;
use App\Http\Requests\UpdatePayrollConfigRequest;
use App\Models\PayrollConfig;
use App\Models\PayrollCycle;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PayrollConfigController extends Controller
{
    /**
     * Display a listing of all payroll configurations with their cycles.
     */
    public function index()
    {
        $configs = PayrollConfig::whereNull('deleted_at')->with('cycles')->get();
        return response()->json($configs, 200);
    }

    /**
     * Store a newly created payroll configuration and generate cycles.
     */
    public function store(StorePayrollConfigRequest $request)
    {
        try {
            return DB::transaction(function () use ($request) {
                $validated = $request->validated();

                // Soft delete the latest active payroll config and its cycles
                $existingConfig = PayrollConfig::whereNull('deleted_at')->latest()->first();
                if ($existingConfig) {
                    $existingConfig->cycles()->delete();
                    $existingConfig->delete();
                }

                // Create new payroll config
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

                return response()->json([
                    'message' => 'Payroll configuration created successfully.',
                    'data' => $config->load('cycles'),
                ], 201);
            });
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to create payroll config: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Display the specified payroll configuration with its cycles.
     */
    public function show($id)
    {
        $config = PayrollConfig::with('cycles')->findOrFail($id);
        return response()->json([
            'message' => 'Payroll configuration retrieved successfully.',
            'data' => $config,
        ], 200);
    }

    /**
     * Update the specified payroll configuration and regenerate cycles.
     */
    public function update(UpdatePayrollConfigRequest $request, $id)
    {
        try {
            $config = PayrollConfig::findOrFail($id);

            return DB::transaction(function () use ($request, $config) {
                $validated = $request->validated();

                // Update the config
                $config->update($validated);

                // Delete and regenerate cycles
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
            });
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to update payroll config: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Remove the specified payroll configuration and its cycles.
     */
    public function destroy($id)
    {
        try {
            return DB::transaction(function () use ($id) {
                $config = PayrollConfig::findOrFail($id);
                $config->cycles()->delete();
                $config->delete();

                return response()->json([
                    'message' => 'Payroll configuration deleted successfully at ' . now(),
                ], 200);
            });
        } catch (\Exception $e) {
            return response()->json([
                'error' => 'Failed to delete payroll config: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Generate payroll cycles for a configuration.
     */
    private function generateCycles(PayrollConfig $config)
    {
        $cycles = [];
        [$year, $month] = explode('-', $config->start_year_month);

        for ($i = 0; $i < 12; $i++) {
            $currentMonth = Carbon::create($year, $month, 1)->addMonths($i);
            $lastDay = $currentMonth->endOfMonth()->day;

            // First cycle
            $firstStartDay = min($config->first_start_day, $lastDay);
            $firstEndDay = min($config->first_end_day, $lastDay);
            $firstStart = $currentMonth->copy()->day($firstStartDay);
            $firstEnd = $currentMonth->copy()->day($firstEndDay);
            $firstPay = $firstEnd->copy()->addDays($config->pay_date_offset);

            // Second cycle
            $secondStartDay = min($config->second_start_day, $lastDay);
            $secondEndDay = min($config->second_end_day, $lastDay);
            $secondStart = $currentMonth->copy()->day($secondStartDay);
            $secondEnd = $currentMonth->copy()->day($secondEndDay);
            $secondPay = $secondEnd->copy()->addDays($config->pay_date_offset);

            // Ensure dates are valid
            if ($firstStart->gt($firstEnd)) {
                $firstEnd = $firstStart->copy();
            }
            if ($secondStart->gt($secondEnd)) {
                $secondEnd = $secondStart->copy();
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
