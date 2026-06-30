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

class ProdutoResource extends Resource
{
    protected static ?string $model = Produto::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

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
                        Forms\Components\TextInput::make('taxa_base')
                            ->label('Taxa Base (%)')
                            ->numeric()
                            ->required(),
                            
                        Forms\Components\TextInput::make('valor_franquia')
                            ->label('Valor Base da Franquia (R$)')
                            ->numeric(),
                    ]),

                Forms\Components\Section::make('Configurações')
                    ->schema([
                        Toggle::make('status')
                            ->label('Produto Ativo')
                            ->default(true),
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
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListProdutos::route('/'),
            'create' => Pages\CreateProduto::route('/create'),
            'edit' => Pages\EditProduto::route('/{record}/edit'),
        ];
    }
}
