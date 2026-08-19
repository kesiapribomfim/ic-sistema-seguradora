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
        // Puxa as instâncias necessárias para a calculadora
        $produto = \App\Models\Produto::find($data['produto_id']);
        $segurado = \App\Models\Segurado::find($data['segurado_id']);
        
        // Roda o cálculo real, invisível e seguro
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
                ->visible(fn () => $this->record->status === 'Em Elaboração')
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
                        
                    redirect()->to(CotacaoResource::getUrl('view', ['record' => $cotacao->id]));
                }),
        ];
    }
}
