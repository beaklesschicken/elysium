<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Rebuilds the tenant tables with foreign keys and the "units" naming:
 * teams -> units, team_user -> unit_user, employee_team -> employee_unit.
 * Existing rows are copied across; rows pointing at missing parents are dropped.
 * users.organization_id is replaced by users.current_organization_id.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('organizations', function (Blueprint $table) {
            $table->string('address')->nullable()->change();
            $table->string('city')->nullable()->change();
            $table->string('state_province', 2)->nullable()->change();
            $table->string('postcode', 10)->nullable()->change();
        });

        Schema::rename('organization_user', 'organization_user_old');
        Schema::create('organization_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('role')->default('manager');
            $table->timestamps();

            $table->unique(['organization_id', 'user_id']);
        });
        $this->copyDistinct('organization_user_old', 'organization_user', [
            'organization_id' => 'organizations',
            'user_id' => 'users',
        ], ['role', 'created_at', 'updated_at']);
        Schema::drop('organization_user_old');

        Schema::create('units', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('description')->nullable();
            $table->string('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state_province', 2)->nullable();
            $table->string('postcode', 10)->nullable();
            $table->string('phone', 30)->nullable();
            $table->timestamps();
        });
        DB::table('units')->insertUsing(
            ['id', 'organization_id', 'name', 'description', 'created_at', 'updated_at'],
            DB::table('teams')
                ->select(['id', 'organization_id', 'name', 'description', 'created_at', 'updated_at'])
                ->whereIn('organization_id', DB::table('organizations')->select('id'))
        );

        Schema::create('unit_user', function (Blueprint $table) {
            $table->id();
            $table->foreignId('unit_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['unit_id', 'user_id']);
        });
        $this->copyDistinct('team_user', 'unit_user', [
            'team_id' => 'units',
            'user_id' => 'users',
        ], [], ['team_id' => 'unit_id']);

        Schema::rename('employees', 'employees_old');
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('organization_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->string('email')->nullable();
            $table->string('phone', 30)->nullable();
            $table->date('start_date')->nullable();
            $table->date('birth_date')->nullable();
            $table->string('status')->default('active');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index(['organization_id', 'status']);
        });
        DB::table('employees')->insertUsing(
            ['id', 'organization_id', 'name', 'start_date', 'birth_date', 'notes', 'created_at', 'updated_at'],
            DB::table('employees_old')
                ->select(['id', 'organization_id', 'name', 'start_date', 'birth_date', 'notes', 'created_at', 'updated_at'])
                ->whereIn('organization_id', DB::table('organizations')->select('id'))
        );
        Schema::drop('employees_old');

        Schema::create('employee_unit', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('unit_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['employee_id', 'unit_id']);
        });
        $this->copyDistinct('employee_team', 'employee_unit', [
            'employee_id' => 'employees',
            'team_id' => 'units',
        ], [], ['team_id' => 'unit_id']);

        Schema::drop('team_user');
        Schema::drop('employee_team');
        Schema::drop('teams');

        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('organization_id');
        });
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('current_organization_id')->nullable()->after('password')
                ->constrained('organizations')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('current_organization_id');
        });
        Schema::table('users', function (Blueprint $table) {
            $table->unsignedBigInteger('organization_id')->default(0);
        });

        Schema::create('teams', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->unsignedBigInteger('organization_id');
            $table->string('description');
            $table->timestamps();
        });
        DB::table('teams')->insertUsing(
            ['id', 'organization_id', 'name', 'description', 'created_at', 'updated_at'],
            DB::table('units')->selectRaw("id, organization_id, name, coalesce(description, ''), created_at, updated_at")
        );

        Schema::create('team_user', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('team_id');
            $table->unsignedBigInteger('user_id');
            $table->timestamps();
        });
        DB::table('team_user')->insertUsing(
            ['team_id', 'user_id', 'created_at', 'updated_at'],
            DB::table('unit_user')->select(['unit_id', 'user_id', 'created_at', 'updated_at'])
        );

        Schema::create('employee_team', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('employee_id');
            $table->unsignedBigInteger('team_id');
            $table->timestamps();
        });
        DB::table('employee_team')->insertUsing(
            ['employee_id', 'team_id', 'created_at', 'updated_at'],
            DB::table('employee_unit')->select(['employee_id', 'unit_id', 'created_at', 'updated_at'])
        );

        Schema::drop('employee_unit');
        Schema::drop('unit_user');
        Schema::drop('units');

        Schema::table('employees', function (Blueprint $table) {
            $table->dropForeign(['organization_id']);
            $table->dropIndex(['organization_id', 'status']);
            $table->dropColumn(['email', 'phone', 'status']);
        });

        Schema::table('organization_user', function (Blueprint $table) {
            $table->dropForeign(['organization_id']);
            $table->dropForeign(['user_id']);
            $table->dropUnique(['organization_id', 'user_id']);
            $table->string('role')->default('admin')->change();
        });
    }

    /**
     * Copies unique rows from one pivot table to another, skipping rows whose parents no longer exist.
     *
     * @param  array<string, string>  $parents  source column => parent table it must exist in
     * @param  list<string>  $extraColumns  columns copied as-is
     * @param  array<string, string>  $renames  source column => destination column
     */
    private function copyDistinct(string $from, string $to, array $parents, array $extraColumns = [], array $renames = []): void
    {
        $keyColumns = array_keys($parents);

        DB::table($from)
            ->orderBy('id')
            ->get()
            ->filter(function (object $row) use ($parents): bool {
                foreach ($parents as $column => $parentTable) {
                    if (! DB::table($parentTable)->where('id', $row->{$column})->exists()) {
                        return false;
                    }
                }

                return true;
            })
            ->unique(fn (object $row): string => implode('-', array_map(fn (string $column) => $row->{$column}, $keyColumns)))
            ->each(function (object $row) use ($to, $keyColumns, $extraColumns, $renames): void {
                $values = [];
                foreach (array_merge($keyColumns, $extraColumns) as $column) {
                    $values[$renames[$column] ?? $column] = $row->{$column};
                }
                $values['created_at'] ??= $row->created_at ?? now();
                $values['updated_at'] ??= $row->updated_at ?? now();

                DB::table($to)->insert($values);
            });
    }
};
