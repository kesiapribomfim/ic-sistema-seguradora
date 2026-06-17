<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\User;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        User::factory()->create([
            'name' => 'Admin Geral',
            'email' => 'admin_test@exemplo.com',
            'password' => 'password',
            'status' => true,
            'tipo' => 'Administrador Geral',
        ]);

        User::factory()->count(20)->create();
    }
}
