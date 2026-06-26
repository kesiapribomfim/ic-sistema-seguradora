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
        
        Segurado::factory()
        ->count(10)
        ->for(User::factory()) 
        ->state(['tipo' => 'PF']) 
        ->recycle($corretores)
        ->has(SeguradoPf::factory(), 'seguradoPf')
        ->create();
    
        Segurado::factory()
        ->count(10)
        ->for(User::factory())
        ->state(['tipo' => 'PJ']) 
        ->recycle($corretores)
        ->has(SeguradoPj::factory(), 'seguradoPj')
        ->create(); 
    
    }
}
