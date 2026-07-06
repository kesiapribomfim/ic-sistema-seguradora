<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CotacaoResource\Pages;
use App\Filament\Resources\CotacaoResource\RelationManagers;
use App\Models\Cotacao;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Wizard;
use Filament\Forms\Components\Select;
use Illuminate\Support\HtmlString;
use Filament\Forms\Components\ToggleButtons;


class CotacaoResource extends Resource
{
    protected static ?string $model = Cotacao::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationLabel = 'Cotações';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Wizard::make([
                    Wizard\Step::make('Cliente e Produto')
                        ->schema([
                            Select::make('segurado_id')
                                ->label('Selecione o Cliente')
                                ->relationship('segurado', 'id') 
                                ->getOptionLabelFromRecordUsing(function ($record) {
                                    return $record->tipo === 'PF'
                                        ? "{$record->seguradoPf?->nome} (CPF: {$record->seguradoPf?->cpf})"
                                        : "{$record->seguradoPj?->razao_social} (CNPJ: {$record->seguradoPj?->cnpj})";
                                })
                                ->searchable() 
                                ->preload()   
                                ->required()
                                ->helperText(function () {
                                    $url = \App\Filament\Resources\SeguradoResource::getUrl('create');
                                    return new HtmlString('Cadastrar novo cliente <a href="' . $url . '" class="text-primary-600 underline hover:text-primary-500">aqui</a>.');
                                }),
                            Select::make('produto_id')
                                ->label('Selecione o Produto')
                                ->relationship(
                                    name: 'produto',
                                    titleAttribute: 'nome',
                                    modifyQueryUsing: fn (Builder $query) => $query
                                        ->where('status', true)
                                )
                                ->getOptionLabelFromRecordUsing(fn ($record) => "{$record->nome} ({$record->ramo})")
                                ->searchable(['nome', 'codigo'])
                                ->preload()
                                ->live()
                                ->afterStateUpdated(function(?string $state, Set $set) {
                                    if ($state) {
                                        $produto = \App\Models\Produto::find($state);
                                        if ($produto) {
                                            $set('ramo_temporario', $produto->ramo);
                                        }
                                    } else {
                                        $set('ramo_temporario', null);
                                    }
                                })
                                ->required(),
                            Forms\Components\Hidden::make('ramo_temporario')
                                ->dehydrated(false),
                                
                        ]),
                    Wizard\Step::make('Dados Específicos')
                        ->statePath('dados_especificos')
                        ->schema([
                            //AUTO
                            Forms\Components\Group::make()
                                ->visible (fn (Get $get)=> $get ('../ramo_temporario') == 'Auto')
                                ->schema([
                                    Forms\Components\Section::make('O Veículo')
                                        ->icon('heroicon-o-truck')
                                        ->schema([
                                            Forms\Components\TextInput::make('placa')
                                                ->label('Placa do Veículo')
                                                ->nullable(),
                                            Forms\Components\Select::make('tipo_veiculo')
                                                ->label('Tipo de Veículo')
                                                ->options([
                                                    'carro' => 'Carro',
                                                    'moto' => 'Moto',
                                                    'caminhao' => 'Caminhão',
                                                ])
                                                ->required(),
                                            Forms\Components\TextInput::make('modelo')
                                                ->label('Modelo do Veículo')
                                                ->required(),
                                            Forms\Components\TextInput::make('ano')
                                                ->label('Ano do Veículo')
                                                ->required(),

                                            Forms\Components\Group::make()
                                            ->schema([
                                                Forms\Components\Toggle::make('zero')
                                                ->label('É zero km?')
                                                ->default(false),
                                            Forms\Components\Toggle::make('kit_gas')
                                                ->label('Possui kit a gas?')
                                                ->default(false),
                                            Forms\Components\Toggle::make('blindado')
                                                ->label('Blindado?')
                                                ->default(false),
                                            Forms\Components\Toggle::make('imposto')
                                                ->label('É isento de imposto?')
                                                ->default(false),
                                            ])
                                            ->columns(4),                                            
                                        ]),
                                    Forms\Components\Section::make('Utilização')
                                        ->icon('heroicon-o-map-pin')
                                        ->schema([
                                            ToggleButtons::make('uso')
                                                ->label('O Veículo é utilizado para alguma das atividades abaixo?')
                                                ->multiple()
                                                ->options([
                                                    'comercial' => 'Atividade Comercial', //add campos
                                                    'trabalho' => 'Ir ao Trabalho',
                                                    'estudo' => 'Ir a faculdade, escola ou pós-graduação',
                                                ])
                                                ->nullable(),
                                            //endereço
                                            Forms\Components\Fieldset::make('noite')
                                                ->label('O veículo fica à noite?')
                                                ->schema([
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
                                                    Forms\Components\TextInput::make('CEP')
                                                        ->label('CEP')
                                                        ->placeholder('CEP')
                                                        ->required()
                                                        ->mask('99.999-999')
                                                        ->stripCharacters(['.', '-']),
                                                ]),    
                                                
                                        ]),
                                ]),
                                
        
                        ]),
                    Wizard\Step::make('Billing')
                        ->schema([
                            // ...
                        ]),
                ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')
                    ->label('Cotação')
                    ->weight(\Filament\Support\Enums\FontWeight::Bold)
                    ->description(fn ($record) => $record->produto->nome) 
                    ->sortable(),
                Tables\Columns\TextColumn::make('user.name')
                    ->label('Corretor Responsável'),
                //mexer nisso aqui
                Tables\Columns\TextColumn::make('identificação_segurado')
                    ->label('Cliente')
                    ->state(fn (Cotacao $record) =>
                        $record->segurado?->tipo === 'PF' ? $record->segurado->seguradoPf->nome 
                        : $record->segurado?->seguradoPj->razao_social),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->colors([
                        'info' => 'Em Elaboração',
                        'success' => 'Aceita',
                        'danger' => 'Recusada',
                        'warning' => 'Enviado ao Cliente',
                        'primary' => 'Expirada',
                    ]),
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
            'index' => Pages\ListCotacaos::route('/'),
            'create' => Pages\CreateCotacao::route('/create'),
            'edit' => Pages\EditCotacao::route('/{record}/edit'),
        ];
    }
}
