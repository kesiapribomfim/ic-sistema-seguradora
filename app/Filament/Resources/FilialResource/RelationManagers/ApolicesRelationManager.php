<?php

namespace App\Filament\Resources\FilialResource\RelationManagers;

use App\Models\Apolice;
use App\Filament\Resources\ApoliceResource;
use App\Filament\Resources\CotacaoResource;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ApolicesRelationManager extends RelationManager
{
    protected static string $relationship = 'apolices';

    protected static ?string $title = 'Apólices';

    protected static ?string $icon = 'heroicon-o-document-text';

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('numero_apolice')
                    ->required()
                    ->maxLength(255),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('numero_apolice')
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Data de Criação')
                    ->date('d/m/Y')
                    ->sortable(),
                Tables\Columns\TextColumn::make('numero_apolice')
                    ->label('Nº da Apólice')
                    ->searchable()
                    ->sortable()
                    ->fontFamily('mono')
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Corretor/Emissor')
                    ->sortable(),

                Tables\Columns\TextColumn::make('data_fim')
                    ->label('Fim da Vigência')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('valor_total')
                    ->label('Prêmio Total')
                    ->money('BRL') // Formata automaticamente para R$ 0.000,00
                    ->sortable(),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        Apolice::STATUS_VIGENTE => 'info',
                        Apolice::STATUS_CANCELADA => 'warning',
                        Apolice::STATUS_RENOVADA => 'success',
                        Apolice::STATUS_SUSPENSA => 'danger',
                        Apolice::STATUS_EXPIRADA => 'gray',
                        Apolice::STATUS_SUBSTITUIDA => 'gray',
                        default => 'gray',
                    }),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status da Apólice')
                    ->options([
                        Apolice::STATUS_VIGENTE => 'Vigente',
                        Apolice::STATUS_CANCELADA => 'Cancelada',
                        Apolice::STATUS_RENOVADA => 'Renovada',
                        Apolice::STATUS_SUSPENSA => 'Suspensa por inadimplência',
                        Apolice::STATUS_EXPIRADA => 'Expirada',
                        Apolice::STATUS_SUBSTITUIDA => 'Substituída',
                    ]),
            ])
            ->headerActions([
                //
            ])
            ->actions([
                Tables\Actions\ActionGroup::make([
                    Tables\Actions\Action::make('ver_apolice')
                        ->label('Abrir Apólice')
                        ->icon('heroicon-o-eye')
                        ->url(fn (Model $record) => ApoliceResource::getUrl('view', ['record' => $record->id]))
                        ->openUrlInNewTab(), // Abre em nova aba para não perder a tela da filial
                    Tables\Actions\Action::make('ver_cotacao')
                        ->label('Ver Cotação')
                        ->icon('heroicon-o-calculator')
                        ->color('info')
                        ->visible(fn (Model $record) => $record->cotacao_id !== null)
                        ->url(fn (Model $record) => CotacaoResource::getUrl('view', ['record' => $record->cotacao_id]))
                        ->openUrlInNewTab(), // Abre em nova aba para não perder a tela da apólice
                ]),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);

    }
}
