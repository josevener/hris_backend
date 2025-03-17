<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class PayrollCycle extends Model
{
    use SoftDeletes; // Enable soft deletes
    protected $fillable = ['payroll_config_id', 'start_date', 'end_date', 'pay_date'];

    public function payrollConfig()
    {
        return $this->belongsTo(PayrollConfig::class);
    }
}
