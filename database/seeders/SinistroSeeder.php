<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Sinistro;
use App\Models\Apolice;

class SinistroSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $apolicesVigentes = Apolice::where('status','Vigente')->get();
        
        if ($apolicesVigentes->isNotEmpty()) {
            Sinistro::factory()
                ->count(15) 
                ->recycle($apolicesVigentes)
                ->create();
        }
    }
}
