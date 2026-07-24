<?php

namespace App\Filament\Resources\ProdutoResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class CoberturasRelationManager extends RelationManager
{
    protected static string $relationship = 'coberturas';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('nome')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('nome')
            ->columns([
                Tables\Columns\TextColumn::make('nome'),
                Tables\Columns\TextColumn::make('limite_maximo'),
                Tables\Columns\IconColumn::make('obrigatoria')
                    ->label('Obrigatoriedade')
                    ->boolean(),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
                Tables\Actions\AttachAction::make()
                    ->recordSelectOptionsQuery(fn (Builder $query) => $query->where('ramo', $this->getOwnerRecord()->ramo))
                    
                    ->preloadRecordSelect()
                    
                    ->form(fn (Tables\Actions\AttachAction $action): array => [
                        $action->getRecordSelect(),
                        
                        Forms\Components\TextInput::make('limite_maximo')
                            ->label('Limite Máximo (R$)')
                            ->numeric()
                            ->required(),
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
