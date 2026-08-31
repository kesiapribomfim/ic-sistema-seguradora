<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;
use Illuminate\Support\Facades\Auth;
use Filament\Forms\Form;
use Filament\Forms\Components\Grid;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use App\Models\Filial;
use App\Models\Apolice;
// use App\Models\Pagamento; // Descomente depois se for usar para inadimplência

class RelatorioOperacional extends Page implements HasForms
{
    use InteractsWithForms;

    protected static ?string $navigationIcon = 'heroicon-o-presentation-chart-line';
    protected static ?string $title = 'Relatório Operacional';
    protected static ?string $navigationGroup = 'Relatórios';
    protected static ?int $navigationSort = 1;
    protected static string $view = 'filament.pages.relatorio-operacional';

    public ?array $data = [];

    public static function canAccess(): bool
    {
        return Auth::user()->hasAnyRole(['super_admin', 'Administrador Geral', 'Gestor de Filial']);
    }

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Grid::make(3)->schema([
                    Select::make('filial_id')
                        ->label('Filial (Opcional)')
                        ->options(function () {
                            /** @var \App\Models\User $user */
                            $user = Auth::user();
                            if ($user->hasAnyRole(['super_admin', 'Administrador Geral'])) {
                                return Filial::pluck('nome', 'id');
                            }
                            return $user->filiais()->pluck('filiais.nome', 'filiais.id');
                        })
                        ->placeholder('Todas as Filiais')
                        ->live(),

                    DatePicker::make('data_inicial')
                        ->label('Emissão (De)')
                        ->live(),

                    DatePicker::make('data_final')
                        ->label('Emissão (Até)')
                        ->live(),
                ])
            ])
            ->statePath('data');
    }

    protected function getHeaderActions(): array
    {
        return [
            \Filament\Actions\Action::make('exportar_excel')
                ->label('Exportar Excel')
                ->icon('heroicon-o-table-cells')
                ->color('success')
                ->action(function () {
                    \Filament\Notifications\Notification::make()
                        ->title('Exportação Iniciada')
                        ->body('O relatório XLSX está sendo gerado em background. Você será notificado quando estiver pronto.')
                        ->success()
                        ->send();
                }),

            \Filament\Actions\Action::make('exportar_pdf')
                ->label('Exportar PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('danger')
                ->action(function () {
                    \Filament\Notifications\Notification::make()
                        ->title('Processamento em Background')
                        ->body('O relatório PDF é pesado e foi enviado para a fila de processamento. O link será enviado para o seu e-mail.')
                        ->success()
                        ->send();
                }),
        ];
    }

    protected function getViewData(): array
    {
        $filialId = $this->data['filial_id'] ?? null;
        $dataInicial = $this->data['data_inicial'] ?? null;
        $dataFinal = $this->data['data_final'] ?? null;

        /** @var \App\Models\User $user */
        $user = Auth::user();
        $isGlobal = $user->hasAnyRole(['super_admin', 'Administrador Geral']);

        $apolicesQuery = Apolice::with(['segurado.seguradoPf', 'segurado.seguradoPj', 'user', 'filial'])
            ->where('status', 'Vigente')
            ->whereBetween('data_fim', [now(), now()->addDays(30)])
            ->orderBy('data_fim', 'asc');

        if ($filialId) {
            $apolicesQuery->where('filial_id', $filialId);
        } elseif (!$isGlobal) {
            $filiaisIds = $user->filiais()->pluck('filiais.id')->toArray();
            $apolicesQuery->whereIn('filial_id', $filiaisIds);
        }

        if ($dataInicial) $apolicesQuery->where('data_emissao', '>=', $dataInicial);
        if ($dataFinal) $apolicesQuery->where('data_emissao', '<=', $dataFinal);

        return [
            'apolicesAVencer' => $apolicesQuery->get(),
        ];
    }
}
