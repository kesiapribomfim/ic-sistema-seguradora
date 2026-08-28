<?php

namespace App\Filament\Resources;

use App\Filament\Resources\UserResource\Pages;
use App\Filament\Resources\UserResource\RelationManagers;
use App\Models\User;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Toggle;
use Filament\Tables\Filters\Filter;
use Illuminate\Database\Eloquent\Builder;
use Filament\Resources\Components\Tab;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Illuminate\Support\Facades\Hash;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;

// TODO: Arranjar uma forma de colocar uma relação das filiais dentro dos usuários, com o perfil de acesso
// TODO: Melhorar essa resource pelo amor de DEUS
// TODO: Assim que o usuário for criado, criar algum meio para vinculá-lo a uma filial
class UserResource extends Resource
{
    protected static ?string $model = User::class;
    protected static ?string $modelLabel = 'Usuário';
    protected static ?string $pluralModelLabel = 'Usuários';
    protected static ?string $slug = 'usuarios';
    protected static ?string $navigationIcon = 'heroicon-o-users';


    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\TextInput::make('name')
                    ->label('Nome Completo')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('email')
                    ->label('E-mail')
                    ->required()
                    ->email()
                    ->unique(ignoreRecord: true)
                    ->maxLength(255),
                Forms\Components\TextInput::make('password') //mexer nisso, pelo amor de Deus
                    ->password() // Oculta os caracteres digitados
                    ->label('Senha')
                    ->placeholder('Deixe em branco para manter a atual')
                    ->helperText('Preencha apenas se desejar redefinir o acesso deste usuário.')
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(fn (?string $state) => filled($state))
                    ->dehydrateStateUsing(fn (string $state): string => \Illuminate\Support\Facades\Hash::make($state)),
                Toggle::make('status')
                    ->label('Ativo'),
                Forms\Components\Select::make('filial_id')
                    ->label('Vincular à Filial')
                    ->options(function () {
                        $user = auth()->user();
                        if ($user->hasRole('Gestor de Filial')) {
                            return $user->filiais()->wherePivot('perfil_acesso', 'Gestor de Filial')->pluck('filiais.nome', 'filiais.id');
                        }
                        return \App\Models\Filial::pluck('nome', 'id');
                    })
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(false), 

                Forms\Components\Select::make('perfil_acesso')
                    ->label('Perfil de Acesso')
                    ->options([
                        'Gestor de Filial' => 'Gestor de Filial',
                        'Subscritor' => 'Subscritor',
                        'Corretor' => 'Corretor',
                        'Analista de Sinistros' => 'Analista de Sinistros',
                        'Financeiro' => 'Financeiro',
                    ])
                    ->required(fn (string $operation): bool => $operation === 'create')
                    ->dehydrated(false),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        if ($user->hasRole('super_admin')){
            return $query;
        }
        if ($user->hasRole('Gestor de Filial')) {
            
            $filiaisComoGestorIds = $user->filiais()
                ->wherePivot('perfil_acesso', 'Gestor de Filial') 
                ->pluck('filiais.id')
                ->toArray();
            
            return $query->whereHas('filiais', function ($q) use ($filiaisComoGestorIds) {
                $q->whereIn('filiais.id', $filiaisComoGestorIds);
            });
        }

        return $query;
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label('Nome Completo')
                    ->searchable(),
                Tables\Columns\TextColumn::make('email'),
                Tables\Columns\IconColumn::make('status')
                    ->label('Ativo')
                    ->boolean(),
            ])
            ->recordUrl(null)
            ->recordAction(Tables\Actions\ViewAction::class)
            ->filters([
                Tables\Filters\selectFilter::make('status')
                    ->options([
                        1 => 'Ativo',
                        0 => 'Inativo',
                    ])
            ])
            ->actions([
                ActionGroup::make([
                    EditAction::make(),
                    ViewAction::make()
                        ->label('Ver Perfil'),
                    // TODO: Ver carteira para corretores e etc
                ])
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
            RelationManagers\FiliaisRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListUsers::route('/'),
            'create' => Pages\CreateUser::route('/create'),
            'view' => Pages\ViewUser::route('/{record}/view'),
            'edit' => Pages\EditUser::route('/{record}/edit'),
        ];
    }

}
