<?php
namespace App\Filament\Resources;

use App\Filament\Resources\PagamentoResource\Pages;
use App\Models\Pagamento;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Builder;

class PagamentoResource extends Resource
{
    protected static ?string $model = Pagamento::class;
    protected static ?string $modelLabel = 'Pagamento';
    protected static ?string $pluralModelLabel = 'Pagamentos';
    protected static ?string $slug = 'pagamentos';
    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    public static function form(Form $form): Form
{
    return $form
        ->schema([
            Forms\Components\Group::make()->schema([
                Forms\Components\Section::make('Classificação da Movimentação')
                    ->icon('heroicon-o-arrows-right-left')
                    ->schema([
                        Forms\Components\Select::make('tipo_movimentacao')
                            ->label('Tipo de Movimentação')
                            ->options([
                                'Recebimento' => 'Recebimento (Prêmio/Parcela)',
                                'Pagamento Indenização' => 'Pagamento (Indenização de Sinistro)',
                            ])
                            ->required()
                            ->live() // Faz a tela reagir instantaneamente a essa escolha
                            ->afterStateUpdated(function (Forms\Set $set) {
                                $set('apolice_id', null);
                                $set('sinistro_id', null);
                                $set('num_parcela', null);
                            }),

                        Forms\Components\Select::make('apolice_id')
                            ->relationship(
                                name: 'apolice', 
                                titleAttribute: 'numero_apolice',
                                modifyQueryUsing: fn (\Illuminate\Database\Eloquent\Builder $query, Forms\Get $get) => 
                                    $get('tipo_movimentacao') === 'Recebimento' 
                                        ? $query->where('status', 'Vigente') 
                                        : $query
                            )
                            ->label('Apólice Vinculada')
                            ->searchable()
                            ->preload()
                            ->required()
                            // Em vez de esconder, bloqueamos o campo. Fica visível como leitura.
                            ->disabled(fn (Forms\Get $get) => $get('tipo_movimentacao') === 'Pagamento Indenização')
                            ->dehydrated(), // Garante o salvamento do campo bloqueado

                        Forms\Components\Select::make('sinistro_id')
                            ->relationship(
                                name:'sinistro', 
                                titleAttribute:'id',
                                modifyQueryUsing: fn (Builder $query) => $query
                                    ->where('status', 'Aprovado')) // Só mostra sinistros aprovados
                            ->label('Sinistro Vinculado')
                            ->searchable()
                            ->preload()
                            ->live()
                            // Só aparece e é obrigatório se for um pagamento de indenização
                            ->visible(fn (Forms\Get $get) => $get('tipo_movimentacao') === 'Pagamento Indenização')
                            ->required(fn (Forms\Get $get) => $get('tipo_movimentacao') === 'Pagamento Indenização')
                            ->afterStateUpdated(function (Forms\Set $set, $state) {
                                if ($state) {
                                    $sinistro = \App\Models\Sinistro::find($state);
                                    if ($sinistro) {
                                        $set('apolice_id', $sinistro->apolice_id);
                                    }
                                } else {
                                    $set('apolice_id', null);
                                }
                            }),                            
                    ])->columns(2),

                Forms\Components\Section::make('Valores e Datas')
                    ->icon('heroicon-o-currency-dollar')
                    ->schema([
                        Forms\Components\Grid::make(3)->schema([
                            Forms\Components\TextInput::make('valor')
                                ->label('Valor (R$)')
                                ->numeric()
                                ->prefix('R$')
                                ->required(),
                                
                            Forms\Components\TextInput::make('num_parcela')
                                ->label('Nº da Parcela')
                                ->numeric()
                                // Só aparece se for um recebimento de apólice
                                ->visible(fn (Forms\Get $get) => $get('tipo_movimentacao') === 'Recebimento'),
                                
                            Forms\Components\DatePicker::make('data_vencimento')
                                ->label('Data de Vencimento')
                                ->displayFormat('d/m/Y')
                                ->required(),
                        ]),
                    ]),
            ])->columnSpan(['lg' => 2]), // Ocupa 2/3 da tela lateralmente

            Forms\Components\Group::make()->schema([
                Forms\Components\Section::make('Situação')
                    ->icon('heroicon-o-check-circle')
                    ->schema([
                        Forms\Components\Select::make('status')
                            ->label('Status do Pagamento')
                            ->options([
                                'Aberta' => 'Aberta',
                                'Paga' => 'Paga',
                                'Vencida' => 'Vencida',
                                'Cancelada' => 'Cancelada',
                            ])
                            ->required()
                            ->live(),

                        // Campos que só aparecem se o status mudar para "Paga"
                        Forms\Components\DatePicker::make('data_pagamento')
                            ->label('Data do Pagamento Efetivo')
                            ->displayFormat('d/m/Y')
                            ->visible(fn (Forms\Get $get) => $get('status') === 'Paga')
                            ->required(fn (Forms\Get $get) => $get('status') === 'Paga'),

                        Forms\Components\Select::make('metodo_baixa')
                            ->label('Método de Baixa')
                            ->options([
                                'Manual' => 'Manual',
                                'Automática' => 'Automática',
                            ])
                            ->visible(fn (Forms\Get $get) => $get('status') === 'Paga')
                            ->required(fn (Forms\Get $get) => $get('status') === 'Paga'),
                    ]),
            ])->columnSpan(['lg' => 1]), // Ocupa 1/3 da tela
        ])->columns(3);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user->hasRole('super_admin')){
            return $query;
        }

