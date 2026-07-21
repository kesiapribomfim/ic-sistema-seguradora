<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SeguradoResource\Pages;
use App\Filament\Resources\SeguradoResource\RelationManagers;
use App\Models\Segurado;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Forms\FormsComponent;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Forms\Components\Toggle;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Select;

class SeguradoResource extends Resource
{
    protected static ?string $model = Segurado::class;

    protected static ?string $navigationIcon = 'heroicon-o-identification';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                //selecionar tipo para modificar campos do fomulario
                Forms\Components\Select::make('tipo')
                    ->label('Tipo de Cliente')
                    ->options([
                        'PF' => 'Pessoa Física',
                        'PJ' => 'Pessoa Jurídica',
                    ])
                    ->required()
                    ->live(),

                //Atributos PF
                Forms\Components\Fieldset::make('Dados de Pessoa Física')
                    ->relationship('seguradoPf') 
                    ->visible(fn (Forms\Get $get): bool => $get('tipo') === 'PF')
                    ->schema([
                        Forms\Components\TextInput::make('nome')
                            ->required(),
                        Forms\Components\TextInput::make('cpf')
                            ->label('CPF')  
                            ->required(),
                        Forms\Components\TextInput::make('rg')
                            ->label('RG')
                            ->required(),
                        Forms\Components\DatePicker::make('data_nascimento')
                            ->label('Data de Nascimento')
                            ->required(),
                        Forms\Components\TextInput::make('profissao')
                            ->label('Profissão')
                            ->required(),
                    ]),

                //Atributos PJ
                Forms\Components\Fieldset::make('Dados de Pessoa Juridica')
                    ->relationship('seguradoPj')
                    ->visible(fn(Forms\Get $get): bool => $get('tipo') === 'PJ')
                    ->schema([
                        Forms\Components\TextInput::make('cnpj')
                            ->label('CNPJ')
                            ->required()
                            ->unique(),
                        Forms\Components\TextInput::make('razao_social')
                            ->required(),
                        Forms\Components\TextInput::make('inscricao_estadual')
                            ->required()
                            ->unique(),
                    ]),

                //Aributos comuns
                Forms\Components\Fieldset::make('Dados de Contato')
                    ->schema([
                        Forms\Components\TextInput::make('telefone')
                            ->required()
                            ->unique(),
                        Forms\Components\TextInput::make('email')
                            ->required()
                            ->unique(),
                    ]),
                

                //Endereço FieldSet
                Forms\Components\Fieldset::make('Endereço')
                    ->schema([
                        Forms\Components\TextInput::make('rua'),
                        Forms\Components\TextInput::make('numero'),
                        Forms\Components\TextInput::make('bairro'),
                        Forms\Components\TextInput::make('complemento'),
                        Forms\Components\TextInput::make('cidade'),
                        Forms\Components\TextInput::make('uf'),
                        Forms\Components\TextInput::make('cep')
                            ->label('CEP'),

                    ]),           
                
                //linkando com a tabela Users
                Select::make('user_id')
                            ->label('Selecione o Corretor Responsável')
                            ->relationship(
                                name: 'user',
                                titleAttribute: 'name',
                                modifyQueryUsing: fn (Builder $query) => $query
                                    ->where('status', true)
                                    ->whereHas('filiais', function (Builder $q) {
                                        $q->where('filial_user.perfil_acesso', 'Corretor');
                                    })
                            )
                            ->default(fn () => auth()->id())
                            ->searchable() 
                            ->preload()   
                            ->required(),

                
                Forms\Components\TextInput::make('score'),
                Toggle::make('status')
                    ->label('Ativo'),


                
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
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

            ])
            ->filters([
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
            'index' => Pages\ListSegurados::route('/'),
            'create' => Pages\CreateSegurado::route('/create'),
            'edit' => Pages\EditSegurado::route('/{record}/edit'),
        ];
    }
}
