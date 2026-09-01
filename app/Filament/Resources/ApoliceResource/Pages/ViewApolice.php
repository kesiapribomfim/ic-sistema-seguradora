<?php

namespace App\Filament\Resources\ApoliceResource\Pages;

use App\Filament\Resources\ApoliceResource;
use Filament\Actions;
use Filament\Resources\Pages\ViewRecord;
use Barryvdh\DomPDF\Facade\Pdf;

class ViewApolice extends ViewRecord
{
    protected static string $resource = ApoliceResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\Action::make('gerar_pdf')
                ->label('Baixar PDF')
                ->color('danger')
                ->icon('heroicon-o-document-arrow-down')
                ->visible(fn() => $this->getRecord()->status !== 'Cancelada')
                ->action(function () {
                    $record = $this->getRecord();

                    $record->loadMissing([
                        'segurado.seguradoPf',
                        'segurado.seguradoPj',
                        'cotacao.produto',
                        'pagamentos'
                    ]);

                    $pdf = Pdf::loadView('pdf.apolice', ['apolice' => $record]);

                    return response()->streamDownload(
                        fn() => print($pdf->output()),
                        "apolice-{$record->numero_apolice}.pdf"
                    );
                }),
        ];
    }

    public function mount(int | string $record): void
    {
        parent::mount($record);

        activity()
            ->causedBy(auth()->user()) 
            ->performedOn($this->record) 
            ->event('view')
            ->log('Visualizou os dados da Apólice');
    }
}
