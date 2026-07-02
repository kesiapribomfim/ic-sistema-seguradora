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
    protected static ?string $title = 'Usuarios da Filial';

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
                    ]),
            ])
            ->actions([
                ActionGroup::make([
                    EditAction::make(),
                    //se corretor
                    Action::make('ver_carteira')
                        ->label('Ver Carteira')
                        ->icon('heroicon-o-identification')
                        ->visible(fn (Model $record): bool => $record->perfil_acesso === 'Corretor') //IMPORTANTE: Add Badges para incluir numero de segurados aqui
                        ->action(function (Model $record) {
                            // Aqui é onde definiremos o que o botão FAZ quando for clicado.
                            // No futuro, ele vai redirecionar para a tela de Segurados filtrando por este corretor.
                        }),
                    DetachAction::make(),

                    
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
