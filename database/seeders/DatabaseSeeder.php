<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $this->call([
            PassportClientSeeder::class,
            PermissionSeeder::class,
            RoleSeeder::class,
            AdminUserSeeder::class,
            CatalogSeeder::class,
            ClipartSeeder::class,
            PhraseTemplateSeeder::class,
            MockupSeeder::class,
            GarmentModelSeeder::class,
            GarmentModelLibrarySeeder::class,
            CmsSeeder::class,
        ]);
    }
}
