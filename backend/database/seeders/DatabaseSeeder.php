<?php

namespace Database\Seeders;

use App\Models\Portfolio;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // User::factory(10)->create();

        Portfolio::firstOrCreate(['owner' => 'demo'], [
            'cash_pkr' => 2500000,
            'gold_grams' => 20,
            'platform_gold_grams' => 100,
        ]);
    }
}
