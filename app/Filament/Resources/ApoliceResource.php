<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ApoliceResource\Pages;
use App\Filament\Resources\ApoliceResource\RelationManagers;
use App\Models\Apolice;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\Eloquent\Model;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Illuminate\Support\HtmlString;
use Filament\Infolists;
use Filament\Infolists\Infolist;
use Barryvdh\DomPDF\Facade\Pdf;
use pxlrbt\FilamentExcel\Actions\Tables\ExportBulkAction;
use pxlrbt\FilamentExcel\Exports\ExcelExport;
use Illuminate\Support\Facades\Auth;

// TODO: Ajeitar os nomes de Apolices na interface visual e os icons (cotação, produto e apolice)

class ApoliceResource extends Resource
{
    protected static ?string $model = Apolice::class;
    protected static ?string $modelLabel = 'Apólices';
    protected static ?string $pluralModelLabel = 'Apólices';
    protected static ?string $navigationIcon = 'heroicon-o-document-check';


public static function form(Form $form): Form
{
    return $form
        ->schema([
            Forms\Components\Group::make()->schema([
                Forms\Components\Section::make('Dados da Apólice')
                    ->description('Informações contratuais e vigência')
                    ->icon('heroicon-o-document-check')
                    ->schema([
                        Forms\Components\Placeholder::make('numero_apolice')
                            ->label('Número da Apólice')
                            ->content(fn ($record) => new HtmlString("<span style='font-family: monospace; font-weight: bold; font-size: 1.1em;'>{$record?->numero_apolice}</span>")),
                            
                        Forms\Components\Placeholder::make('status_visual')
                            ->label('Status da Apólice')
                            ->content(function ($record) {
                                $status = $record ? $record->status : 'Em Elaboração';
                                
                                $cor = match($status) {
                                    'Vigente' => '#3b82f6',      
                                    'Cancelada' => '#f59e0b', 
                                    'Renovada' => '#10b981', 
                                    'Suspensa por inadimplência' => '#ef4444',
                                    'Expirada' => '#6b7280',
                                    'Substituída' => 'gray',
                                    default => '#6b7280',              
                                };
                                
                                return new HtmlString(
                                    "<span style='color: {$cor}; font-weight: bold;'>{$status}</span>"
                                );
                            }),

                        Forms\Components\Grid::make(3)->schema([
                            Forms\Components\Placeholder::make('data_emissao')
                                ->label('Emissão')
                                ->content(fn ($record) => $record?->data_emissao ? $record->data_emissao->format('d/m/Y') : '-'),
                                
                            Forms\Components\Placeholder::make('data_inicio')
                                ->label('Início da Vigência')
                                ->content(fn ($record) => $record?->data_inicio ? $record->data_inicio->format('d/m/Y') : '-'),
                                
                            Forms\Components\Placeholder::make('data_fim')
                                ->label('Fim da Vigência')
                                ->content(fn ($record) => $record?->data_fim ? $record->data_fim->format('d/m/Y') : '-'),
                        ]),
                    ]),

                Forms\Components\Section::make('Detalhes Financeiros')
                    ->icon('heroicon-o-currency-dollar')
                    ->columns(4)
                    ->schema([
                        Forms\Components\Placeholder::make('valor_total')
                            ->label('Prêmio Total')
                            ->content(fn ($record) => $record?->valor_total ? 'R$ ' . number_format($record->valor_total, 2, ',', '.') : '-'),
                            
                        Forms\Components\Placeholder::make('forma_pagamento')
                            ->label('Forma de Pagto')
                            ->content(fn ($record) => $record?->forma_pagamento ?? '-'),
                            
                        Forms\Components\Placeholder::make('quantidade_parcelas')
                            ->label('Qtd. Parcelas')
                            ->content(fn ($record) => $record?->quantidade_parcelas ?? '-'),
                            
                        Forms\Components\Placeholder::make('valor_parcela')
                            ->label('Valor da Parcela')
                            ->content(fn ($record) => $record?->valor_parcela ? 'R$ ' . number_format($record->valor_parcela, 2, ',', '.') : '-'),
                    ]),
            ])->columnSpan(['lg' => 2]),


            Forms\Components\Group::make()
                ->schema([
                    Forms\Components\Section::make('Vínculos')
                        ->icon('heroicon-o-users')
                        ->schema([
                            Forms\Components\Placeholder::make('segurado_id')
                                ->label('Segurado')
                                ->content(function ($record) {
                                    if (!$record || !$record->segurado) return '-';

                                    return $record->segurado->tipo === 'PF'
                                        ? $record->segurado->seguradoPf?->nome
                                        : $record->segurado->seguradoPj?->razao_social;
                                }),

                            Forms\Components\Placeholder::make('user_id')
                                ->label('Corretor')
                                ->content(fn($record) => $record?->user?->name ?? '-'),

                            Forms\Components\Placeholder::make('filial_id')
                                ->label('Filial')
                                ->content(fn($record) => $record?->filial?->nome ?? '-'),

                            Forms\Components\Placeholder::make('cotacao_id')
                                ->label('Cotação')
                                ->content(fn($record) => $record?->cotacao_id ? "Cotação #{$record->cotacao_id}" : '-'),

                            Forms\Components\Placeholder::make('apolice_origem_id')
                                ->label('Apólice de Origem')
                                ->content(fn($record) => $record?->apolice_origem_id ? "Apólice #{$record->apolice_origem_id}" : '-')
                        ]),
                ])->columnSpan(['lg' => 1]),

                Forms\Components\Group::make()->schema([
                Forms\Components\Section::make('Snapshot')
                    ->icon('heroicon-o-cpu-chip')
                    ->schema([
                        Forms\Components\KeyValue::make('snapshot')
                            ->label('Snapshot do Produto')
                            ->keyLabel('Atributo')
                            ->valueLabel('Valor preservado')
                            ->disabled(),
                            
                        Forms\Components\KeyValue::make('dados_bem_assegurado')
                            ->label('Dados do Bem')
                            ->keyLabel('Campo')
                            ->valueLabel('Informação')
                            ->disabled(),
                    ]),
            ])->columnSpan(['lg' => 3]),
        ])
        ->columns(3);
}

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist
            ->schema([
                Infolists\Components\Group::make()->schema([
                    Infolists\Components\Section::make('Dados da Apólice')
                        ->description('Informações contratuais e vigência')
                        ->icon('heroicon-o-document-check')
                        ->schema([
                            Infolists\Components\TextEntry::make('numero_apolice')
                                ->label('Número da Apólice')
                                ->fontFamily('mono')
                                ->weight('bold')
                                ->size(Infolists\Components\TextEntry\TextEntrySize::Large),
                            
                            Infolists\Components\TextEntry::make('status')
                                ->label('Status da Apólice')
                                ->badge()
                                ->color(fn (string $state): string => match ($state) {
                                    'Vigente' => 'info',      
                                    'Cancelada' => 'warning', 
                                    'Renovada' => 'success', 
                                    'Suspensa por inadimplência' => 'danger',
                                    'Expirada' => 'gray',
                                    'Substituída' => 'gray',
                                    default => 'gray',              
                                }),

                            Infolists\Components\Grid::make(3)->schema([
                                Infolists\Components\TextEntry::make('data_emissao')
                                    ->label('Emissão')
                                    ->date('d/m/Y')
                                    ->default('-'),
                                    
                                Infolists\Components\TextEntry::make('data_inicio')
                                    ->label('Início da Vigência')
                                    ->date('d/m/Y')
                                    ->default('-'),
                                    
                                Infolists\Components\TextEntry::make('data_fim')
                                    ->label('Fim da Vigência')
                                    ->date('d/m/Y')
                                    ->default('-'),
                            ]),
                        ]),

                    Infolists\Components\Section::make('Detalhes Financeiros')
                        ->icon('heroicon-o-currency-dollar')
                        ->columns(4)
                        ->schema([
                            Infolists\Components\TextEntry::make('valor_total')
                                ->label('Prêmio Total')
                                ->money('BRL')
                                ->default('-'),
                                
                            Infolists\Components\TextEntry::make('forma_pagamento')
                                ->label('Forma de Pagto')
                                ->default('-'),
                                
                            Infolists\Components\TextEntry::make('quantidade_parcelas')
                                ->label('Qtd. Parcelas')
                                ->default('-'),
                                
                            Infolists\Components\TextEntry::make('valor_parcela')
                                ->label('Valor da Parcela')
                                ->money('BRL')
                                ->default('-'),
                        ]),
                ])->columnSpan(['lg' => 2]),

                Infolists\Components\Group::make()->schema([
                    Infolists\Components\Section::make('Vínculos')
                        ->icon('heroicon-o-users')
                        ->visible(fn() => auth()->user()->hasAnyRole(['Administrador Geral', 'Gestor de Filial', 'super_admin']))
                        ->schema([
                            Infolists\Components\TextEntry::make('identificacao_segurado')
                                ->label('Segurado')
                                ->state(fn (Model $record) => $record->segurado?->tipo === 'PF' 
                                    ? $record->segurado?->seguradoPf?->nome 
                                    : $record->segurado?->seguradoPj?->razao_social)
                                ->url(fn ($record) => $record->segurado_id ? \App\Filament\Resources\SeguradoResource::getUrl('view', ['record' => $record->segurado_id]) : null)
                                ->color('warning'),
                                
                            Infolists\Components\TextEntry::make('user.name')
                                ->label('Corretor')
                                ->url(fn ($record) => $record->user_id ? \App\Filament\Resources\UserResource::getUrl('view', ['record' => $record->user_id]) : null)
                                ->color('warning'),
                                
                            Infolists\Components\TextEntry::make('filial.nome')
                                ->label('Filial')
                                ->url(fn ($record) => $record->filial_id ? \App\Filament\Resources\FilialResource::getUrl('view', ['record' => $record->filial_id]) : null)
                                ->color('warning'),
                                
                            Infolists\Components\TextEntry::make('cotacao_id')
                                ->label('Cotação')
                                ->formatStateUsing(fn ($state) => "Cotação #{$state}")
                                ->url(fn ($record) => $record->cotacao_id ? \App\Filament\Resources\CotacaoResource::getUrl('view', ['record' => $record->cotacao_id]) : null)
                                ->color('warning'),

                            Infolists\Components\TextEntry::make('apolice_origem_id')
                                ->label('Apólice de Origem')
                                ->formatStateUsing(fn ($state) => "Apólice #{$state}")
                                ->url(fn ($record) => $record->apolice_origem_id ? \App\Filament\Resources\ApoliceResource::getUrl('view', ['record' => $record->apolice_origem_id]) : null)
                                ->color('warning')
                                ->visible(fn ($record) => $record->apolice_origem_id !== null),
                        ]),
                ])->columnSpan(['lg' => 1]),

                Infolists\Components\Group::make()->schema([
                    Infolists\Components\Section::make('Snapshot e Dados do Bem')
                        ->icon('heroicon-o-cpu-chip')
                        ->schema([
                            Infolists\Components\TextEntry::make('snapshot.produto.nome')
                                ->label('Produto Contratado')
                                ->weight('bold')
                                ->size(Infolists\Components\TextEntry\TextEntrySize::Large),

                            Infolists\Components\RepeatableEntry::make('snapshot.coberturas')
                                ->label('Coberturas Contratadas (Snapshot)')
                                ->schema([
                                    Infolists\Components\TextEntry::make('nome_cobertura')->label('Cobertura'),
                                    Infolists\Components\TextEntry::make('limite_maximo')->label('Limite Máximo')->money('BRL'),
                                ])
                                ->columns(2),
                            Infolists\Components\RepeatableEntry::make('dados_bem_assegurado.dependentes_vida')
                                ->label('Dependentes (Seguro de Vida)')
                                ->schema([
                                    Infolists\Components\TextEntry::make('nome')->label('Nome')->weight('bold'),
                                    Infolists\Components\TextEntry::make('parentesco')->label('Parentesco'),
                                    Infolists\Components\TextEntry::make('data_nascimento')->label('Nascimento')->date('d/m/Y'),
                                    Infolists\Components\TextEntry::make('peso')->label('Peso (kg)')->suffix(' kg'),
                                    Infolists\Components\TextEntry::make('altura')->label('Altura (cm)')->suffix(' cm'),
                                ])
                                ->columns(5)
                                ->visible(fn ($record) => !empty($record->dados_bem_assegurado['dependentes_vida'])),

                            Infolists\Components\KeyValueEntry::make('dados_bem_assegurado')
                                ->label('Dados Específicos do Risco')
                                ->keyLabel('Campo')
                                ->valueLabel('Informação')
                                ->state(function ($record) {
                                    if (!$record->dados_bem_assegurado) return [];
                                    
                                    // 1. Identificamos qual é o ramo do produto desta apólice
                                    $ramo = $record->cotacao?->produto?->ramo ?? $record->snapshot['produto']['ramo'] ?? null;
                                    
                                    return collect($record->dados_bem_assegurado)
                                        ->reject(fn ($value, $key) => 
                                            is_null($value) || 
                                            $value === '' || 
                                            $value === [] || 
                                            in_array($key, ['dependentes_vida', 'beneficiarios_vida'])
                                        )
                                        // 2. O FILTRO DE CONTEXTO: Só exibe o que pertence ao ramo da apólice
                                        ->filter(function ($value, $key) use ($ramo) {
                                            if ($ramo === 'Vida') {
                                                return in_array($key, ['peso', 'altura', 'profissao_risco', 'fumante', 'consome_alcool', 'pratica_esportes_radicais', 'possui_doenca_preexistente', 'doencas_diagnosticadas', 'detalhes_saude', 'valor_base_risco']);
                                            }
                                            if ($ramo === 'Auto') {
                                                return in_array($key, ['ano', 'tipo_veiculo', 'kit_gas', 'blindado', 'zero', 'uso', 'estacionamento', 'uso_anterior', 'seguro_antigo', 'classe_bonus', 'valor_base_risco', 'placa', 'chassi', 'modelo', 'marca', 'cep', 'rua', 'numero', 'bairro', 'cidade', 'uf', 'sem_placa']);
                                            }
                                            if ($ramo === 'Residencial') {
                                                return in_array($key, ['tipo_moradia', 'detalhe_apartamento', 'uso_residencia', 'tipo_construcao', 'regiao', 'agro_comercial', 'sobre_imovel', 'terreno_baldio', 'sinistros', 'valor_base_risco', 'cep', 'rua', 'numero', 'bairro', 'cidade', 'uf', 'complemento']);
                                            }
                                            return true; // Fallback: se não identificar o ramo, deixa passar tudo
                                        })
                                        // 3. O TRADUTOR
                                        ->map(function ($value) {
                                            if (is_array($value)) {
                                                $isListaSimples = array_is_list($value) && empty(array_filter($value, 'is_array'));
                                                return $isListaSimples ? implode(', ', $value) : json_encode($value, JSON_UNESCAPED_UNICODE);
                                            }
                                            if (is_bool($value)) {
                                                return $value ? 'Sim' : 'Não';
                                            }
                                            return (string) $value;
                                        })
                                        ->toArray();
                                }),
                        ]),
                ])->columnSpan(['lg' => 3]),
            ])
            ->columns(3);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery()->with([
            'segurado.seguradoPf',
            'segurado.seguradoPj',
            'user',
            'filial',
            'cotacao.produto'
        ]);

