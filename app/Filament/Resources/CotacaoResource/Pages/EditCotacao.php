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
                ->requiresConfirmation()
                ->action(function () { 
                    $cotacao = $this->record; 
                    
                    $cotacao->update(['status' => 'Enviada ao cliente']);
                    
                    \App\Jobs\EnviarCotacaoEmailJob::dispatch($cotacao);

                    \Filament\Notifications\Notification::make()
                        ->title('E-mail na fila de envio!')
                        ->success()
                        ->send();
                }),
        ];
    }
}
