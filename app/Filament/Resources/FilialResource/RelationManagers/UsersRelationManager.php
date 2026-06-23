<?php

namespace App\Filament\Resources\FilialResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class UsersRelationManager extends RelationManager
{
    protected static string $relationship = 'users';

    public function form(Form $form): Form
    {
        return $form
            ->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('name')
            ->columns([
                Tables\Columns\TextColumn::make('name'),
                Tables\Columns\TextColumn::make('perfil_acesso')
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->preloadRecordSelect()
                    ->form(fn (Tables\Actions\AttachAction $action): array => [
                        $action->getRecordSelect(), // O campo obrigatório que escolhe "quem" é a pessoa
                        
                        // O campo extra para o "Livro da Recepção"
                        Forms\Components\Select::make('perfil_acesso')
                            ->label('Perfil na Filial')
                            ->options([
                                'Gestor de Filial' => 'Gestor de Filial',
                                'Subscritor' => 'Subscritor',
                                'Corretor' => 'Corretor',
                                'Analista de Sinistros' => 'Analista de Sinistros',
                                'Financeiro' => 'Financeiro',
                            ])
                            ->required(),
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                Tables\Actions\DetachAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make(),
                ]),
            ]);
    }
}
