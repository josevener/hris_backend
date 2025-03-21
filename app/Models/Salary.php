<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Salary extends Model
{
    use HasFactory;
    use SoftDeletes;

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
