<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /** Reference data is always seeded; demo users and cases only outside production (or with SEED_DEMO=true). */
    public function run(): void
    {
        $this->call([
            RolesAndPermissionsSeeder::class,
            TaxonomySeeder::class,
            KnowledgeSeeder::class,
            KpiSeeder::class,
            ServicePathSeeder::class,
            PilotProgramSeeder::class,
        ]);

        if (! app()->isProduction() || env('SEED_DEMO', false)) {
            $this->call([PartnerSeeder::class, DemoSeeder::class]);
        }
    }
}
