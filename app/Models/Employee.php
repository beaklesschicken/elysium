<?php

namespace App\Models;


use Illuminate\Support\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Employee extends Model
{
    /** @use HasFactory<\Database\Factories\EmployeeFactory> */
    use HasFactory;
    public function organization()
    {
        $this->belongsToOne(Organization::class);
    }
    public function teams()
    {
        $this->belongsToMany(Team::class);
    }  
    public function assignCredential($employee_id)
    {
        $employee_id = $employee_id ?: auth()->user;
        return $this->assigned()->attach($employee_id);
    }  
    public function assigned()
    {
        return $this->morphToMany(Credential::class,'assignables')->withTimestamps();
    }
}
