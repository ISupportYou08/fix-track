<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            ServiceCatalogSeeder::class,
            DemoServiceCatalogSeeder::class,
            DemoAccountsSeeder::class,
            DemoCategoryTechniciansSeeder::class,
            DemoWalkInShopsSeeder::class,
        ]);
    }
}
