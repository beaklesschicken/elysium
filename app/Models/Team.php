<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;


class Team extends Model
{
    use HasFactory;
    protected $fillable = ['name','description','organization_id'];

    public function managers()
    {
        return $this->belongsToMany(User::class,'team_user','user_id');
    }
    public function organization()
    {
        return $this->belongsTo(Organization::class);
    }
    public function employees()
    {
        return $this->belongsToMany(Employee::class);
    }
}
