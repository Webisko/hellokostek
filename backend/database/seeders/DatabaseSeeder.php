<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            CuranderaProductSeeder::class,
            DevCmsReviewSeeder::class,
            OrderRelationsSeeder::class,
            HelloKostekSeeder::class,
        ]);
    }
}
