<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CotacaoResource\Pages;
use App\Models\Cotacao;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Get;
use Filament\Forms\Components\Wizard;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\Auth;

class CotacaoResource extends Resource
{
    protected static ?string $model = Cotacao::class;
    protected static ?string $modelLabel = 'Cotação';
    protected static ?string $pluralModelLabel = 'Cotações';
    protected static ?string $slug = 'cotacoes';
    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Wizard::make([
                    Wizard\Step::make('Cliente e Ramo')
                        ->schema(self::getClienteRamoSchema())
                        ->columns(2),

                    Wizard\Step::make('Dados Específicos')
                        ->statePath('dados_especificos')
                        ->schema([
                            self::getAutoSchema(),
                            self::getResidencialSchema(),
                            self::getVidaSchema(),
                        ]),

                    Wizard\Step::make('Coberturas')
                        ->statePath('dados_especificos')
                        ->schema([
                            self::getCoberturasAutoSchema(),
                            self::getCoberturasResidencialSchema(),
                            self::getCoberturasVidaSchema()
                        ]),

                    Wizard\Step::make('Status e Validade')
                        ->schema(self::getStatusValidadeSchema())
                        ->columns(2),

                ])->columnSpanFull(),
            ]);
    }

    // =========================================================================
    // BLOCOS MODULARIZADOS DO FORMULÁRIO
    // =========================================================================

    private static function getClienteRamoSchema(): array
    {
        return [
            Forms\Components\Select::make('segurado_id')
                ->label('Selecione o Cliente')
                ->relationship('segurado', 'id') 
                ->getOptionLabelFromRecordUsing(fn ($record) => $record->tipo === 'PF' 
                    ? "{$record->seguradoPf?->nome} (CPF: {$record->seguradoPf?->cpf})" 
                    : "{$record->seguradoPj?->razao_social} (CNPJ: {$record->seguradoPj?->cnpj})")
                ->searchable() 
                ->preload()   
                ->required()
                ->helperText(fn () => new HtmlString('Cadastrar novo cliente <a href="' . \App\Filament\Resources\SeguradoResource::getUrl('create') . '" class="text-primary-600 underline">aqui</a>.')),
            
            Forms\Components\Select::make('ramo')
                ->label('Selecione o Ramo do Produto')
                ->options([
                    'Auto' => 'Auto',
                    'Residencial' => 'Residencial',
                    'Vida' => 'Vida',
                ])
                ->live()
                ->required()
                ->dehydrated(false),

            Forms\Components\Select::make('user_id')
                ->label('Corretor Responsável')
                ->relationship('user', 'name')
                ->default(fn () => Auth::id())
                ->disabled()
                ->dehydrated()
                ->required(),

            Forms\Components\Select::make('filial_id')
                ->label('Filial')
                ->relationship('filial', 'nome')
                ->default(function () {
                    /** @var \App\Models\User $user */
                    $user = Auth::user();
                    return $user?->filiais()->first()?->id;
                })
                ->disabled()
                ->dehydrated()
                ->required(),
        ];
    }
    // =========================================================================
    // DADOS ESPECIFICOS (Auto, Vida, Residencial)
    // =========================================================================

    //AUTO
    private static function getAutoSchema(): Forms\Components\Component
    {
        return Forms\Components\Group::make()
            ->visible(function (Get $get) {
                $ramo = $get('../ramo');
                if (! $ramo) return false;
                return $ramo === 'Auto';
            })
            ->schema([
                Forms\Components\Section::make('O Veículo')
                    ->icon('heroicon-o-truck')
                    ->schema([
                        Forms\Components\Group::make()->schema([
                            Forms\Components\TextInput::make('placa')
                                ->label('Placa do Veículo')
                                // Fica desabilitado (cinza e não clicável) se 'sem_placa' for true
                                ->disabled(fn (\Filament\Forms\Get $get): bool => $get('sem_placa') === true)
                                // É obrigatório apenas se 'sem_placa' for false ou nulo
                                ->required(fn (\Filament\Forms\Get $get): bool => ! $get('sem_placa'))
                                // IMPORTANTE: Campos disabled não são enviados ao banco por padrão. 
                                // O dehydrated garante que ele envie 'null' para o banco caso esteja desabilitado.
                                ->dehydrated(), 
                            
                            Forms\Components\Checkbox::make('sem_placa')
                                ->label('Ainda não possui placa')
                                ->live() // O 'live()' é a mágica que faz a tela reagir na mesma hora ao clique
                                ->inline(false),
                        ])->columns(2),
                        Forms\Components\Select::make('tipo_veiculo')->label('Tipo de Veículo')->options(['carro' => 'Carro', 'moto' => 'Moto', 'caminhao' => 'Caminhão'])->required(),
                        Forms\Components\TextInput::make('modelo')->label('Modelo do Veículo')->required(),
                        Forms\Components\TextInput::make('ano')->label('Ano do Veículo')
                            ->label('Ano do Veículo')
                            ->numeric()
                            ->minValue(1900)
                            ->maxValue(now()->year + 1)
                            ->required(),
                        Forms\Components\Group::make()->schema([
                            Forms\Components\Toggle::make('zero')->label('É zero km?')->default(false),
                            Forms\Components\Toggle::make('kit_gas')->label('Possui kit a gas?')->default(false),
                            Forms\Components\Toggle::make('blindado')->label('Blindado?')->default(false),
                            Forms\Components\Toggle::make('imposto')->label('É isento de imposto?')->default(false),
                        ])->columns(4),                           
                    ]),
                Forms\Components\Section::make('Utilização')
                    ->icon('heroicon-o-map-pin')
                    ->schema([
                        Forms\Components\Fieldset::make('dia')
                            ->label('Uso diário')
                            ->schema([
                                Forms\Components\ToggleButtons::make('uso')
                                    ->label('O Veículo é utilizado para alguma das atividades abaixo?')
                                    ->multiple()
                                    ->options(['comercial' => 'Atividade Comercial', 'trabalho' => 'Ir ao Trabalho', 'estudo' => 'Ir a faculdade, escola ou pós-graduação'])
                                    ->live()
                                    ->nullable(),
                                Forms\Components\Select::make('detalhe_uso_comercial')
                                    ->label('Qual o tipo de uso comercial?')
                                    ->visible(fn (Get $get): bool => in_array('comercial', $get('uso') ?? []))
                                    ->options(['visita' => 'Visitar cliente', 'entrega' => 'Fazer entregas', 'motorista_app' => 'Transporte por aplicativo', 'taxi'=>'Táxi', 'outros'=>'Outros'])
                                    ->required(fn (Get $get): bool => in_array('comercial', $get('uso') ?? [])),
                                Forms\Components\ToggleButtons::make('detalhe_uso_trabalho_estudo')
                                    ->label('Durante o trabalho/estudo, fica estacionado onde?')
                                    ->visible(fn (Get $get): bool => in_array('trabalho', $get('uso') ?? []) || in_array('estudo', $get('uso') ?? []))
                                    ->options(['garagem' => 'Garagem', 'rua' => 'Rua', 'estacionamento' => 'Estacionamento'])
                                    ->inline()
                                    ->multiple(),
                            ]),
                        Forms\Components\Fieldset::make('noite')
                            ->label('Onde o veículo fica à noite?')
                            ->schema([
                                Forms\Components\TextInput::make('rua')->label('Rua')->required(),
                                Forms\Components\TextInput::make('numero')->label('Numero')->required(),
                                Forms\Components\TextInput::make('bairro')->required(),
                                Forms\Components\TextInput::make('complemento'),
                                Forms\Components\TextInput::make('cidade')->required(),
                                Forms\Components\TextInput::make('uf')->label('UF')->required()->maxLength(2)->extraAttributes(['style'=>'text-transform: uppercase']),
                                Forms\Components\TextInput::make('CEP')->label('CEP')->required()->mask('99.999-999')->stripCharacters(['.', '-']),
                                Forms\Components\ToggleButtons::make('estacionamento')->label('Em qual local?')->options(['garagem' => 'Garagem', 'rua' => 'Rua', 'estacionamento' => 'Estacionamento']),
                            ]),
                    ]),
                Forms\Components\Section::make('Contrato Anterior')
                    ->icon('heroicon-o-document-text')
                    ->schema([
                        Forms\Components\Toggle::make('seguro_antigo')->label('Já possuo seguro')->default(false)->live(),
                        Forms\Components\Fieldset::make()
                            ->visible(fn (Get $get): bool => $get('seguro_antigo') === true)
                            ->schema([
                                Forms\Components\TextInput::make('seguradora')->label('Seguradora')->required(),
                                Forms\Components\TextInput::make('numero_apolice')->label('Número da Apólice')->required(),
                                Forms\Components\DatePicker::make('data_vencimento')->label('Vigência Fim')->required(),
                                Forms\Components\TextInput::make('classe_bonus')->label('Classe de Bônus')->required(), 
                                Forms\Components\Select::make('uso_anterior')
                                    ->label ('O seguro atual foi utilizado?')
                                    ->options(['nao' => 'Não', 'uma_vez' => 'Uma vez', 'duas_vezes' => 'Duas vezes', 'tres_vezes' => 'Três vezes', 'mais_de_tres_vezes' => 'Mais de três vezes']),
                            ])
                    ]),
            ]);
    }

    //RESIDENCIAL
    private static function getResidencialSchema(): Forms\Components\Component
    {
        return Forms\Components\Group::make()
            ->visible(function (Get $get) {
                $ramo = $get('../ramo');
                if (! $ramo) return false;
                return $ramo === 'Residencial';
            })
            ->schema([
                Forms\Components\Section::make('Dados da Residência')->icon('heroicon-o-home')
                ->schema([
                    Forms\Components\Section::make('Dados do Imóvel')
                        ->icon('heroicon-o-home')
                        ->schema([
                            Forms\Components\Select::make('tipo_moradia')
                                ->label('Tipo de residência')
                                ->required()
                                ->options([
                                    'casa' => 'Casa',
                                    'apartamento' => 'Apartamento',
                                    'condominio_horizontal' => 'Condomínio Horizontal',
                                ])
                                ->live(),
                            Forms\Components\Select::make('detalhe_apartamento')
                                ->label('Tipo de Apartamento')
                                ->visible(fn (Forms\Get $get): bool => $get('tipo_moradia') === 'apartamento')
                                ->options([
                                    'pavimento_terreo' => 'Pavimento Térreo',
                                    'pavimento_superior' => 'Pavimento Superior',
                                    'cobertura' => 'Cobertura',
                                    'sobrado' => 'Sobrado',
                                ]),
                            Forms\Components\Fieldset::make('Endereço do Imóvel')
                                ->schema([
                                    Forms\Components\TextInput::make('rua')->label('Rua')->required(),
                                    Forms\Components\TextInput::make('numero')->label('Número')->required(),
                                    Forms\Components\TextInput::make('bairro')->label('Bairro')->required(),
                                    Forms\Components\TextInput::make('complemento')->label('Complemento'),
                                    Forms\Components\TextInput::make('cidade')->label('Cidade')->required(),
                                    Forms\Components\TextInput::make('uf')->label('UF')->required()->maxLength(2)->extraAttributes(['style'=>'text-transform: uppercase']),
                                    Forms\Components\TextInput::make('cep')->label('CEP')->required()->mask('99.999-999')->stripCharacters(['.', '-']),
                                ]),
                        ]),
                        Forms\Components\Section::make('Detalhamento')
                        ->icon('heroicon-o-home')
                        ->schema([
                            Forms\Components\Select::make('uso_residencia')
                                ->label('Qual é o uso?')
                                ->options([
                                    'habitavel' => 'Habitável',
                                    'veraneio' => 'Veraneio',
                                ]),
                            Forms\Components\Select::make('tipo_construcao')
                                ->label('Qual é o tipo de construção?')
                                ->options([
                                    'madeira' => 'Madeira',
                                    'alvenaria' => 'Alvenaria',
                                ])
                                ->required(),
                            Forms\Components\Select::make('regiao')
                                ->label('Qual o local?')
                                ->required()
                                ->live()
                                ->options([
                                    'urbano' => 'Urbano',
                                    'rural' => 'Rural',
                                ]),
                            Forms\Components\Select::make('agro_comercial')
                                ->label('Há atividades agropecuárias de fins comerciais?')
                                ->visible(fn (Forms\Get $get): bool => $get('regiao') === 'rural')
                                ->options([
                                    'com_agro_comercial' => 'Sim',
                                    'sem_agro_comercial' => 'Não',
                                ]),
                            Forms\Components\ToggleButtons::make('sobre_imovel')
                                    ->label('O imóvel é:')
                                    ->multiple()
                                    ->options(['proprio' => 'Próprio', 'alugado'=>'Alugado','desocupado' => 'Desocupado'])
                                    ->live()
                                    ->nullable(),
                            Forms\Components\Select::make('terreno_baldio')
                                ->label('Faz divisa com terreno baldio ou área descampadas?')
                                ->options(['sim'=>'Sim','nao'=>'Não'])
                                ->required(),
                            Forms\Components\TextInput::make('valor_imovel')
                                ->label('Qual é o valor do imóvel?')
                                ->numeric()
                                ->prefix('R$')
                                ->required(),
                            Forms\Components\Select::make('sinistros')
                                ->label('Houve sinistros?')
                                ->options(['nao'=>'Não','uma_vez'=>'Sim, uma vez','duas_vezes'=>'Sim, duas vezes','tres_mais'=>'Sim, três ou mais vezes'])
                                ->required(),
                            
                        ])
                ]),
            ]);
    }


    //VIDA
    private static function getVidaSchema(): Forms\Components\Component
    {
        return Forms\Components\Group::make()
            ->visible(function (Get $get) {
                $ramo = $get('../ramo');
                if (! $ramo) return false;
                return $ramo === 'Vida';
            })
            ->schema([
                Forms\Components\Section::make('Dados do Segurado')
                    ->icon('heroicon-o-user')
                    ->schema([
                        //FIELDS
                    ]),
            ]);
    }

    // =========================================================================
    // COBERTURAS
    // =========================================================================

    private static function getCoberturasAutoSchema(): Forms\Components\Component
    {
        return Forms\Components\Group::make()
            ->visible(function (Get $get) {
                $ramo = $get('../ramo');
                if (! $ramo) return false;
                return $ramo === 'Auto';
            })
            ->schema([
                Forms\Components\ToggleButtons::make('produto_id')
                    ->label('Selecione um plano')
                    ->options(function (Forms\Get $get) {
                        $ramoEscolhido = $get ('../ramo');
                        if (! $ramoEscolhido) return[];

                        return \App\Models\Produto::where('ramo', $ramoEscolhido)
                            ->where('status', true)
                            ->pLuck('nome','id');
                    })
                    ->inline()
                    ->live()
                    ->required()
                    ->afterStateUpdated(function (Forms\Set $set, $state){
                        if (! $state) {
                            $set ('coberturas_selecionadas', []);
                             return;
                        }

                        $produto = \App\Models\Produto::with('coberturas')->find($state);

                        if ($produto && $produto->coberturas->isNotEmpty()) {

                           $coberturasFormatadas = $produto->coberturas->map(function ($cobertura) {
                                return [
                                    'cobertura_id' => $cobertura->id, 
                                    'nome_cobertura' => $cobertura->nome, 
                                    'limite_maximo' => $cobertura->pivot->limite_maximo, 
                                ];
                            })->toArray(); 

                            $set('coberturas_selecionadas', $coberturasFormatadas);
                            
                        } else {
                            $set('coberturas_selecionadas', []);
                        }
                    }),

                Forms\Components\Repeater::make('coberturas_selecionadas')
                    ->label('Coberturas da Cotação')
                    ->schema([
                        // Campo invisível, serve só para guardar o ID no banco na hora de salvar
                        Forms\Components\Hidden::make('cobertura_id'), 

                        // Mostra o nome, mas bloqueia para o corretor não alterar a regra do produto
                        Forms\Components\TextInput::make('nome_cobertura')
                            ->label('Cobertura')
                            ->disabled(),
                            
                        // Campo livre para o corretor ajustar o LMI se for necessário
                        Forms\Components\TextInput::make('limite_maximo')
                            ->label('Limite Máximo de Indenização (R$)')
                            ->numeric()
                            ->prefix('R$')
                            ->required(),
                    ])
                    ->columns(2)
                    ->defaultItems(0)
                    // Travas de segurança opcionais, mas recomendadas:
                    ->addable(false) // Impede de criar linhas vazias
                    ->deletable(false) // Impede de apagar as coberturas obrigatórias do plano
            ]);
    }

    private static function getCoberturasResidencialSchema(): Forms\Components\Component
    {
        return Forms\Components\Group::make()
            ->visible(function (Get $get) {
                $ramo = $get('../ramo');
                if (! $ramo) return false;
                return $ramo === 'Residencial';
            })
            ->schema([
                Forms\Components\ToggleButtons::make('produto_id')
                    ->label('Selecione um plano')
                    ->options(function (Forms\Get $get) {
                        $ramoEscolhido = $get ('../ramo');
                        if (! $ramoEscolhido) return[];

                        return \App\Models\Produto::where('ramo', $ramoEscolhido)->pLuck('nome','id');
                    })
                    ->inline()
                    ->live()
                    ->required()
                    ->afterStateUpdated(function (Forms\Set $set, $state){
                        if (! $state) {
                            $set ('coberturas_selecionadas', []);
                            return;
                        }

                        $produto = \App\Models\Produto::find($state);

                        if ($produto) {
                           if (!empty($produto->coberturas) && is_array($produto->coberturas)) {
                                $set('coberturas_selecionadas', $produto->coberturas);
                            } else {
                                $set('coberturas_selecionadas', []);
                            }
                        }
                    }),

                Forms\Components\Repeater::make('coberturas_selecionadas')
                    ->label('Coberturas da Cotação')
                    ->schema([
                        Forms\Components\Select::make('cobertura_id')
                            ->label('Cobertura')
                            ->options([
                                'danos_eletricos' => 'Danos Elétricos',
                                'incendio_raio_explosao' => 'Incêncio, raio e explosão',
                                'roubo_furto' => 'Roubo e Furto',
                                'resp_civil' => 'Responsabilidade Civil',
                                'danos_vidros' => 'Danos a Vidros',
                                'impacto_veiculo' => 'Impacto de auto ou avião',
                                'vendaval_granizo_tornado' => 'Vendaval, granizo e tornado',
                            ]) 
                            ->required(),
                        Forms\Components\TextInput::make('lmi')
                            ->label('Limite Máximo de Indenização (R$)')
                            ->numeric()
                            ->prefix('R$')
                            ->required(),
                    ])
                    ->columns(2)
                    ->defaultItems(0)
            ]);
    }

    private static function getCoberturasVidaSchema(): Forms\Components\Component
    {
        return Forms\Components\Group::make()
            ->visible(function (Get $get) {
                $ramo = $get('../ramo');
                if (! $ramo) return false;
                return $ramo === 'Vida';
            })
            ->schema([
                Forms\Components\ToggleButtons::make('produto_id')
                    ->label('Selecione um plano')
                    ->options(function (Forms\Get $get) {
                        $ramoEscolhido = $get ('../ramo');
                        if (! $ramoEscolhido) return[];

                        return \App\Models\Produto::where('ramo', $ramoEscolhido)->pLuck('nome','id');
                    })
                    ->inline()
                    ->live()
                    ->required()
                    ->afterStateUpdated(function (Forms\Set $set, $state){
                        if (! $state) {
                            $set ('coberturas_selecionadas', []);
                            return;
                        }

                        $produto = \App\Models\Produto::find($state);

                        if ($produto) {
                           if (!empty($produto->coberturas) && is_array($produto->coberturas)) {
                                $set('coberturas_selecionadas', $produto->coberturas);
                            } else {
                                $set('coberturas_selecionadas', []);
                            }
                        }
                    }),

                Forms\Components\Repeater::make('coberturas_selecionadas')
                    ->label('Coberturas da Cotação')
                    ->schema([
                        Forms\Components\Select::make('cobertura_id')
                            ->label('Cobertura')
                            ->options([
                                'morte' => 'Morte (qualquer causa)',
                                'invalidez_permanente_total' => 'Invalidez permanente total por acidente',
                                'dobro_morte_acidental' => 'Indenização em Dobro por Morte Acidental',
                                'antecipacao_doenca' => 'Antecipação Especial por Doença',
                                'diaria_incapacidade_temp' => 'Diárias por Incapacidade Temporária',
                                'despesa_hospitalar_odonto' => 'Despesas Médico-hospitalares e Odontológicas',
                                'assistencia_funeral' => 'Assistência Funeral',
                                'invalidez_pernamente' => 'Invalidez permanente por acidente',
                            ]) 
                            ->required(),
                        Forms\Components\TextInput::make('lmi')
                            ->label('Limite Máximo de Indenização (R$)')
                            ->numeric()
                            ->prefix('R$')
                            ->required(),
                    ])
                    ->columns(2)
                    ->defaultItems(0)
            ]);
    }

    private static function getStatusValidadeSchema(): array
    {
        return [
            Forms\Components\Select::make('status')
                ->label('Status da Cotação')
                ->options(['Em Elaboração' => 'Em Elaboração', 'Enviado ao Cliente' => 'Enviado ao Cliente', 'Aceita' => 'Aceita', 'Recusada' => 'Recusada', 'Expirada' => 'Expirada'])
                ->default('Em Elaboração'),
            Forms\Components\DatePicker::make('validade')
                ->label('Validade da Cotação')
                ->required()
                ->default(now()->addDays(30))
                ->minDate(now()),
        ];
    }

    // =========================================================================
    // CONFIGURAÇÃO DA TABELA (Listagem)
    // =========================================================================

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->label('Cotação')->weight(\Filament\Support\Enums\FontWeight::Bold)->description(fn ($record) => $record->produto->nome)->sortable(),
                Tables\Columns\TextColumn::make('user.name')->label('Corretor Responsável'),
                Tables\Columns\TextColumn::make('identificacao_segurado')->label('Cliente')
                    ->state(fn (Cotacao $record) => $record->segurado?->tipo === 'PF' ? $record->segurado?->seguradoPf?->nome : $record->segurado?->seguradoPj?->razao_social),
                Tables\Columns\TextColumn::make('status')->badge()
                    ->colors(['info' => 'Em Elaboração', 'success' => 'Aceita', 'danger' => 'Recusada', 'warning' => 'Enviado ao Cliente', 'primary' => 'Expirada']),
            ])
            ->filters([])->actions([Tables\Actions\EditAction::make()])->bulkActions([Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getRelations(): array { return []; }
    public static function getPages(): array { return ['index' => Pages\ListCotacaos::route('/'), 'create' => Pages\CreateCotacao::route('/create'), 'edit' => Pages\EditCotacao::route('/{record}/edit')]; }
}