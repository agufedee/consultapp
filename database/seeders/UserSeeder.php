<?php

namespace Database\Seeders;

use App\Models\Role;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

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
            'name' => config('app.seed_admin_name', 'Maria Fernanda Trinidad'),
            'email' => config('app.seed_admin_email', 'nutricionista@consultapp.com'),
            'password' => Hash::make(config('app.seed_admin_password', 'Nutricionista2024!')),
            'role_id' => $nutritionistRole?->id ?? 1,
            'active' => true,
        ]);

        // Secretary user
        User::create([
            'name' => config('app.seed_secretary_name', 'Ana Perez'),
            'email' => config('app.seed_secretary_email', 'secretaria@consultapp.com'),
            'password' => Hash::make(config('app.seed_secretary_password', 'Secretaria2024!')),
            'role_id' => $secretaryRole?->id ?? 2,
            'active' => true,
        ]);
    }
}
