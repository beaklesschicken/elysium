<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Links an employee record to the portal account of the same person (an owner,
     * admin or manager who also needs their own credentials tracked).
     */
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->foreignId('user_id')->nullable()->after('organization_id')->constrained()->nullOnDelete();
            $table->unique(['organization_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropUnique(['organization_id', 'user_id']);
            $table->dropConstrainedForeignId('user_id');
        });
    }
};