        /** @var \App\Models\User $user */
        $user = Auth::user();

        if ($user->hasRole('super_admin')) {
            return $query;
        }

        if ($user->hasRole('Corretor')) {
            return $query->where('user_id', $user->id);
        }

        if ($user->hasRole('Cliente')) {
            return $query->whereHas('segurado', function ($q) use ($user) {
                $q->where('user_id', $user->id);
            });
        }

        $filiaisIds = $user->filiais()->pluck('filiais.id');

        return $query->whereIn('filial_id', $filiaisIds);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('numero_apolice')
                    ->label('Nº da Apólice')
                    ->searchable()
                    ->sortable()
                    ->fontFamily('mono')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('identificacao_segurado')
                    ->label('Segurado')
                    ->state(fn (Model $record) => $record->segurado?->tipo === 'PF' 
                        ? $record->segurado?->seguradoPf?->nome 
                        : $record->segurado?->seguradoPj?->razao_social)
                    ->sortable()
                    ->weight(\Filament\Support\Enums\FontWeight::Bold)
                    //->sortable() (em ordem alfabetica)
                    ->description(fn ($record) => "Corretor: {$record->user->name}"),
                    
                    //Talvez voltar com a coluna user para facilitar a filtragem por corretor

                Tables\Columns\TextColumn::make('data_fim')
                    ->label('Fim da Vigência')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('valor_total')
                    ->label('Prêmio Total')
                    ->money('BRL') // Formata automaticamente para R$ 0.000,00
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Vigente' => 'info',      
                        'Cancelada' => 'warning', 
                        'Renovada' => 'success', 
                        'Suspensa por inadimplência' => 'danger',
                        'Expirada' => 'gray',
                        'Substituída' => 'gray',
                    }),
            ])
            ->recordUrl(null) // Desativa o clique na linha inteira
            ->recordAction(Tables\Actions\ViewAction::class) // Transforma o clique no gatilho da View
            ->filters([
                // TODO: Filters (Ex: Filtrar por Status, Data de Vencimento)
            ])
            ->actions([
                ActionGroup::make([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\ViewAction::make(),

                    Tables\Actions\Action::make('baixar_pdf')
                        ->label('Baixar PDF')
                        ->icon('heroicon-o-document-arrow-down')
                        ->color('danger')
                        ->visible(fn ($record) => $record->status !== 'Cancelada')
                        ->action(function ($record) {
                            
                            $record->load([
                                'segurado.seguradoPf', 
                                'segurado.seguradoPj', 
                                'cotacao.produto', 
                                'pagamentos'
                            ]);

                            $pdf = Pdf::loadView('pdf.apolice', ['apolice' => $record]);
                            
                            return response()->streamDownload(
                                fn () => print($pdf->output()), 
                                "apolice-{$record->numero_apolice}.pdf"
                            );
                        }),

                    Tables\Actions\Action::make('ver_cotacao')
                        ->label('Ver Cotação')
                        ->icon('heroicon-o-calculator')
                        ->color('info')
                        ->visible(fn (Model $record) => $record->cotacao_id !== null)
                        ->url(fn (Model $record) => \App\Filament\Resources\CotacaoResource::getUrl('view', ['record' => $record->cotacao_id]))
                        ->openUrlInNewTab(), // Abre em nova aba para não perder a tela da apólice
                    Tables\Actions\Action::make('gerar_endosso')
                        ->label('Gerar Endosso')
                        ->icon('heroicon-o-document-duplicate')
                        ->color('warning')
                        ->visible(
                            fn(Model $record) =>
                            $record->status === 'Vigente' &&
                                auth()->user()->hasAnyRole(['Corretor', 'Gestor de Filial', 'Administrador Geral', 'super_admin'])
                        )
                        ->form([
                            Forms\Components\Textarea::make('motivo_endosso')
                                ->label('Motivo / Alteração Solicitada')
                                ->placeholder('Ex: Mudança de endereço para a Rua X; Inclusão de cobertura de vidros...')
                                ->required()
                                ->rows(3),

                            Forms\Components\TextInput::make('novo_valor_total')
                                ->label('Novo Valor Total da Apólice (Após endosso)')
                                ->helperText('Deixe em branco se a alteração não gerar cobrança extra.')
                                ->numeric()
                                ->prefix('R$'),
                        ])
                        ->modalHeading('Processar Endosso de Apólice')
                        ->modalDescription('Informe os dados da alteração. Uma nova versão da apólice será gerada e a atual será arquivada no histórico.')
                        ->action(function (array $data, Model $record) {

                            $novaApolice = $record->replicate();

                            $novaApolice->numero_apolice = $record->numero_apolice . '-END';
                            $novaApolice->apolice_origem_id = $record->id;
                            $novaApolice->data_emissao = now();

                            if (!empty($data['novo_valor_total'])) {
                                $novaApolice->valor_total = $data['novo_valor_total'];
                            }

                            $snapshotAtualizado = $novaApolice->snapshot;
                            $snapshotAtualizado['historico_endosso'] = [
                                'data' => now()->format('d/m/Y H:i'),
                                'autor' => auth()->user()->name,
                                'motivo' => $data['motivo_endosso']
                            ];
                            $novaApolice->snapshot = $snapshotAtualizado;

                            $novaApolice->save();

                            $record->update([
                                'status' => 'Substituída'
                            ]);

                            \Filament\Notifications\Notification::make()
                                ->title('Endosso Gerado com Sucesso')
                                ->success()
                                ->send();

                            return redirect(\App\Filament\Resources\ApoliceResource::getUrl('view', ['record' => $novaApolice->id]));
                        }),
                    ])
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                    ExportBulkAction::make()
                        ->label('Exportar Planilha')
                        ->exports([
                            ExcelExport::make()
                                ->fromTable()
                                ->withFilename('relatorio_apolices_' . date('Y-m-d'))
                                ->queue(), // Manda para a fila (Job) em vez de travar o navegador
                        ]),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [
            RelationManagers\BeneficiariosRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListApolices::route('/'),
            'view' => Pages\ViewApolice::route('/{record}/view'),
            'edit' => Pages\EditApolice::route('/{record}/edit'),
        ];
    }
}
