<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ProdutoResource\Pages;
use App\Filament\Resources\ProdutoResource\RelationManagers;
use App\Models\Produto;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Filament\Forms\Components\Toggle;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;

class ProdutoResource extends Resource
{
    protected static ?string $model = Produto::class;

    protected static ?string $navigationIcon = 'heroicon-o-cube';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Informações Básicas')
                    ->columns(2) 
                    ->schema([
                                              

                        Forms\Components\TextInput::make('nome')
                            ->required()
                            ->maxLength(255),
                            
                        Forms\Components\TextInput::make('codigo')
                            ->label('Código do Produto')
                            ->required()
                            ->unique(ignoreRecord: true) 
                            ->maxLength(50),
                            
                        
                            
                        Forms\Components\TextInput::make('versao')
                            ->label('Versão')
                            ->default('1.0')
                            ->required(),

                        Forms\Components\Textarea::make('descricao')
                            ->label('Descrição')
                            ->columnSpanFull(),
                    ]),

                //campos especificos por ramo
                Forms\Components\Select::make('ramo')
                ->label('Ramo')
                ->options([
                    'Auto' => 'Auto',
                    'Vida' => 'Vida',
                    'Residencial' => 'Residencial',
                ])
                ->required()
                ->live(),

                // =========================================================================
                // SECTIONS: Fatores de Risco e Desconto por perfil de cliente, por ramo de seguro
                // =========================================================================
                //AUTO
                Forms\Components\Section::make('Parâmetros: Seguro Auto')
                    ->schema([
                        Forms\Components\Fieldset::make('Fatores de Risco (Agravantes)')
                            ->schema([
                                // TODO: Ainda não tem campos para coletar dados de motoristas
                                // Forms\Components\TextInput::make('parametros_calculo.fator_idade')
                                //     ->label('Agravante por Idade do Condutor (%)')
                                //     ->numeric()
                                //     ->helperText('Ex: Se preencher 5%, um cliente com idade 20 anos receberá 100% de acréscimo no prêmio final.'), 
                                
                                Forms\Components\TextInput::make('parametros_calculo.fator_veiculo_antigo')
                                    ->label('Agravante para Veículo Antigo (%)')
                                    ->numeric()
                                    ->helperText('Aplicado se o veículo tiver mais de 10 anos de fabricação.'),
                                
                                Forms\Components\TextInput::make('parametros_calculo.fator_tipo_moto')
                                    ->label('Agravante para Motos (%)')
                                    ->numeric(),

                                Forms\Components\TextInput::make('parametros_calculo.fator_tipo_caminhao')
                                    ->label('Agravante para Caminhões (%)')
                                    ->numeric(),

                                Forms\Components\TextInput::make('parametros_calculo.fator_kit_gas')
                                    ->label('Agravante para Veículo com Kit Gás (%)')
                                    ->numeric(),

                                Forms\Components\TextInput::make('parametros_calculo.fator_blindado')
                                    ->label('Agravante para Veículo Blindado (%)')
                                    ->numeric(),

                                Forms\Components\TextInput::make('parametros_calculo.fator_uso_comercial')
                                    ->label('Agravante para Uso Comercial / Aplicativo (%)')
                                    ->numeric()
                                    ->helperText('Aplicado se o cliente usa para entregas, táxi ou app.'),

                                Forms\Components\TextInput::make('parametros_calculo.fator_estacionamento_rua')
                                    ->label('Agravante para Pernoite na Rua (%)')
                                    ->numeric(),

                                Forms\Components\TextInput::make('parametros_calculo.fator_sinistro_anterior')
                                    ->label('Agravante por Acionamento Recente de Sinistro (%)')
                                    ->numeric()
                                    ->helperText('Aplicado se o seguro anterior foi utilizado.'),
                            ])
                            ->columns(3),

                        Forms\Components\Fieldset::make('Descontos por Perfil')
                            ->schema([
                                Forms\Components\TextInput::make('parametros_calculo.desconto_zero_km')
                                    ->label('Desconto para Veículo Zero KM (%)')
                                    ->numeric(),

                                Forms\Components\TextInput::make('parametros_calculo.desconto_garagem')
                                    ->label('Desconto para Garagem (Dia e Noite) (%)')
                                    ->numeric(),

                                Forms\Components\TextInput::make('parametros_calculo.desconto_por_classe_bonus')
                                    ->label('Desconto por Classe de Bônus (% por ponto)')
                                    ->numeric()
                                    ->helperText('Ex: Se preencher 5%, um cliente com Classe 3 receberá 15% de desconto final.'),
                            ])
                            ->columns(3),
                    ])
                    ->columns(1)
                    ->visible(fn (Forms\Get $get) => $get('ramo') === 'Auto'),
                // VIDA
                Forms\Components\Section::make('Parâmetros: Seguro de Vida')
                    ->schema([
                        Forms\Components\Fieldset::make('Fatores de Risco (Agravantes)')
                            ->schema([
                                Forms\Components\TextInput::make('parametros_calculo.fator_profissao_risco')
                                    ->label('Agravante para Profissão de Risco (%)')
                                    ->numeric(),

                                Forms\Components\TextInput::make('parametros_calculo.fator_imc_fora_padrao')
                                    ->label('Agravante para IMC fora do padrão (%)')
                                    ->numeric()
                                    ->helperText('Aplicado se o cálculo de Peso/Altura indicar obesidade ou baixo peso extremo.'),

                                Forms\Components\TextInput::make('parametros_calculo.fator_doenca_preexistente')
                                    ->label('Agravante Base para Doenças Leves/Moderadas (%)')
                                    ->numeric(),

                                Forms\Components\TextInput::make('parametros_calculo.fator_doenca_grave')
                                    ->label('Agravante Especial para Doenças Graves (%)')
                                    ->numeric()
                                    ->helperText('Aplicado se possuir Câncer, AVC, Infarto, Alzheimer, etc.'),

                                Forms\Components\TextInput::make('parametros_calculo.fator_fumante')
                                    ->label('Agravante para Tabagismo (%)')
                                    ->numeric(),

                                Forms\Components\TextInput::make('parametros_calculo.fator_alcool')
                                    ->label('Agravante para Consumo de Álcool (%)')
                                    ->numeric(),

                                Forms\Components\TextInput::make('parametros_calculo.fator_esportes_radicais')
                                    ->label('Agravante para Esportes Radicais (%)')
                                    ->numeric(),
                            ])
                            ->columns(3),

                        Forms\Components\Fieldset::make('Descontos e Regras')
                            ->schema([
                                Forms\Components\TextInput::make('parametros_calculo.desconto_perfil_saudavel')
                                    ->label('Desconto para Perfil Saudável (%)')
                                    ->numeric()
                                    ->helperText('Aplicado se o cliente não fuma, não bebe e não possui doenças preexistentes.'),

                                Forms\Components\TextInput::make('parametros_calculo.carencia_dias')
                                    ->label('Período de Carência (Dias)')
                                    ->numeric()
                                    ->required(),
                            ])
                            ->columns(2),
                    ])
                    ->columns(1)
                    ->visible(fn (Forms\Get $get) => $get('ramo') === 'Vida'),

