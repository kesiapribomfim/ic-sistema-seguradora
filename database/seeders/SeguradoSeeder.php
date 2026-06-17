<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Segurado;
use App\Models\User;
use App\Models\SeguradoPj;
use App\Models\SeguradoPf;

class SeguradoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        Segurado::factory()
        ->count(10)
        ->for(User::factory()) 
        ->has(SeguradoPf::factory(), 'seguradoPf')
        ->create();
    
        Segurado::factory()
        ->count(10)
        ->for(User::factory())
        ->has(SeguradoPj::factory(), 'seguradoPj')
        ->create(); 
    
    }
}
