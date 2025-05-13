<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Credential extends Model
{
    use HasFactory;
    public function assignCredential($user_id = null)
    {
        $user_id = $user_id ?: auth()->user;
        return $this->assigned()->attach($user_id);
    }

    public function assigned()
    {
        return $this->morphToMany(Credential::class,'assignables')->withTimestamps();
    }
}