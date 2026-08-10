<?php

namespace App\Filament\Resources\FilialResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use App\Models\Segurado;
use Illuminate\Database\Eloquent\Model;

class SeguradosRelationManager extends RelationManager
{
    protected static string $relationship = 'segurados';
    protected static ?string $icon = 'heroicon-o-identification';

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
                Tables\Columns\TextColumn::make('tipo')
                    ->label('Tipo')
                    ->badge() // Transforma o texto numa "etiqueta" colorida bonitinha
                    ->color(fn (string $state): string => match ($state) {
                        'PF' => 'info',    // Azul para PF
                        'PJ' => 'warning', // Amarelo para PJ
                    }),
                Tables\Columns\TextColumn::make('identificacao_cliente')
                    ->label('Nome / Razão Social')
                    ->state(function (Segurado $record) {
                        return $record->tipo === 'PF' 
                            ? $record->seguradoPf?->nome 
                            : $record->seguradoPj?->razao_social;
                    })
                    ->searchable(query: function (Builder $query, string $search): Builder {
                        // pesquisa no banco
                        return $query
                            ->whereHas('seguradoPf', fn ($q) => $q->where('nome', 'like', "%{$search}%"))
                            ->orWhereHas('seguradoPj', fn ($q) => $q->where('razao_social', 'like', "%{$search}%"));
                    })
                    ->weight(\Filament\Support\Enums\FontWeight::Bold)
                    //->sortable() (em ordem alfabetica)
                    ->description(fn ($record) => "Corretor: {$record->user->name}"),
                    Tables\Columns\TextColumn::make('email'),
                
                Tables\Columns\IconColumn::make('status')
                ->label('Ativo')
                ->boolean(),

            ])
            ->recordUrl(null)
            ->recordAction(Tables\Actions\ViewAction::class)
            ->filters([
                Tables\Filters\SelectFilter::make('user_id')
                    ->label('Carteira do Corretor')
                    ->options(
                        // Aqui buscamos no banco: a Chave será o ID (oculto) e o Valor será o Nome (visível)
                        \App\Models\User::role('Corretor')->pluck('name', 'id')->toArray()
                    )
                    ->searchable(),
                Tables\Filters\SelectFilter::make('tipo')
                    ->options([
                        'PF' => 'CPF',
                        'PJ' => 'CNPJ',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        1 => 'Ativo',
                        0 => 'Inativo',
                    ]),
            ])
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make(),
            ])
            ->actions([
               Tables\Actions\ActionGroup::make([
                    Tables\Actions\EditAction::make(),
                    Tables\Actions\Action::make('ver_segurado')
                        ->label('Ver Perfil')
                        ->icon('heroicon-o-eye')
                        ->url(fn (Model $record) => \App\Filament\Resources\SeguradoResource::getUrl('view', ['record' => $record->id]))
                        ->openUrlInNewTab(), // Abre em nova aba para não perder a tela da filial
                ])
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }
}
