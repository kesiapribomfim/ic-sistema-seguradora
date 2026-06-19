<?php

namespace Database\Seeders;
use App\Models\Apolice;
use App\Models\Cotacao;
use App\Models\Segurado;
use App\Models\User;
use App\Models\Filial;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class ApoliceSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $cotacoes = Cotacao::all();

        foreach ($cotacoes as $cotacao){
            Apolice::factory()->create([
                'cotacao_id'  => $cotacao->id,
                'segurado_id' => $cotacao->segurado_id,
                'filial_id'   => $cotacao->filial_id,
                'user_id'     => $cotacao->user_id,
                'valor_total' => $cotacao->valor_total,
            ]);
        }

        $apoliceAntiga = Apolice::first();

    if ($apoliceAntiga) {
        // Criamos uma nova cotação para a renovação
        $cotacaoRenovacao = Cotacao::factory()->create([
            'segurado_id' => $apoliceAntiga->segurado_id,
        ]);

        // Emitimos a Apólice de Renovação apontando para a antiga
        Apolice::factory()->create([
            'cotacao_id'        => $cotacaoRenovacao->id,
            'segurado_id'       => $cotacaoRenovacao->segurado_id,
            'filial_id'         => $cotacaoRenovacao->filial_id,
            'user_id'           => $cotacaoRenovacao->user_id,
            'valor_total'       => $cotacaoRenovacao->valor_total,
            'status'            => 'Vigente',
            'apolice_origem_id' => $apoliceAntiga->id, // <--- AQUI ESTÁ O VÍNCULO!
        ]);

        $apoliceAntiga->update(['status' => 'Renovada']);

        }
    }
}
