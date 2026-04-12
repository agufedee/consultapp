<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     *
     * Uses environment variables for credentials:
     * - SEED_ADMIN_NAME, SEED_ADMIN_EMAIL, SEED_ADMIN_PASSWORD
     * - SEED_SECRETARY_NAME, SEED_SECRETARY_EMAIL, SEED_SECRETARY_PASSWORD
     */
    public function run(): void
    {
        // Get role IDs from the catalog
        $nutritionistRole = Role::where('name', 'Nutricionista')->first();
        $secretaryRole = Role::where('name', 'Secretaria')->first();

        // Admin user (Nutricionista)
        User::create([
            'name' => env('SEED_ADMIN_NAME', 'Maria Fernanda Trinidad'),
            'email' => env('SEED_ADMIN_EMAIL', 'nutricionista@consultapp.com'),
            'password' => env('SEED_ADMIN_PASSWORD', 'changeme'),
            'role_id' => $nutritionistRole?->id ?? 1,
            'active' => true,
        ]);

        // Secretary user
        User::create([
            'name' => env('SEED_SECRETARY_NAME', 'Ana Perez'),
            'email' => env('SEED_SECRETARY_EMAIL', 'secretaria@consultapp.com'),
            'password' => env('SEED_SECRETARY_PASSWORD', 'changeme'),
            'role_id' => $secretaryRole?->id ?? 2,
            'active' => true,
        ]);
    }
}
