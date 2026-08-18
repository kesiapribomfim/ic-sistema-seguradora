<?php

namespace App\Filament\Resources\CotacaoResource\Pages;

use App\Filament\Resources\CotacaoResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditCotacao extends EditRecord
{
    protected static string $resource = CotacaoResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
            
            \Filament\Actions\Action::make('enviar_cliente')
                ->label('Enviar para o Cliente')
                ->icon('heroicon-o-paper-airplane')
                ->color('info')
                ->visible(fn () => $this->record->status === 'Em Elaboração')
                ->requiresConfirmation()
                ->modalHeading('Enviar Cotação')
                ->modalDescription('Tem certeza que deseja enviar esta proposta? O status será alterado e um e-mail será disparado para o cliente.')
                ->action(function () { 
                    $cotacao = $this->record; 
                    
                    $cotacao->update(['status' => 'Enviada ao Cliente']);
                    
                    \App\Jobs\EnviarCotacaoEmailJob::dispatch($cotacao);

                    \Filament\Notifications\Notification::make()
                        ->title('E-mail na fila de envio!')
                        ->success()
                        ->send();
                        
                    redirect()->to(CotacaoResource::getUrl('view', ['record' => $cotacao->id]));
                }),
        ];
    }
}
