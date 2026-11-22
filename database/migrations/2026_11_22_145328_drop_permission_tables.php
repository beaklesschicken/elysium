<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

/**
 * Removes the tables left behind by spatie/laravel-permission, which was never installed.
 * Roles now live on the organization_user pivot and are enforced with policies.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::dropIfExists('role_has_permissions');
        Schema::dropIfExists('model_has_roles');
        Schema::dropIfExists('model_has_permissions');
        Schema::dropIfExists('roles');
        Schema::dropIfExists('permissions');
    }

    public function down(): void
    {
        //
    }
};
