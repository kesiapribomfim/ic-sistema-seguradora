<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Cotacao;
use App\Models\Segurado;
use App\Models\Produto;
use App\Models\User;
use App\Models\Filial;
use Illuminate\Database\Eloquent\Builder;

class CotacaoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $corretores = User::whereHas('filiais', function (Builder $query) {
            $query->where('filial_user.perfil_acesso', 'Corretor');
        })->get();

        $segurados = Segurado::all();
        $produtos = Produto::all();
        $filiais = Filial::all();

    
        Cotacao::factory()
        ->count(3)
        ->recycle($corretores)
        ->recycle($segurados)
        ->recycle($produtos)
        ->recycle($filiais)
        ->create();
    }
}
