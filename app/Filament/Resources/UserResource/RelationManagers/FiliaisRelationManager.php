<?php

namespace App\Filament\Resources\UserResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Filament\Resources\Components\Tab;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\AttachAction;
use Filament\Tables\Actions\DetachAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\Action;

class FiliaisRelationManager extends RelationManager
{
    protected static string $relationship = 'filiais';
    protected static ?string $title = 'Vínculos com Filiais';
    protected static ?string $icon = 'heroicon-o-building-office';

    public function form(Form $form): Form
    {
        return $form
            ->schema([]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('nome')
            ->columns([
                Tables\Columns\TextColumn::make('nome')
                    ->label('Nome da Filial'),
                Tables\Columns\TextColumn::make('perfil_acesso')
                    ->label('Perfil de Acesso')
                    ->badge() 
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('perfil_acesso')
                    ->options([
                        'Corretor' => 'Corretor',
                        'Financeiro' => 'Financeiro',
                        'Gestor de Filial' => 'Gestor de Filial',
                        'Analista de Sinistro' => 'Analista de Sinistro',
                        'Subscritor' => 'Subscritor',
                    ])
            ])
            ->headerActions([
                Tables\Actions\AttachAction::make()
                    ->preloadRecordSelect()
                    ->form(fn (Tables\Actions\AttachAction $action): array => [
                        $action->getRecordSelect(), 
                        
                        Forms\Components\Select::make('perfil_acesso')
                            ->label('Perfil nesta Filial')
                            ->options([
                                'Gestor de Filial' => 'Gestor de Filial',
                                'Subscritor' => 'Subscritor',
                                'Corretor' => 'Corretor',
                                'Analista de Sinistros' => 'Analista de Sinistros',
                                'Financeiro' => 'Financeiro',
                            ])
                            ->required(),
                    ])
                    ->after(function (array $data, RelationManager $livewire) {
                        $livewire->getOwnerRecord()->assignRole($data['perfil_acesso']);
                    }),
            ])
            ->actions([
                ActionGroup::make([
                    EditAction::make(),
                    
                    Action::make('ver_filial')
                        ->label('Ver Filial')
                        ->icon('heroicon-o-eye')
                        ->url(fn (Model $record) => \App\Filament\Resources\FilialResource::getUrl('view', ['record' => $record->id]))
                        ->openUrlInNewTab(), 
                        
                    DetachAction::make()
                        ->before(function (RelationManager $livewire, Model $record){
                            $livewire->getOwnerRecord()->removeRole($record->pivot->perfil_acesso);
                        }),
                ])
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make(),
                ]),
            ]);
    }
}