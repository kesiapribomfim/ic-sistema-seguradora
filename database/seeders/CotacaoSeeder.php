<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Cotacao;
use App\Models\Segurado;
use App\Models\Produto;
use App\Models\User;
use App\Models\Filial;

class CotacaoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $corretores= User::where('tipo','corretor')->get();

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
