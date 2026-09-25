<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            RolePermissionSeeder::class,
            OrganisationSeeder::class,
            ProductCatalogSeeder::class,
            MasterDataSeeder::class,
            LenderSeeder::class,
            // Development sample data - safe to remove in production.
            DemoDataSeeder::class,
        ]);
    }
}
