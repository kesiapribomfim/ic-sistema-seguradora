<?php

namespace App\Filament\Resources;

use App\Filament\Resources\FilialResource\Pages;
use App\Filament\Resources\FilialResource\RelationManagers;
use App\Models\Filial;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Tables\Actions\ActionGroup;

class FilialResource extends Resource
{
    protected static ?string $model = Filial::class;
    protected static ?string $modelLabel = 'Filial';
    protected static ?string $pluralModelLabel = 'Filiais';
    protected static ?string $slug = 'filiais';
    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //id, nome, cnpj, telefone, endereco, bairro, cidade, estado, cep
                Forms\Components\TextInput::make('nome')
                    ->label('Nome da Filial')
                    ->required()
                    ->maxLength(255)
                    ->columnSpanFull(),
                Forms\Components\TextInput::make('cnpj') //forçar nome a ser "CNPJ"
                    ->label('CNPJ')
                    ->required()
                    ->unique(ignoreRecord: true)
                    ->mask('99.999.999/9999-99')
                    ->stripCharacters(['.', '-', '/']),
                Forms\Components\TextInput::make('telefone')
                    ->label('Telefone')
                    ->required()
                    ->mask('(99) 99999-9999')
                    ->stripCharacters(['(',' ',')', '-']),
                Forms\Components\TextInput::make('rua')
                    ->label('Rua')
                    ->required(),
                Forms\Components\TextInput::make('numero')
                    ->label('Numero')
                    ->required(),
                Forms\Components\TextInput::make('bairro') ->required(),
                Forms\Components\TextInput::make('complemento'),
                Forms\Components\TextInput::make('cidade') -> required(),
                Forms\Components\TextInput::make('uf')
                    ->label('UF')
                    ->required()
                    ->maxLength(2)
                    ->extraAttributes(['style'=>'text-transform: uppercase']),
                Forms\Components\TextInput::make('cep')
                    ->label('CEP')
                    ->required()
                    ->mask('99.999-999')
                    ->stripCharacters(['.', '-']),
                
            ]);

    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nome')
                    ->searchable(),
                Tables\Columns\TextColumn::make('telefone'),
                                
            ])
            ->filters([
                    
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
            RelationManagers\UsersRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListFilials::route('/'),
            'create' => Pages\CreateFilial::route('/create'),
            'edit' => Pages\EditFilial::route('/{record}/edit'),
        ];
    }
}
