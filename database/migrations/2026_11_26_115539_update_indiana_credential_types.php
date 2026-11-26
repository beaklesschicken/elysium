<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Query\Builder;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Indiana-specific validity periods and catalog entries, researched 2026-11-26 from ATC,
 * IDOH and DOR sources. Adds `expires_at_age` for permits that end on a birthday, such as
 * Indiana's restricted employee permit for 18–20 year olds.
 */
return new class extends Migration
{
    /**
     * @return list<array{name: string, us_state: ?string, before: array<string, mixed>, after: array<string, mixed>}>
     */
    private function updates(): array
    {
        return [
            [
                'name' => 'Alcohol server permit',
                'us_state' => 'IN',
                'before' => ['description' => 'Employee permit for serving or selling alcohol.'],
                'after' => ['description' => 'ATC employee permit for serving or selling alcohol, for employees 21 and older. Holders must complete certified server training within 120 days of hire.'],
            ],
            [
                'name' => 'Retail alcohol permit',
                'us_state' => 'IN',
                'before' => ['validity_months' => null],
                'after' => ['validity_months' => 12],
            ],
            [
                'name' => 'Retail merchant certificate',
                'us_state' => 'IN',
                'before' => ['validity_months' => null, 'info_link' => null, 'description' => 'Certificate to collect sales tax.'],
                'after' => ['validity_months' => 24, 'info_link' => 'https://www.in.gov/dor/business-tax/sales-tax', 'description' => 'Registered retail merchant certificate to collect sales tax. Renews automatically when returns are filed and paid.'],
            ],
            [
                'name' => 'Food handler certificate',
                'us_state' => null,
                'before' => ['description' => 'Basic food safety training for staff who handle food.'],
                'after' => ['description' => 'Basic food safety training for staff who handle food. In Indiana this does not meet the certified food protection manager requirement.'],
            ],
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function additions(): array
    {
        return [
            ['name' => 'Restricted employee permit', 'category' => 'alcohol_permit', 'holder_type' => 'employee', 'issuing_authority' => 'Indiana Alcohol and Tobacco Commission', 'us_state' => 'IN', 'validity_months' => null, 'expires_at_age' => 21, 'info_link' => 'https://www.in.gov/atc/', 'description' => 'ATC employee permit for staff aged 18 to 20. Valid until the employee turns 21, when they need the regular employee permit.'],
            ['name' => 'Alcohol server certification', 'category' => 'training', 'holder_type' => 'employee', 'issuing_authority' => 'Indiana Alcohol and Tobacco Commission', 'us_state' => 'IN', 'validity_months' => 36, 'expires_at_age' => null, 'info_link' => 'https://secure.in.gov/atc/alcohol-permit-resources/alcohol-permit-information/server-training-classes', 'description' => 'Certified server training, required within 120 days of hire for employee permit holders. The ATC online course is free; approved third-party courses also count.'],
            ['name' => 'Food protection manager certification', 'category' => 'food_safety', 'holder_type' => 'employee', 'issuing_authority' => 'Accredited exam provider (e.g. ServSafe)', 'us_state' => 'IN', 'validity_months' => 60, 'expires_at_age' => null, 'info_link' => 'https://secure.in.gov/health/food-protection/retail/food-protection-manager-certification', 'description' => 'At least one certified food protection manager is required per establishment; the certificate must be kept on site.'],
        ];
    }

    public function up(): void
    {
        Schema::table('credential_types', function (Blueprint $table) {
            $table->unsignedTinyInteger('expires_at_age')->nullable()->after('validity_months');
        });

        foreach ($this->updates() as $update) {
            $this->publicType($update['name'], $update['us_state'])->update([...$update['after'], 'updated_at' => now()]);
        }

        foreach ($this->additions() as $type) {
            if (! $this->publicType($type['name'], $type['us_state'])->exists()) {
                DB::table('credential_types')->insert([...$type, 'created_at' => now(), 'updated_at' => now()]);
            }
        }
    }

    public function down(): void
    {
        foreach ($this->additions() as $type) {
            $this->publicType($type['name'], $type['us_state'])
                ->whereNotIn('id', DB::table('credentials')->select('credential_type_id'))
                ->delete();
        }

        foreach ($this->updates() as $update) {
            $this->publicType($update['name'], $update['us_state'])->update($update['before']);
        }

        Schema::table('credential_types', function (Blueprint $table) {
            $table->dropColumn('expires_at_age');
        });
    }

    private function publicType(string $name, ?string $usState): Builder
    {
        return DB::table('credential_types')
            ->whereNull('organization_id')
            ->where('name', $name)
            ->when(
                $usState === null,
                fn ($query) => $query->whereNull('us_state'),
                fn ($query) => $query->where('us_state', $usState)
            );
    }
};
