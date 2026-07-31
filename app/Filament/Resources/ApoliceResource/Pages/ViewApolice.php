<?php
namespace App\Filament\Resources\ApoliceResource\Pages;

use App\Filament\Resources\ApoliceResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;

class ViewApolice extends ViewRecord
{
    protected static string $resource = ApoliceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('gerar_pdf')
                ->label('Baixar')
                ->color('danger')
                ->icon('heroicon-o-document-arrow-down')
                ->action(function () {
                    // TODO: lógica do barryvdh/laravel-dompdf no futuro!
                    
                    \Filament\Notifications\Notification::make()
                        ->title('Emissão de PDF')
                        ->body('A geração do documento está em desenvolvimento e será conectada em breve.')
                        ->info()
                        ->send();
                }),

            Actions\EditAction::make(),
        ];
    }
}