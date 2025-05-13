<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('assignables', function (Blueprint $table) {
            $table->primary(['credential_id','assignables_id','assignables_type']);
            $table->unsignedBigInteger('credential_id');
            $table->unsignedBigInteger('assignables_id'); // The id of the user, organization, or employee
            $table->string('assignables_type'); // user, organization, employee
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('assignables');
    }
};
