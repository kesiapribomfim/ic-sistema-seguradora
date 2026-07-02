<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CotacaoResource\Pages;
use App\Filament\Resources\CotacaoResource\RelationManagers;
use App\Models\Cotacao;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Select;
use Illuminate\Support\HtmlString;


class CotacaoResource extends Resource
{
    protected static ?string $model = Cotacao::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Cotações';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Wizard::make([
                    Wizard\Step::make('Dados Iniciais')
                        ->schema([
                            Select::make('segurado_id')
                                ->label('Selecione o Cliente')
                                ->relationship('segurado', 'id') 
                                ->getOptionLabelFromRecordUsing(function ($record) {
                                    return $record->tipo === 'PF'
                                        ? "{$record->seguradoPf?->nome} (CPF: {$record->seguradoPf?->cpf})"
                                        : "{$record->seguradoPj?->razao_social} (CNPJ: {$record->seguradoPj?->cnpj})";
                                })
                                ->searchable() 
                                ->preload()   
                                ->required()
                                ->helperText(function () {
                                    $url = \App\Filament\Resources\SeguradoResource::getUrl('create');
                                    return new HtmlString('Cadastrar novo cliente <a href="' . $url . '" target="_blank" class="text-primary-600 underline hover:text-primary-500">aqui</a>.');
                                }),
                            Select::make('produto_id')
                                ->label('Selecione o Produto')
                                ->relationship(
                                    name: 'produto',
                                    titleAttribute: 'nome',
                                    modifyQueryUsing: fn (Builder $query) => $query->where('status', true)
                                )
                                ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->nome} ({$record->ramo})")
                                ->searchable(['nome', 'codigo'])
                                ->preload()
                                ->required(),
                        ]),
                    Wizard\Step::make('Coberturas')
                        ->schema([
                            // ...
                        ]),
                    Wizard\Step::make('Billing')
                        ->schema([
                            // ...
                        ]),
                ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('Cotação')
                    ->weight(\Filament\Support\Enums\FontWeight::Bold)
                    ->description(fn ($record) => $record->produto->nome) 
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Corretor Responsável'),
                //mexer nisso aqui
                Tables\Columns\TextColumn::make('identificação_segurado')
                    ->label('Cliente')
                    ->state(fn (Cotacao $record) =>
                        $record->segurado->tipo === 'PF' ? $record->segurado->seguradoPf->nome 
                        : $record->segurado->seguradoPj->razao_social),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'info' => 'Em Elaboração',
                        'success' => 'Aceita',
                        'danger' => 'Recusada',
                        'warning' => 'Enviado ao Cliente',
                        'primary' => 'Expirada',
                    ]),
            ])
            ->filters([
                //
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
            'index' => Pages\ListCotacaos::route('/'),
            'create' => Pages\CreateCotacao::route('/create'),
            'edit' => Pages\EditCotacao::route('/{record}/edit'),
        ];
    }
}
