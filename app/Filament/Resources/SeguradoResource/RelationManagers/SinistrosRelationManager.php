<?php

namespace App\Filament\Resources\SeguradoResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\Eloquent\Model;

class SinistrosRelationManager extends RelationManager
{
    protected static string $relationship = 'sinistros';
    protected static ?string $icon = 'heroicon-o-exclamation-triangle';

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
                Tables\Columns\TextColumn::make('id')
                    ->label('Protocolo')
                    ->sortable()
                    ->searchable()
                    ->fontFamily('mono')
                    ->weight('bold')
                    ->prefix('SIN-'), // Adiciona um prefixo visual sem alterar o banco

                Tables\Columns\TextColumn::make('apolice.numero_apolice')
                    ->label('Apólice Vinculada')
                    ->searchable()
                    ->sortable()
                    ->color('info')
                    // Link direto para a apólice, mantendo a excelente navegabilidade que você criou
                    ->url(fn (Model $record) => $record->apolice_id ? \App\Filament\Resources\ApoliceResource::getUrl('view', ['record' => $record->apolice_id]) : null)
                    ->openUrlInNewTab(),

                // Buscando o cliente dinamicamente através do relacionamento da Apólice
                Tables\Columns\TextColumn::make('identificacao_segurado')
                    ->label('Segurado')
                    ->state(function (Model $record) {
                        $segurado = $record->apolice?->segurado;
                        if (!$segurado) return '-';
                        
                        return $segurado->tipo === 'PF' 
                            ? $segurado->seguradoPf?->nome 
                            : $segurado->seguradoPj?->razao_social;
                    })
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('data_hora_ocorrencia')
                    ->label('Data do Evento')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('valor_indenizacao')
                    ->label('Indenização Aprovada')
                    ->money('BRL')
                    ->placeholder('-')
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Aberto' => 'warning',
                        'Em análise', 'Em perícia' => 'info',
                        'Aprovado', 'Pago' => 'success',
                        'Negado' => 'danger',
                        'Encerrado' => 'gray',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status do Sinistro')
                    ->attribute('sinistros.status')
                    ->options([
                        'Aberto' => 'Aberto',
                        'Em análise' => 'Em análise',
                        'Em perícia' => 'Em perícia',
                        'Aprovado' => 'Aprovado',
                        'Negado' => 'Negado',
                        'Pago' => 'Pago',
                        'Encerrado' => 'Encerrado',
                    ]),
            ])
            ->headerActions([
                //
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
