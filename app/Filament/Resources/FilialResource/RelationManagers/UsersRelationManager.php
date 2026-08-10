<?php

namespace App\Filament\Resources\FilialResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Database\Eloquent\Model;
use Filament\Resources\Components\Tab;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\AttachAction;
use Filament\Tables\Actions\DetachAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\Action;

class UsersRelationManager extends RelationManager
{
    protected static string $relationship = 'users';
    protected static ?string $title = 'Usuários';
    protected static ?string $icon = 'heroicon-o-users';

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
                        $action->getRecordSelect(), // O campo obrigatório que escolhe "quem" é a pessoa
                        
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
                    ])
                    ->after(function (array $data, Model $record) {
                        $record->assignRole($data['perfil_acesso']);
                    }),
            ])
            ->actions([
                ActionGroup::make([
                    EditAction::make(),
                    Action::make('ver_usuario')
                        ->label('Ver Perfil')
                        ->icon('heroicon-o-eye')
                        ->url(fn (Model $record) => \App\Filament\Resources\UserResource::getUrl('view', ['record' => $record->id]))
                        ->openUrlInNewTab(), // Abre em nova aba para não perder a tela da filial
                    Action::make('ver_carteira')
                        ->label('Ver Carteira')
                        ->icon('heroicon-o-identification')
                        ->visible(fn (Model $record): bool => $record->pivot->perfil_acesso === 'Corretor')
                        ->url(fn (Model $record): string => \App\Filament\Resources\SeguradoResource::getUrl('index', [
                            'tableFilters' => [
                                'user_id' => ['value' => $record->id],
                            ],
                        ])),
                    DetachAction::make()
                        ->before(function (Model $record){
                            $record->removeRole($record->perfil_acesso);
                        }),

                    
                ])
   
                    

            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DetachBulkAction::make(),
                ]),
            ]);
    }

    public function getTabs(): array
    {
        return[
            'todos' => Tab::make('Todos os Vínculos'),
        
            'corretores' => Tab::make('Corretores')
                ->modifyQueryUsing(fn (Builder $query) => $query->where('filial_user.perfil_acesso', 'Corretor')),
    ];
        
    }
}
