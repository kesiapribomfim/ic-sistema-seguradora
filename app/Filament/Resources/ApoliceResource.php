<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ApoliceResource\Pages;
use App\Filament\Resources\ApoliceResource\RelationManagers;
use App\Models\Apolice;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\Eloquent\Model;

class ApoliceResource extends Resource
{
    protected static ?string $model = Apolice::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();

        $user = auth()->user();

        if ($user->hasRole('Corretor') && ! $user->hasRole('super_admin')) {
            $query->where('user_id', $user->id);
        }

        return $query;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('cotacao.segurado_id') // Usamos uma coluna real como âncora
                    ->label('Cliente')
                    ->getStateUsing(function (Model $record) {
                        $segurado = $record->cotacao?->segurado;

                        if (! $segurado) {
                            return 'Não informado';
                        }

                        if ($segurado->tipo === 'PF') {
                            return $segurado->seguradoPf?->nome ?? 'Nome PF não encontrado';
                        }

                        if ($segurado->tipo === 'PJ') {
                            return $segurado->seguradoPj?->razao_social ?? 'Razão Social PJ não encontrada';
                        }

                        return 'Tipo Desconhecido';
                    }),

                Tables\Columns\TextColumn::make('cotacao.user.name') // Troque 'corretor' por 'user' se for esse o nome do relacionamento no model Cotacao
                    ->label('Corretor')
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('data_emissao')
                    ->label('Data de Emissão')
                    ->date('d/m/Y')
                    ->sortable()
                    ->toggleable(isToggledHiddenByDefault: true),

                Tables\Columns\TextColumn::make('data_inicio')
                    ->label('Início da Vigência')
                    ->date('d/m/Y')
                    ->sortable(),

                Tables\Columns\TextColumn::make('data_fim')
                    ->label('Fim da Vigência')
                    ->date('d/m/Y')
                    ->sortable(),
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
            'index' => Pages\ListApolices::route('/'),
            'create' => Pages\CreateApolice::route('/create'),
            'edit' => Pages\EditApolice::route('/{record}/edit'),
        ];
    }
}