                // RESIDENCIAL
                Forms\Components\Section::make('Parâmetros: Seguro Residencial')
                    ->schema([
                        Forms\Components\Fieldset::make('Fatores de Risco (Agravantes)')
                            ->schema([
                                Forms\Components\TextInput::make('parametros_calculo.fator_construcao_madeira')
                                    ->label('Agravante para Construção em Madeira (%)')
                                    ->numeric()
                                    ->helperText('Risco elevado de propagação de incêndio.'),

                                Forms\Components\TextInput::make('parametros_calculo.fator_uso_veraneio')
                                    ->label('Agravante para Casa de Veraneio (%)')
                                    ->numeric()
                                    ->helperText('Aplicado devido ao risco de invasão por longos períodos de vacância.'),

                                Forms\Components\TextInput::make('parametros_calculo.fator_imovel_desocupado')
                                    ->label('Agravante para Imóvel Desocupado (%)')
                                    ->numeric(),

                                Forms\Components\TextInput::make('parametros_calculo.fator_regiao_rural')
                                    ->label('Agravante para Região Rural (%)')
                                    ->numeric()
                                    ->helperText('Dificuldade de acesso para bombeiros e polícia.'),

                                Forms\Components\TextInput::make('parametros_calculo.fator_agro_comercial')
                                    ->label('Agravante para Atividade Agropecuária/Comercial (%)')
                                    ->numeric(),

                                Forms\Components\TextInput::make('parametros_calculo.fator_terreno_baldio')
                                    ->label('Agravante para Divisa com Terreno Baldio (%)')
                                    ->numeric()
                                    ->helperText('Facilita invasões e risco de queimadas externas.'),

                                Forms\Components\TextInput::make('parametros_calculo.fator_sinistro_anterior')
                                    ->label('Agravante por Histórico de Sinistros (%)')
                                    ->numeric(),
                            ])
                            ->columns(3),

                        Forms\Components\Fieldset::make('Descontos por Perfil')
                            ->schema([
                                Forms\Components\TextInput::make('parametros_calculo.desconto_apartamento')
                                    ->label('Desconto para Apartamentos (%)')
                                    ->numeric()
                                    ->helperText('Aplicado por possuir menor risco de invasão em relação a casas de rua.'),

                                Forms\Components\TextInput::make('parametros_calculo.desconto_condominio_horizontal')
                                    ->label('Desconto para Condomínio Fechado (%)')
                                    ->numeric()
                                    ->helperText('Aplicado devido ao controle de portaria e segurança privada.'),
                            ])
                            ->columns(3),
                    ])
                    ->columns(1)
                    ->visible(fn (Forms\Get $get) => $get('ramo') === 'Residencial'),
                
