<?php

namespace App\Filament\Resources\SeguradoResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\Cotacao;

class CotacoesRelationManager extends RelationManager
{
    protected static string $relationship = 'cotacoes';
    protected static ?string $title = 'Cotações';
    protected static ?string $icon = 'heroicon-o-calculator';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('id')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('id')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                ->label('Data de Criação')
                ->date('d/m/Y')
                ->sortable(),
                Tables\Columns\TextColumn::make('identificacao_segurado')
                    ->label('Cliente')
                    ->state(fn (Cotacao $record) => $record->segurado?->tipo === 'PF' ? $record->segurado?->seguradoPf?->nome : $record->segurado?->seguradoPj?->razao_social)
                    ->sortable(),
                    // ->searchable(),
                Tables\Columns\TextColumn::make('user.name')->label('Corretor Responsável')
                    ->sortable(),
                    // ->searchable(),
                Tables\Columns\TextColumn::make('produto.nome')->label('Produto')->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Em Elaboração' => 'info',
                        'Enviada ao Cliente' => 'warning',
                        'Aceita' => 'success',
                        'Recusada' => 'danger',
                        'Expirada' => 'gray',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status da Cotação')
                    ->options([
                        'Em Elaboração' => 'Em Elaboração',
                        'Enviada ao Cliente' => 'Enviada ao Cliente',
                        'Aceita' => 'Aceita',
                        'Recusada' => 'Recusada',
                        'Expirada' => 'Expirada',
                    ]),
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
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
