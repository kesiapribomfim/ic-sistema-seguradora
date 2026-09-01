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
            // ========================================================
            // BOTÃO EXCEL (Gerando um CSV nativo que o Excel ama)
            // ========================================================
            \Filament\Actions\Action::make('exportar_excel')
                ->label('Exportar Excel')
                ->icon('heroicon-o-table-cells')
                ->color('success')
                ->action(function () {
                    // Pega os mesmos dados que aparecem na tela
                    $apolices = $this->getViewData()['apolicesAVencer'];
                    $nomeArquivo = 'relatorio_operacional_' . now()->format('Y_m_d_His') . '.csv';

                    return response()->streamDownload(function () use ($apolices) {
                        $handle = fopen('php://output', 'w');
                        
                        // MÁGICA: Adiciona o BOM UTF-8 para o Excel brasileiro não bugar os acentos (ç, ã, é)
                        fputs($handle, chr(0xEF) . chr(0xBB) . chr(0xBF));
                        
                        // Cabeçalho da Planilha
                        fputcsv($handle, ['Apólice', 'Cliente', 'Corretor', 'Filial', 'Fim da Vigência', 'Status'], ';');

                        // Linhas da Planilha
                        foreach ($apolices as $apolice) {
                            $cliente = $apolice->segurado->tipo === 'PF' 
                                ? $apolice->segurado->seguradoPf?->nome 
                                : $apolice->segurado->seguradoPj?->razao_social;

                            fputcsv($handle, [
                                $apolice->numero_apolice,
                                $cliente,
                                $apolice->user->name ?? '-',
                                $apolice->filial->nome ?? '-',
                                $apolice->data_fim ? $apolice->data_fim->format('d/m/Y') : '-',
                                $apolice->status
                            ], ';'); // Separado por ponto e vírgula, o padrão brasileiro
                        }
                        fclose($handle);
                    }, $nomeArquivo, [
                        'Content-Type' => 'text/csv; charset=UTF-8',
                    ]);
                }),

            // ========================================================
            // BOTÃO PDF (Usando a biblioteca DomPDF)
            // ========================================================
            \Filament\Actions\Action::make('exportar_pdf')
                ->label('Exportar PDF')
                ->icon('heroicon-o-document-arrow-down')
                ->color('danger')
                ->action(function () {
                    // Pega os dados
                    $apolices = $this->getViewData()['apolicesAVencer'];
                    
                    // Montamos um HTML puro. 
                    // O DomPDF tem dificuldade com o TailwindCSS do Filament, então HTML clássico é à prova de balas.
                    $html = '<h1 style="text-align:center; font-family:sans-serif;">Relatório Operacional - Apólices a Vencer</h1>';
                    $html .= '<p style="text-align:center; font-family:sans-serif;">Gerado em: ' . now()->format('d/m/Y H:i') . '</p><hr>';
                    
                    $html .= '<table border="1" width="100%" cellspacing="0" cellpadding="8" style="font-family:sans-serif; font-size:12px; text-align:left;">';
                    $html .= '<thead style="background-color:#f3f4f6;"><tr><th>Apólice</th><th>Cliente</th><th>Corretor</th><th>Vencimento</th></tr></thead>';
                    $html .= '<tbody>';
                    
                    foreach($apolices as $apolice) {
                        $cliente = $apolice->segurado->tipo === 'PF' 
                            ? $apolice->segurado->seguradoPf?->nome 
                            : $apolice->segurado->seguradoPj?->razao_social;
                            
                        $dataFim = $apolice->data_fim ? $apolice->data_fim->format('d/m/Y') : '-';
                        
                        $html .= "<tr>
                                    <td>{$apolice->numero_apolice}</td>
                                    <td>{$cliente}</td>
                                    <td>{$apolice->user->name}</td>
                                    <td>{$dataFim}</td>
                                  </tr>";
                    }
                    
                    $html .= '</tbody></table>';

                    // Carrega o HTML na biblioteca
                    $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadHTML($html);
                    
                    // Retorna forçando o download imediato
                    return response()->streamDownload(
                        fn () => print($pdf->output()), 
                        'relatorio_operacional_' . now()->format('Ymd_His') . '.pdf'
                    );
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
