<?php

namespace Database\Seeders;

use App\Models\Segurado;
use App\Models\SeguradoPf;
use App\Models\SeguradoPj;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Seeder;

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
