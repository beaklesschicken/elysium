<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

/**
 * Public catalog entries every organization can pick from. Validity is left empty where it
 * varies by state or provider, so the person entering a credential types the real expiry.
 */
return new class extends Migration
{
    /**
     * @return list<array{name: string, category: string, holder_type: string, issuing_authority: ?string, us_state: ?string, validity_months: ?int, info_link: ?string, description: ?string}>
     */
    private function types(): array
    {
        return [
            ['name' => 'Alcohol server permit', 'category' => 'alcohol_permit', 'holder_type' => 'employee', 'issuing_authority' => 'Indiana Alcohol and Tobacco Commission', 'us_state' => 'IN', 'validity_months' => null, 'info_link' => 'https://www.in.gov/atc/', 'description' => 'Employee permit for serving or selling alcohol.'],
            ['name' => 'Alcohol server training', 'category' => 'training', 'holder_type' => 'employee', 'issuing_authority' => null, 'us_state' => null, 'validity_months' => null, 'info_link' => null, 'description' => 'Responsible beverage service course such as TIPS or ServSafe Alcohol.'],
            ['name' => 'Food handler certificate', 'category' => 'food_safety', 'holder_type' => 'employee', 'issuing_authority' => null, 'us_state' => null, 'validity_months' => null, 'info_link' => null, 'description' => 'Basic food safety training for staff who handle food.'],
            ['name' => 'Food protection manager certification', 'category' => 'food_safety', 'holder_type' => 'employee', 'issuing_authority' => null, 'us_state' => null, 'validity_months' => null, 'info_link' => null, 'description' => 'Certified food protection manager, such as ServSafe Manager.'],
            ['name' => 'Retail alcohol permit', 'category' => 'alcohol_permit', 'holder_type' => 'unit', 'issuing_authority' => 'Indiana Alcohol and Tobacco Commission', 'us_state' => 'IN', 'validity_months' => null, 'info_link' => 'https://www.in.gov/atc/', 'description' => 'The location\'s permit to sell alcohol.'],
            ['name' => 'Food establishment permit', 'category' => 'license', 'holder_type' => 'unit', 'issuing_authority' => 'Local health department', 'us_state' => null, 'validity_months' => null, 'info_link' => null, 'description' => 'Health department permit to operate a food service location.'],
            ['name' => 'Liquor liability insurance', 'category' => 'insurance', 'holder_type' => 'unit', 'issuing_authority' => null, 'us_state' => null, 'validity_months' => null, 'info_link' => null, 'description' => 'Policy covering alcohol-related claims.'],
            ['name' => 'Business registration', 'category' => 'license', 'holder_type' => 'organization', 'issuing_authority' => 'Secretary of State', 'us_state' => null, 'validity_months' => null, 'info_link' => null, 'description' => 'The business entity\'s state registration or report.'],
            ['name' => 'Retail merchant certificate', 'category' => 'license', 'holder_type' => 'organization', 'issuing_authority' => 'Indiana Department of Revenue', 'us_state' => 'IN', 'validity_months' => null, 'info_link' => null, 'description' => 'Certificate to collect sales tax.'],
        ];
    }

    public function up(): void
    {
        $now = now();

        foreach ($this->types() as $type) {
            $exists = DB::table('credential_types')
                ->whereNull('organization_id')
                ->where('name', $type['name'])
                ->when(
                    $type['us_state'] === null,
                    fn ($query) => $query->whereNull('us_state'),
                    fn ($query) => $query->where('us_state', $type['us_state'])
                )
                ->exists();

            if (! $exists) {
                DB::table('credential_types')->insert([...$type, 'created_at' => $now, 'updated_at' => $now]);
            }
        }
    }

    public function down(): void
    {
        DB::table('credential_types')
            ->whereNull('organization_id')
            ->whereIn('name', array_column($this->types(), 'name'))
            ->whereNotIn('id', DB::table('credentials')->select('credential_type_id'))
            ->delete();
    }
};
