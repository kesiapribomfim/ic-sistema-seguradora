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
    // 1. Busca APENAS as cotações que têm status 'Aceita'
    $cotacoesAceitas = Cotacao::where('status', 'Aceita')->get();

    // 2. Pega apenas a METADE dessas cotações para gerar apólices automáticas
    // A outra metade vai ficar sem apólice para podermos ver o botão no painel!
    $metade = (int) ($cotacoesAceitas->count() / 2);
    $cotacoesParaEmitir = $cotacoesAceitas->take($metade);

    foreach ($cotacoesParaEmitir as $cotacao){
        Apolice::factory()->create([
            'cotacao_id'  => $cotacao->id,
            'segurado_id' => $cotacao->segurado_id,
            'filial_id'   => $cotacao->filial_id,
            'user_id'     => $cotacao->user_id,
            'valor_total' => $cotacao->valor_total,
        ]);
    }

    // A sua lógica de renovação que já estava ótima continua aqui
    $apoliceAntiga = Apolice::first();

    if ($apoliceAntiga) {
        $cotacaoRenovacao = Cotacao::factory()->create([
            'segurado_id' => $apoliceAntiga->segurado_id,
            'status'      => 'Aceita', // Garantindo que a de renovação também nasce aceita
        ]);

        Apolice::factory()->create([
            'cotacao_id'        => $cotacaoRenovacao->id,
            'segurado_id'       => $cotacaoRenovacao->segurado_id,
            'filial_id'         => $cotacaoRenovacao->filial_id,
            'user_id'           => $cotacaoRenovacao->user_id,
            'valor_total'       => $cotacaoRenovacao->valor_total,
            'status'            => 'Vigente',
            'apolice_origem_id' => $apoliceAntiga->id,
        ]);

        $apoliceAntiga->update(['status' => 'Renovada']);
    }
}
}
