<?php

namespace App\Filament\Resources\ApoliceResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

class BeneficiariosRelationManager extends RelationManager
{
    protected static string $relationship = 'beneficiarios';

    protected static ?string $recordTitleAttribute = 'nome';

    protected static ?string $title = 'Beneficiários';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                // 1. Dados do Cadastro Central (Tabela beneficiarios)
                Forms\Components\TextInput::make('nome')
                    ->required()
                    ->maxLength(255),
                Forms\Components\TextInput::make('cpf')
                    ->label('CPF')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->maxLength(14),
                Forms\Components\DatePicker::make('data_nascimento')
                    ->label('Data de Nascimento'),
                    
                // 2. Dados do Vínculo com a Apólice (Tabela Pivot)
                // O Filament mapeia esses campos automaticamente por causa do withPivot() na Model
                Forms\Components\TextInput::make('percentual_rateio')
                    ->label('Rateio (%)')
                    ->numeric()
                    ->required()
                    ->maxValue(100),
                Forms\Components\TextInput::make('parentesco')
                    ->label('Parentesco')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nome')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('cpf')
                    ->searchable(),
                Tables\Columns\TextColumn::make('parentesco')
                    ->label('Parentesco'),
                Tables\Columns\TextColumn::make('percentual_rateio')
                    ->label('Rateio')
                    ->suffix('%')
                    ->badge()
                    ->color('info'),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                // Ação 1: Cria um beneficiário do zero e já vincula
                Tables\Actions\CreateAction::make()
                    ->label('Novo Beneficiário')
                    ->icon('heroicon-o-plus'),
                    
                // Ação 2: Busca um CPF/Nome que já existe na base para vincular
                Tables\Actions\AttachAction::make()
                    ->label('Vincular Existente')
                    ->icon('heroicon-o-link')
                    ->preloadRecordSelect()
                    ->form(fn (Tables\Actions\AttachAction $action): array => [
                        $action->getRecordSelect(), // O select automático de busca
                        
                        // Pedimos apenas os dados do vínculo (Pivot) na hora de anexar
                        Forms\Components\TextInput::make('percentual_rateio')
                            ->label('Rateio (%)')
                            ->numeric()
                            ->required()
                            ->maxValue(100),
                        Forms\Components\TextInput::make('parentesco')
                            ->label('Parentesco')
                            ->required(),
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
                // DetachAction apenas desvincula da apólice, sem apagar o cadastro central da pessoa
                Tables\Actions\DetachAction::make()
                    ->label('Desvincular'), 
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make(),
                ]),
            ]);
    }
}