<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Payroll extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'salary_id',
        'total_earnings',
        'total_deductions',
        'net_salary',
        'payroll_cycles_id',
        'status'
    ];
    protected $casts = [
        'pay_date' => 'date',
        'start_date' => 'date',
        'end_date' => 'date',
    ];
    // Relationships
    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function salary()
    {
        return $this->belongsTo(Salary::class);
    }

    public function payroll_items()
    {
        return $this->hasMany(PayrollItem::class);
    }
}
