<?php

namespace App\Filament\Resources\CotacaoResource\Pages;

use App\Filament\Resources\CotacaoResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;


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

            Actions\Action::make('avaliar_subscricao')
                ->label('Avaliar Risco')
                ->icon('heroicon-o-shield-check')
                ->color('warning')
                ->visible(fn () => auth()->user()->hasRole('Subscritor') && in_array($this->record->status, ['Em Elaboração', 'Enviada ao Cliente']))
                ->form([
                    Select::make('decisao')
                        ->label('Parecer da Subscrição')
                        ->options([
                            'Aceita' => 'Aprovar Risco (Aceitar)',
                            'Recusada' => 'Recusar Risco',
                        ])
                        ->required(),
                        
                    Textarea::make('motivo')
                        ->label('Justificativa / Parecer Técnico')
                        ->required()
                        ->rows(3),
                ])
                ->action(function (array $data) {
                    $this->record->update([
                        'status' => $data['decisao'],
                    ]);
                    
                    \Filament\Notifications\Notification::make()
                        ->title('Avaliação registrada com sucesso!')
                        ->success()
                        ->send();
                }),
        ];
    }
}

