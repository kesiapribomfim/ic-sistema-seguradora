<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use App\Models\Cobertura;

class CoberturaSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $coberturas = [
            // --- RAMO: AUTO ---
            ['ramo' => 'Auto', 'nome' => 'Roubo e Furto', 'descricao' => 'Subtração parcial ou total do veículo segurado'],
            ['ramo' => 'Auto', 'nome' => 'Cobertura de Colisões (Casco)', 'descricao' => 'Danos materiais causados ao próprio veículo por batida'],
            ['ramo' => 'Auto', 'nome' => 'Incêndio', 'descricao' => 'Danos causados por fogo, queda de raio ou explosão no veículo'],
            ['ramo' => 'Auto', 'nome' => 'Danos da Natureza', 'descricao' => 'Danos por enchentes, alagamentos, vendavais e granizo'],
            ['ramo' => 'Auto', 'nome' => 'Danos a Terceiros', 'descricao' => 'Cobertura para danos materiais e corporais causados a outras pessoas'],
            ['ramo' => 'Auto', 'nome' => 'Assistência 24h', 'descricao' => 'Serviços de guincho, socorro mecânico e chaveiro'],
            ['ramo' => 'Auto', 'nome' => 'Danos a Vidros', 'descricao' => 'Reparo ou troca de para-brisas, faróis, lanternas e retrovisores'],
            ['ramo' => 'Auto', 'nome' => 'Carro Reserva', 'descricao' => 'Disponibilização de veículo locado em caso de sinistro'],

            // --- RAMO: RESIDENCIAL ---
            ['ramo' => 'Residencial', 'nome' => 'Danos Elétricos', 'descricao' => 'Danos a aparelhos e instalações causados por curto-circuito'],
            ['ramo' => 'Residencial', 'nome' => 'Incêndio, Raio e Explosão', 'descricao' => 'Cobertura básica para a estrutura e bens do imóvel'],
            ['ramo' => 'Residencial', 'nome' => 'Roubo e Furto', 'descricao' => 'Subtração de bens no interior da residência mediante arrombamento'],
            ['ramo' => 'Residencial', 'nome' => 'Responsabilidade Civil', 'descricao' => 'Danos materiais ou corporais causados a terceiros por moradores ou animais'],
            ['ramo' => 'Residencial', 'nome' => 'Danos a Vidros', 'descricao' => 'Quebra acidental de vidros, espelhos e mármores da residência'],
            ['ramo' => 'Residencial', 'nome' => 'Impacto de Veículos ou Aviões', 'descricao' => 'Danos causados pela colisão de veículos terrestres ou queda de aeronaves'],
            ['ramo' => 'Residencial', 'nome' => 'Vendaval, Granizo e Tornado', 'descricao' => 'Danos causados por ventos fortes e chuva de granizo'],

            // --- RAMO: VIDA ---
            ['ramo' => 'Vida', 'nome' => 'Morte (Qualquer Causa)', 'descricao' => 'Pagamento do capital segurado aos beneficiários em caso de falecimento'],
            ['ramo' => 'Vida', 'nome' => 'Invalidez Permanente Total por Acidente', 'descricao' => 'Indenização em caso de perda total das funções por acidente'],
            ['ramo' => 'Vida', 'nome' => 'Invalidez Permanente por Acidente', 'descricao' => 'Indenização proporcional à perda parcial ou total de membros/órgãos'],
            ['ramo' => 'Vida', 'nome' => 'Indenização em Dobro por Morte Acidental', 'descricao' => 'Pagamento de 200% do capital segurado se o falecimento for acidental'],
            ['ramo' => 'Vida', 'nome' => 'Antecipação Especial por Doença', 'descricao' => 'Adiantamento da indenização em diagnósticos de doenças graves terminais'],
            ['ramo' => 'Vida', 'nome' => 'Diárias por Incapacidade Temporária', 'descricao' => 'Pagamento de diárias durante afastamento do trabalho por doença ou acidente'],
            ['ramo' => 'Vida', 'nome' => 'Despesas Médico-Hospitalares e Odontológicas', 'descricao' => 'Reembolso de gastos médicos decorrentes de acidentes pessoais'],
            ['ramo' => 'Vida', 'nome' => 'Assistência Funeral', 'descricao' => 'Reembolso ou prestação de serviços para despesas com o funeral do segurado'],
        ];

        foreach ($coberturas as $cobertura) {
            Cobertura::create($cobertura);
        }
    }
}
