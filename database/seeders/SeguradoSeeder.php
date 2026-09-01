<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Segurado;
use App\Models\User;
use App\Models\SeguradoPj;
use App\Models\SeguradoPf;
USE Illuminate\Database\Eloquent\Builder;

class SeguradoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $corretores = User::whereHas('filiais', function (Builder $query) {
            $query->where('filial_user.perfil_acesso', 'Corretor');
        })->get();

        if ($corretores->isEmpty()) {
            $this->command->warn('Nenhum corretor encontrado! Rode a UserSeeder primeiro.');
            return;
        }

        Segurado::factory()
            ->count(10)
            ->state(function (array $attributes) use ($corretores) {
                return [
                    'tipo' => 'PF',
                    'corretor_id' => $corretores->random()->id,
                ];
            })
            ->has(SeguradoPf::factory(), 'seguradoPf')
            ->create();

        Segurado::factory()
            ->count(10)
            ->state(function (array $attributes) use ($corretores) {
                return [
                    'tipo' => 'PJ',
                    'corretor_id' => $corretores->random()->id,
                ];
            })
            ->has(SeguradoPj::factory(), 'seguradoPj')
            ->create();
    }
}
