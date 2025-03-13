<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Employee extends Model
{
    use HasFactory;

    protected $fillable = [
        'company_id_number',
        'birthdate',
        'reports_to',
        'gender',
        'user_id',
        'department_id',
        'designation_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function department()
    {
        return $this->belongsTo(Department::class);
    }
    public function designation()
    {
        return $this->belongsTo(Designation::class);
    }

    public function salary()
    {
        return $this->hasMany(Salary::class);
    }

    public function scopeIsActive($query)
    {
        return $query->where('isActive', '>', 0);
    }
}
