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

class CotacaoResource extends Resource
{
    protected static ?string $model = Cotacao::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('ramo')
                    ->label('ramo')
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
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
