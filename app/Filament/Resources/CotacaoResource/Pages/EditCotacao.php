<?php

namespace App\Filament\Resources\CotacaoResource\Pages;

use App\Filament\Resources\ApoliceResource;
use App\Filament\Resources\CotacaoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;
use Illuminate\Support\Facades\Redirect;

class EditCotacao extends EditRecord
{
    protected static string $resource = CotacaoResource::class;

    protected function mutateFormDataBeforeSave(array $data): array
    {
        $produto = \App\Models\Produto::find($data['produto_id']);
        $segurado = \App\Models\Segurado::find($data['segurado_id']);
        
        $calculadora = new \App\Services\CalculadoraPremioService();
        $data['valor_total'] = $calculadora->calcular($produto, $data, $segurado);

        return $data;
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            
            //Action de envio ao cliente (role: corretor)
            \Filament\Actions\Action::make('enviar_cliente')
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
                    
                    \App\Jobs\EnviarCotacaoEmailJob::dispatch($cotacao);

                    \Filament\Notifications\Notification::make()
                        ->title('E-mail na fila de envio!')
                        ->success()
                        ->send();
                        
                    return redirect()->to(CotacaoResource::getUrl('view', ['record' => $cotacao->id]));
                }),
            Actions\Action::make('avaliar_subscricao')
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
                ->action(function (array $data, \App\Services\EmissaoApoliceService $emissaoService) {
                    $cotacao = $this->record;
                    
                    if ($data['decisao'] === 'Recusada') {
                        $cotacao->update(['status' => 'Recusada']);
                        
                        \Filament\Notifications\Notification::make()
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
                        
                        \Filament\Notifications\Notification::make()
                            ->title('Aprovado! Apólice Emitida com Sucesso.')
                            ->success()
                            ->send();
                    }
                }),
        ];
    }
}
