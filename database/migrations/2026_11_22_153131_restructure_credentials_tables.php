<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Splits the old `credentials` catalog into:
 *  - credential_types: what can be held (public catalog rows have no organization)
 *  - credentials: one held permit/certificate/training, with number, dates and documents
 * The old assignables pivot (no dates or numbers) is dropped.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('credential_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->nullable()->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('category');
            $table->string('holder_type');
            $table->string('issuing_authority')->nullable();
            $table->string('us_state', 2)->nullable();
            $table->unsignedSmallInteger('validity_months')->nullable();
            $table->string('info_link')->nullable();
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'holder_type']);
        });

        DB::table('credential_types')->insertUsing(
            ['id', 'name', 'category', 'holder_type', 'us_state', 'info_link', 'description', 'created_at', 'updated_at'],
            DB::table('credentials')->selectRaw("id, name, 'other', 'employee', us_state, info_link, description, created_at, updated_at")
        );

        Schema::drop('assignables');
        Schema::drop('credentials');

        Schema::create('credentials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('credential_type_id')->constrained()->restrictOnDelete();
            $table->morphs('holder');
            $table->string('number', 100)->nullable();
            $table->date('issued_on')->nullable();
            $table->date('expires_on')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('verified_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('verified_at')->nullable();
            $table->timestamp('superseded_at')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'superseded_at', 'expires_on']);
        });

        Schema::create('credential_documents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('credential_id')->constrained()->cascadeOnDelete();
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('path');
            $table->string('original_name');
            $table->string('mime_type', 100);
            $table->unsignedInteger('size');
            $table->timestamps();
        });

        Schema::create('credential_reminders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('credential_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('days_before');
            $table->timestamp('sent_at');

            $table->unique(['credential_id', 'days_before']);
        });
    }

    public function down(): void
    {
        Schema::drop('credential_reminders');
        Schema::drop('credential_documents');
        Schema::drop('credentials');

        Schema::create('credentials', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('type');
            $table->string('description');
            $table->string('us_state')->nullable();
            $table->string('info_link')->nullable();
            $table->boolean('public')->default(true);
            $table->unsignedBigInteger('submitted_by')->default('0');
            $table->timestamps();
        });
        DB::table('credentials')->insertUsing(
            ['id', 'name', 'type', 'description', 'us_state', 'info_link', 'created_at', 'updated_at'],
            DB::table('credential_types')->selectRaw("id, name, category, coalesce(description, ''), us_state, info_link, created_at, updated_at")
        );

        Schema::create('assignables', function (Blueprint $table) {
            $table->primary(['credential_id', 'assignables_id', 'assignables_type']);
            $table->unsignedBigInteger('credential_id');
            $table->unsignedBigInteger('assignables_id');
            $table->string('assignables_type');
            $table->timestamps();
        });

        Schema::drop('credential_types');
    }
};
