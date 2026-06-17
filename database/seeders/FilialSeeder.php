<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Filial;
use App\Models\User;

class FilialSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Filial::factory()
        ->count(10)
        ->hasAttached(
            User::factory()->count(1),
            ['perfil_acesso' => array_rand(array_flip([ 
                    'Gestor de Filial', 
                    'Subscritor',
                    'Corretor',
                    'Analista de Sinistro',
                    'Financeiro',
                    'Cliente'
                ]))]

            )
            
        ->create();

    }
}
