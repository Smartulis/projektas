<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
// ← Add this:
use Database\Seeders\ProductsServicesSeeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call(MeasurementUnitsTableSeeder::class);
        $this->call(ProductsServicesSeeder::class);

        // Sukuria admin userį, jei jo dar nėra
        \App\Models\User::firstOrCreate([
            'email' => 'kokosas@geriausias.com',
        ], [
            'name' => 'Admin',
            'password' => bcrypt('kok0sas2002'), // PASIKEISK!
        ]);
    }
}