        if ($user->hasRole('Corretor')) {
            return $query->whereHas('apolice', function ($q) use ($user) {
                $q->where('user_id', $user->id); 
            });
        }

        if ($user->hasRole('Cliente')) {
            return $query->whereHas('apolice.segurado', function ($q) use ($user) { 
                $q->where('user_id', $user->id);});
        }

        if ($user->hasAnyRole(['Gestor de Filial', 'Financeiro'])) {
            $filiaisIds = $user->filiais()->pluck('filiais.id');
            
            return $query->whereHas('apolice', function ($q) use ($filiaisIds) {
                $q->whereIn('filial_id', $filiaisIds);
            });
        }
        
        return $query;
    }
    
    
    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Data de Criação')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('tipo_movimentacao')
                    ->label('Tipo')
                    ->badge()
                    ->color(fn (string $state): string => $state === 'Recebimento' ? 'success' : 'danger'),

                Tables\Columns\TextColumn::make('apolice.numero_apolice')
                    ->label('Apólice')
                    ->searchable()
                    ->color('info')
                    ->url(fn (Model $record) => $record->apolice_id ? \App\Filament\Resources\ApoliceResource::getUrl('view', ['record' => $record->apolice_id]) : null)
                    ->openUrlInNewTab(),

                Tables\Columns\TextColumn::make('sinistro_id')
                    ->label('Sinistro')
                    ->placeholder('-')
                    ->color('info')
                    ->url(fn (Model $record) => $record->sinistro_id ? \App\Filament\Resources\SinistroResource::getUrl('view', ['record' => $record->sinistro_id]) : null)
                    ->openUrlInNewTab(),

                Tables\Columns\TextColumn::make('num_parcela')
                    ->label('Parcela')
                    ->placeholder('-'),

                Tables\Columns\TextColumn::make('valor')
                    ->label('Valor')
                    ->money('BRL')
                    ->sortable(),

                Tables\Columns\TextColumn::make('data_vencimento')
                    ->label('Vencimento')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Aberta' => 'warning',
                        'Paga' => 'success',
                        'Vencida' => 'danger',
                        'Cancelada' => 'gray',
                        default => 'gray',
                    }),
            ])
            ->recordUrl(null)
            ->recordAction(Tables\Actions\ViewAction::class)
            ->filters([
                // Aqui você pode adicionar filtros por data no futuro
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\ViewAction::make(),
                    
                    // Botão mockado para baixar a fatura (se for um recebimento)
                    Tables\Actions\Action::make('baixar_fatura')
                        ->label('Fatura (PDF)')
                        ->icon('heroicon-o-document-arrow-down')
                        ->color('danger')
                        ->visible(fn (Model $record) => $record->tipo_movimentacao === 'Recebimento')
                        ->action(function () {
                            \Filament\Notifications\Notification::make()
                                ->title('Fatura Indisponível')
                                ->body('A geração de faturas está em desenvolvimento.')
                                ->info()
                                ->send();
                        }),
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
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPagamentos::route('/'),
            'create' => Pages\CreatePagamento::route('/create'),
            'view' => Pages\ViewPagamento::route('/{record}'),
            'edit' => Pages\EditPagamento::route('/{record}/edit'),
        ];
    }
}