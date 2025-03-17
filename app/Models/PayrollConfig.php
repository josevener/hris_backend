<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class PayrollConfig extends Model
{
    use SoftDeletes; // Enable soft deletes

    protected $fillable = [
        'start_year_month',
        'first_start_day',
        'first_end_day',
        'second_start_day',
        'second_end_day',
        'pay_date_offset',
    ];

    public function cycles(): HasMany
    {
        return $this->hasMany(PayrollCycle::class);
    }
}
