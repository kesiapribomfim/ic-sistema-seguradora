<?php

namespace App\Filament\Resources\CotacaoResource\Pages;

use App\Filament\Resources\CotacaoResource;
use App\Jobs\EnviarCotacaoEmailJob;
use App\Models\Cotacao;
use App\Services\EmissaoApoliceService;
use Filament\Actions;
use Filament\Forms\Components\Select;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\ViewRecord;

class ViewCotacao extends ViewRecord
{
    protected static string $resource = CotacaoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('ir_edit')
                ->label('Editar')
                ->color('primary')
                ->action(function () {
                    $this->redirect($this->getResource()::getUrl('edit', ['record' => $this->record]));
                }),

            Actions\Action::make('enviar_cliente')
                ->label('Enviar para o Cliente')
                ->icon('heroicon-o-paper-airplane')
                ->color('info')
                ->visible(fn () => $this->record->status === Cotacao::STATUS_ELABORACAO && auth()->user()->hasRole('Corretor'))
                ->requiresConfirmation()
                ->modalHeading('Enviar Cotação')
                ->modalDescription('Tem certeza que deseja enviar esta proposta?')
                ->action(function () {
                    $cotacao = $this->record;

                    $cotacao->update(['status' => Cotacao::STATUS_ENVIADA]);

                    EnviarCotacaoEmailJob::dispatch($cotacao);

                    Notification::make()
                        ->title('E-mail na fila de envio!')
                        ->success()
                        ->send();

                    return redirect()->to(CotacaoResource::getUrl('view', ['record' => $cotacao->id]));
                }),

            // Action subscrição
            Actions\Action::make('avaliar_subscricao')
                ->label('Avaliar Risco')
                ->icon('heroicon-o-shield-check')
                ->color('info')
                ->visible(fn () => auth()->user()->hasRole('Subscritor') && $this->record->status === Cotacao::STATUS_EM_SUBSCRICAO)
                ->form([
                    Select::make('decisao')
                        ->label('Parecer da Subscrição')
                        ->options([
                            Cotacao::STATUS_ACEITA => 'Aprovar',
                            Cotacao::STATUS_RECUSADA => 'Recusar',
                        ])
                        ->required(),
                ])
                ->action(function (array $data, EmissaoApoliceService $emissaoService) {
                    $cotacao = $this->record;

                    if ($data['decisao'] === Cotacao::STATUS_RECUSADA) {
                        $cotacao->update(['status' => Cotacao::STATUS_RECUSADA]);

                        Notification::make()
                            ->title('Risco recusado.')
                            ->danger()
                            ->send();

                        return; // Encerra a execução aqui
                    }

                    if ($data['decisao'] === Cotacao::STATUS_ACEITA) {
                        $cotacao->update(['status' => Cotacao::STATUS_ACEITA]);

                        $formaPagamento = $cotacao->forma_pagamento_preferida ?? 'Boleto Bancário';
                        $parcelas = $cotacao->quantidade_parcelas_preferida ?? 1;

                        $apolice = $emissaoService->emitir($cotacao, $formaPagamento, $parcelas);

                        Notification::make()
                            ->title('Aprovado! Apólice Emitida com Sucesso.')
                            ->success()
                            ->send();
                    }
                }),
        ];
    }
}
