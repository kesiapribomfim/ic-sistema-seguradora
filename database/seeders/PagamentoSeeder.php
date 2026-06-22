<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Apolice;
use App\Models\Pagamento;
use App\Models\Sinistro;

class PagamentoSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $apolices = Apolice::where('status','vigente')->get();
        $sinistros = Sinistro::where('status','aprovado')->get();

        foreach($apolices as $apolice){
            Pagamento::factory()->create([
                'apolice_id' => $apolice->id,
                'sinistro_id' => null, // Força a ser nulo
                'tipo_movimentacao' => 'Recebimento',

            ]);
        }
        
        foreach($sinistros as $sinistro){
            Pagamento::factory()->create([
                'sinistro_id'=>$sinistro->id,
                'apolice_id'=>$sinistro->apolice_id,
                'tipo_movimentacao' => 'Pagamento Indenização',
            ]);
        }

        
    }
}
