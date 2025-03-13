<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Salary extends Model
{
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'basic_salary',
        'pay_period',
        'start_date',
        'end_date',
        'salary_id',
        'isActive',
    ];

    public function employee()
    {
        return $this->belongsTo(Employee::class);
    }

    public function scopeIsActive($query)
    {
        return $query->where('isActive', '>', 0);
    }
}
