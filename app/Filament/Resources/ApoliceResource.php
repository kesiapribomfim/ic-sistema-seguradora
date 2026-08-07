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
                Forms\Components\Section::make('Dados Técnicos (Snapshot)')
                    ->icon('heroicon-o-cpu-chip')
                    ->schema([
                        Forms\Components\KeyValue::make('snapshot')
                            ->label('Snapshot do Produto')
                            ->keyLabel('Atributo')
                            ->valueLabel('Valor preservado'),
                            
                        Forms\Components\KeyValue::make('dados_bem_assegurado')
                            ->label('Dados do Bem')
                            ->keyLabel('Campo')
                            ->valueLabel('Informação'),
                            
                        Forms\Components\KeyValue::make('beneficiarios')
                            ->label('Beneficiários')
                            ->keyLabel('Nome/CPF')
                            ->valueLabel('Percentual (%)'),
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
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Corretor/Emissor')
                    ->sortable(),

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
            //
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
