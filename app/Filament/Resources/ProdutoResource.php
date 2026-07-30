<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProdutoResource\Pages;
use App\Filament\Resources\ProdutoResource\RelationManagers;
use App\Models\Produto;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;

class ProdutoResource extends Resource
{
    protected static ?string $model = Produto::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informações Básicas')
                    ->columns(2) 
                    ->schema([
                                              

                        Forms\Components\TextInput::make('nome')
                            ->required()
                            ->maxLength(255),
                            
                        Forms\Components\TextInput::make('codigo')
                            ->label('Código do Produto')
                            ->required()
                            ->unique(ignoreRecord: true) 
                            ->maxLength(50),
                            
                        
                            
                        Forms\Components\TextInput::make('versao')
                            ->label('Versão')
                            ->default('1.0')
                            ->required(),

                        Forms\Components\Textarea::make('descricao')
                            ->label('Descrição')
                            ->columnSpanFull(),
                    ]),

                //campos especificos por ramo
                Forms\Components\Select::make('ramo')
                ->label('Ramo')
                ->options([
                    'Auto' => 'Seguro Auto',
                    'Vida' => 'Seguro de Vida',
                    'Residencial' => 'Seguro Residencial',
                ])
                ->required()
                ->live(),
                
                Forms\Components\Section::make('Parâmetros Financeiros (Base)')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('parametros_calculo.taxa_base')
                            ->label('Taxa Base (%)')
                            ->numeric()
                            ->required(),
                            
                        Forms\Components\TextInput::make('parametros_calculo.valor_franquia')
                            ->label('Valor Base da Franquia (R$)')
                            ->numeric(),
                    ]),

                Forms\Components\Section::make('Configurações')
                    ->schema([
                        Toggle::make('status')
                            ->label('Produto Ativo')
                            ->default(false) 
                            ->hiddenOn('create') 
                            ->helperText('O produto só deve ser ativado após o cadastro das coberturas.'),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nome')
                    ->searchable(),
                Tables\Columns\TextColumn::make('codigo')
                    ->searchable(),
                Tables\Columns\TextColumn::make('ramo')
                ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Vida' => 'danger',    // Vermelho
                        'Residencial' => 'info', // Azul
                        'Auto' => 'success', //Verde
                    }),
                Tables\Columns\IconColumn::make('status')
                    ->label('Ativo')
                    ->boolean(),
            ])
            ->recordUrl(null)
            ->recordAction(Tables\Actions\ViewAction::class)
            ->filters([
                Tables\Filters\SelectFilter::make('ramo')
                    ->options([
                        'Auto' => 'Auto',
                        'Residencial' => 'Residencial',
                        'Vida' => 'Vida',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        1 => 'Ativo',
                        0 => 'Inativo',
                    ])
            ])
            ->actions([
                ActionGroup::make([
                    EditAction::make(),
                    ViewAction::make(),
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
            RelationManagers\CoberturasRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProdutos::route('/'),
            'create' => Pages\CreateProduto::route('/create'),
            'view' => Pages\ViewProduto::route('/{record}/view'),
            'edit' => Pages\EditProduto::route('/{record}/edit'),
        ];
    }
}
