<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Run with:
     *   php artisan db:seed                   -- document types + demo data
     *   php artisan db:seed --class=DocumentTypeSeeder  -- types only (idempotent)
     *   php artisan db:seed --class=DemoSeeder          -- demo vendors/users only
     */
    public function run(): void
    {
        // Always seed document types and category requirements first -
        // demo vendors depend on these.
        $this->call(DocumentTypeSeeder::class);

        // Demo data: 5 vendors + user accounts for all 5 roles.
        $this->call(DemoSeeder::class);
    }
}
