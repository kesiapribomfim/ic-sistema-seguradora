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


            // TODO: melhorar visualização dos dados na hora da edição (problema da infolist com dados de todos os ramos e coberturas não aparecendo)
            Forms\Components\Group::make()->schema([
                Forms\Components\Section::make('Vinculos')
                    ->icon('heroicon-o-users')
                    ->schema([
                        Forms\Components\Placeholder::make('segurado_id')
                            ->label('Segurado')
                            ->content(function ($record) {
                                if (!$record || !$record->segurado) return '-';
                                
                                $nome = $record->segurado->tipo === 'PF' 
                                    ? "{$record->segurado->seguradoPf?->nome}" 
                                    : "{$record->segurado->seguradoPj?->razao_social}";
                                    
                                $url = \App\Filament\Resources\SeguradoResource::getUrl('view', ['record' => $record->segurado_id]);
                                
                                return new HtmlString("<a href='{$url}' target='_blank' style='color: #f59e0b;'>{$nome}</a>");
                            }),
                            
                        Forms\Components\Placeholder::make('user_id')
                            ->label('Corretor')
                            ->content(function ($record) {
                                
                                if(!$record || !$record->user) return '-';

                                $name = $record->user?->name;
                                $url = \App\Filament\Resources\UserResource::getUrl('view', ['record' => $record->user_id]);

                                return new HtmlString(("<a href='{$url}' target='_blank' style='color: #f59e0b;'>{$name}</a>"));

                            }),
                            
                        Forms\Components\Placeholder::make('filial_id')
                            ->label('Filial')
                            ->content(function ($record){
                                if (!$record || !$record->filial) return '-';

                                $nome = $record->filial?->nome;
                                $url = \App\Filament\Resources\FilialResource::getUrl('view', ['record' => $record->filial_id]);

                                return new HtmlString(("<a href= '{$url}' target='_blank' style='color: #f59e0b;'>{$nome}</a>"));
                            }),
                            
                        Forms\Components\Placeholder::make('cotacao_id')
                            ->label('Cotação')
                            ->content(function ($record){
                                if (!$record || !$record->cotacao_id) return '-';

                                $nome = 'Cotação #' . $record->cotacao_id;
                                $url = \App\Filament\Resources\CotacaoResource::getUrl('view', ['record'=> $record->cotacao_id]);

                                return new HtmlString("<a href='{$url}' target='_blank' style='color: #f59e0b;'>{$nome}</a>");
                            }),

                        Forms\Components\Placeholder::make ('apolice_origem_id')
                            ->label('Apólice de Origem')
                            ->content (function ($record){
                                if (!$record || !$record->apolice_origem_id) return '-';

                                $nome = 'Apolice #' . $record->apolice_origem_id;
                                $url = \App\Filament\Resources\ApoliceResource::getUrl('view', ['record'=> $record->apolice_origem_id]);

                                return new HtmlString(("<a href= '{$url}' target='_blank' style='color: #f59e0b;'>{$nome}</a>"));
                            })
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
        $query = parent::getEloquentQuery();

        $user = auth()->user();

        if ($user->hasRole('Corretor') && ! $user->hasRole('super_admin')) {
            $query->where('user_id', $user->id);
        }

        return $query;
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

                    Tables\Actions\Action::make('gerar_pdf')
                        ->label('Gerar PDF')
                        ->icon('heroicon-o-document-check')
                        ->color('danger'),
                        //->action(),

                    // O botão inverso: da Apólice para a Cotação
                    Tables\Actions\Action::make('ver_cotacao')
                        ->label('Ver Cotação')
                        ->icon('heroicon-o-calculator')
                        ->color('info')
                        ->visible(fn (Model $record) => $record->cotacao_id !== null)
                        ->url(fn (Model $record) => \App\Filament\Resources\CotacaoResource::getUrl('view', ['record' => $record->cotacao_id]))
                        ->openUrlInNewTab(), // Abre em nova aba para não perder a tela da apólice
                ])
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
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
