<?php

namespace App\Filament\Resources\SeguradoResource\RelationManagers;

use App\Models\Cotacao;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;

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
                        Cotacao::STATUS_ELABORACAO => 'info',
                        Cotacao::STATUS_ENVIADA => 'warning',
                        Cotacao::STATUS_EM_SUBSCRICAO => '#ebb284',
                        Cotacao::STATUS_ACEITA => 'success',
                        Cotacao::STATUS_RECUSADA => 'danger',
                        Cotacao::STATUS_EXPIRADA => 'gray',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status da Cotação')
                    ->options([
                        Cotacao::STATUS_ELABORACAO => 'Em Elaboração',
                        Cotacao::STATUS_ENVIADA => 'Enviada ao Cliente',
                        Cotacao::STATUS_EM_SUBSCRICAO => 'Em Subscrição',
                        Cotacao::STATUS_ACEITA => 'Aceita',
                        Cotacao::STATUS_RECUSADA => 'Recusada',
                        Cotacao::STATUS_EXPIRADA => 'Expirada',
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
