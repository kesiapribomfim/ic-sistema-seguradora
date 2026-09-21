<?php

namespace App\Filament\Resources\CotacaoResource\Pages;

use App\Filament\Resources\CotacaoResource;
use App\Jobs\EnviarCotacaoEmailJob;
use App\Models\Produto;
use App\Models\Segurado;
use App\Services\CalculadoraPremioService;
use App\Services\EmissaoApoliceService;
use Filament\Actions;
use Filament\Actions\Action;
use Filament\Notifications\Notification;
use Filament\Resources\Pages\EditRecord;

class EditCotacao extends EditRecord
{
    protected static string $resource = CotacaoResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $produto = Produto::find($data['produto_id']);
        $segurado = Segurado::find($data['segurado_id']);

        $calculadora = new CalculadoraPremioService;
        $data['valor_total'] = $calculadora->calcular($produto, $data, $segurado);

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),

            // Action de envio ao cliente (role: corretor)
            Action::make('enviar_cliente')
                ->label('Enviar para o Cliente')
                ->icon('heroicon-o-paper-airplane')
                ->color('info')
                ->visible(fn () => $this->record->status === 'Em Elaboração' && auth()->user()->hasRole('Corretor'))
                ->requiresConfirmation()
                ->modalHeading('Enviar Cotação')
                ->modalDescription('Tem certeza que deseja enviar esta proposta?')
                ->action(function () {
                    $cotacao = $this->record;

                    $cotacao->update(['status' => 'Enviada ao Cliente']);

                    EnviarCotacaoEmailJob::dispatch($cotacao);

                    Notification::make()
                        ->title('E-mail na fila de envio!')
                        ->success()
                        ->send();

                    return redirect()->to(CotacaoResource::getUrl('view', ['record' => $cotacao->id]));
                }),
            Action::make('avaliar_subscricao')
                ->label('Avaliar Risco')
                ->icon('heroicon-o-shield-check')
                ->color('info')
                ->visible(fn () => auth()->user()->hasRole('Subscritor') && $this->record->status === 'Em Subscrição')
                ->form([
                    Select::make('decisao')
                        ->label('Parecer da Subscrição')
                        ->options([
                            'Aceita' => 'Aprovar',
                            'Recusada' => 'Recusar',
                        ])
                        ->required(),
                ])
                ->action(function (array $data, EmissaoApoliceService $emissaoService) {
                    $cotacao = $this->record;

                    if ($data['decisao'] === 'Recusada') {
                        $cotacao->update(['status' => 'Recusada']);

                        Notification::make()
                            ->title('Risco recusado.')
                            ->danger()
                            ->send();

                        return; // Encerra a execução aqui
                    }

                    if ($data['decisao'] === 'Aceita') {
                        $cotacao->update(['status' => 'Aceita']);

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