                Forms\Components\Section::make('Parâmetros Financeiros (Base)')
                    ->columns(2)
                    ->schema([
                        Forms\Components\TextInput::make('parametros_calculo.taxa_base')
                            ->label('Taxa Base (%)')
                            ->numeric()
                            ->required(),
                            
                        Forms\Components\TextInput::make('parametros_calculo.valor_franquia')
                            ->label('Valor Base da Franquia (R$)')
                            ->numeric(),
                    ]),

                Forms\Components\Section::make('Configurações')
                    ->schema([
                        Toggle::make('status')
                            ->label('Produto Ativo')
                            ->default(false) 
                            ->hiddenOn('create') 
                            ->helperText('O produto só deve ser ativado após o cadastro das coberturas.'),
                    ])
            
                
            ]);// TODO: Mudar rediecionamento de produito criado para a edit e não para a view
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('nome')
                    ->searchable(),
                Tables\Columns\TextColumn::make('codigo')
                    ->searchable(),
                Tables\Columns\TextColumn::make('ramo')
                ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Vida' => 'danger',    // Vermelho
                        'Residencial' => 'info', // Azul
                        'Auto' => 'success', //Verde
                    }),
                Tables\Columns\IconColumn::make('status')
                    ->label('Ativo')
                    ->boolean(),
            ])
            ->recordUrl(null)
            ->recordAction(Tables\Actions\ViewAction::class)
            ->filters([
                Tables\Filters\SelectFilter::make('ramo')
                    ->options([
                        'Auto' => 'Auto',
                        'Residencial' => 'Residencial',
                        'Vida' => 'Vida',
                    ]),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        1 => 'Ativo',
                        0 => 'Inativo',
                    ])
            ])
            ->actions([
                ActionGroup::make([
                    EditAction::make(),
                    ViewAction::make(),
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
            RelationManagers\CoberturasRelationManager::class,
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListProdutos::route('/'),
            'create' => Pages\CreateProduto::route('/create'),
            'view' => Pages\ViewProduto::route('/{record}/view'),
            'edit' => Pages\EditProduto::route('/{record}/edit'),
        ];
    }
}
