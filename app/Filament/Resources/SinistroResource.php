<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SinistroResource\Pages;
use App\Filament\Resources\SinistroResource\RelationManagers;
use App\Models\Sinistro;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;

class SinistroResource extends Resource
{
    protected static ?string $model = Sinistro::class;

    protected static ?string $navigationIcon = 'heroicon-o-exclamation-triangle';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                //
            ])
            ->recordUrl(null)
            ->recordAction(Tables\Actions\ViewAction::class)
            ->filters([
                //
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
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSinistros::route('/'),
            'create' => Pages\CreateSinistro::route('/create'),
            'view' => Pages\ViewSinistro::route('/{record}/view'),
            'edit' => Pages\EditSinistro::route('/{record}/edit'),
        ];
    }
}
