<?php

namespace App\Filament\Widgets;

use App\Models\Sinistro;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Support\Facades\Auth;
use Illuminate\Database\Eloquent\Builder;

class FilaSinistrosWidget extends BaseWidget
{
    protected static ?int $sort = 3;
    protected int | string | array $columnSpan = 'full'; 
    protected static ?string $heading = 'Fila de Análise (Sinistros Recentes)';

    public static function canView(): bool
    {
        return Auth::user()->hasAnyRole(['super_admin', 'Administrador Geral', 'Gestor de Filial', 'Analista de Sinistros']);
    }

    public function table(Table $table): Table
    {
        /** @var \App\Models\User $user */
        $user = Auth::user();
        $isGlobal = $user->hasAnyRole(['super_admin', 'Administrador Geral']);

        $query = Sinistro::with('apolice')->whereIn('status', ['Aberto', 'Em análise'])->latest();

        if (!$isGlobal) {
            $filiaisIds = $user->filiais()->pluck('filiais.id')->toArray();
            $query->whereHas('apolice', function (Builder $q) use ($filiaisIds) {
                $q->whereIn('filial_id', $filiaisIds);
            });
        }

        return $table
            ->query($query)
            ->columns([
                Tables\Columns\TextColumn::make('apolice.numero_apolice')
                    ->label('Apólice')
                    ->searchable()
                    ->weight('bold'),

                Tables\Columns\TextColumn::make('data_hora_ocorrencia')
                    ->label('Ocorrência')
                    ->dateTime('d/m/Y H:i'),

                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn(string $state): string => match ($state) {
                        'Aberto' => 'warning',
                        'Em análise' => 'info',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('descricao')
                    ->label('Descrição')
                    ->limit(50),
            ])
            ->paginated([5]);
    }
}
