<?php

namespace App\Filament\Resources\CotacaoResource\Pages;

use App\Filament\Resources\CotacaoResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Illuminate\Database\Console\Migrations\StatusCommand;
use App\Filament\Resources\ApoliceResource;

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

            //Action subscrição
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
                            
                        redirect()->to(ApoliceResource::getUrl('view', ['record' => $apolice->id]));
                    }
                }),
        ];
    }
}

