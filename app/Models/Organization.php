<?php

namespace App\Models;

use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Organization extends Model
{
    /** @use HasFactory<\Database\Factories\OrganizationFactory> */
    use HasFactory;

    public function users()
    {
        return $this->belongsToMany(User::class)->withPivot('role');
    }
    public function teams()
    {
        return $this->hasMany(Team::class);
    }

    public function getTeamsIdAttribute()
    {
        return $this->teams->pluck('id');
    }

        public function assignCredential($organization_id)
    {
        $organization_id = $organization_id ?: auth()->user;
        return $this->assigned()->attach($organization_id);
    }  

    public function assigned()
    {
        return $this->morphToMany(Credential::class,'assignables')->withTimestamps();
    }
}
