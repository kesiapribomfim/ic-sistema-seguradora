<?php

namespace App\Filament\Resources\SinistroResource\Pages;

use App\Filament\Resources\SinistroResource;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateSinistro extends CreateRecord
{
    protected static string $resource = SinistroResource::class;

    /**
     * Este Hook roda exatamente 1 milissegundo depois que o Sinistro 
     * e a Movimentação inicial (do Observer) foram salvos no banco.
     */
    protected function afterCreate(): void
    {
        // 1. Pega os caminhos dos arquivos que foram upados na tela
        $anexos = $this->data['anexos_temporarios'] ?? null;

        if ($anexos) {
            $sinistro = $this->record;

            $movimentacaoAbertura = $sinistro->movimentacoes()
                ->where('acao_realizada', 'Abertura')
                ->first();

            if ($movimentacaoAbertura) {
                $movimentacaoAbertura->update([
                    'anexos' => $anexos
                ]);
            }
        }
    }
}