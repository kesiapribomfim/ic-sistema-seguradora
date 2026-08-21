<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CotacaoResource\Pages;
use App\Models\Cotacao;
use Filament\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Filament\Forms\Get;
use Filament\Forms\Components\Wizard;
use Illuminate\Support\Facades\App;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Facades\Auth;
use Filament\Infolists\Components\Tabs;
use Illuminate\Database\Eloquent\Model;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Notifications\Notification;

class CotacaoResource extends Resource
{
    protected static ?string $model = Cotacao::class;
    protected static ?string $modelLabel = 'Cotação';
    protected static ?string $pluralModelLabel = 'Cotações';
    protected static ?string $slug = 'cotacoes';
    protected static ?string $navigationIcon = 'heroicon-o-calculator';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Wizard::make([
                    Wizard\Step::make('Cliente e Produto')
                        ->schema(self::getClienteRamoSchema()),

                    Wizard\Step::make('Dados Específicos')
                        ->statePath('dados_especificos')
                        ->schema([
                            self::getAutoSchema(),
                            self::getResidencialSchema(),
                            self::getVidaSchema(),
                        ]),

                    Wizard\Step::make('Coberturas')
                        ->schema([
                            self::getCoberturasSchema(),
                        ]),

                    Wizard\Step::make('Validade')
                        ->schema([
                            self::getResumoSchema()
                        ]),

                ])->columnSpanFull(),
            ]);
    }

    public static function getEloquentQuery(): Builder
    {
        $query = parent::getEloquentQuery();
        $user = auth()->user();

        // Permissão super_admin e Admin
        if ($user->hasRole(['super_admin', 'Administrador Geral'])) {
            return $query;
        }

        // Permissão Corretor (restrita a seu id)
        if ($user->hasRole('Corretor')) {
            return $query->where('user_id', $user->id);
        }

        // TODO: Relacionar Cliente User
        if ($user->hasRole('Cliente')) {
            // ... sua lógica de cliente
        }

        // Analista, Gestor e Subscritor ligado a filial
        $filiaisIds = $user->filiais()->pluck('filiais.id');
        
        // CORREÇÃO AQUI: Filtra a filial DIRETAMENTE na tabela de cotações!
        return $query->whereIn('filial_id', $filiaisIds);
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
            Forms\Components\ToggleButtons::make('ramo')
                ->label('Selecione o Ramo do Produto')
                ->options([
                    'Auto' => 'Auto',
                    'Residencial' => 'Residencial',
                    'Vida' => 'Vida',
                ])
                ->live()
                ->inline()
                ->required()
                ->dehydrated(false)
                ->afterStateHydrated(function (Forms\Components\ToggleButtons $component, $record) {
                    if ($record) {
                        $component->state($record->produto?->ramo);
                    }
                })
                ->afterStateUpdated(function (Forms\Set $set) {
                    $set('produto_id', null);
                    $set('cobertura_selecionada', []);
                }),

            Forms\Components\Select::make('produto_id')
                ->label('Selecione o produto')
                ->options(function (Forms\Get $get) {
                    $ramoEscolhido = $get('ramo');
                    if (! $ramoEscolhido) return [];

                    return \App\Models\Produto::where('ramo', $ramoEscolhido)
                        ->where('status', true)
                        ->pluck('nome','id');
                })
                ->live()
                ->required()
                ->afterStateUpdated(function (Forms\Set $set, $state) {
                    if (! $state) {
                        $set('cobertura_selecionada', []); 
                         return;
                    }

                    $produto = \App\Models\Produto::with('coberturas')->find($state);

                    if ($produto && $produto->coberturas->isNotEmpty()) {
                       $coberturasFormatadas = [];
                       
                       foreach ($produto->coberturas as $cobertura) {
                           $uuid = (string) \Illuminate\Support\Str::uuid();
                           $isObrigatoria = (bool) $cobertura->pivot->obrigatoria;
                           $coberturasFormatadas[$uuid] = [
                               'cobertura_id' => $cobertura->id, 
                               'nome_cobertura' => $cobertura->nome, 
                               'limite_maximo'  => $cobertura->pivot->limite_maximo,
                               'obrigatoria'    => $isObrigatoria, 
                               'contratada'     => true,
                           ];
                       }
                        $set('cobertura_selecionada', $coberturasFormatadas);
                    } else {
                        $set('cobertura_selecionada', []);
                    }
                }),
                Forms\Components\Fieldset::make('Informações do Sistema')
                ->schema([
                    Forms\Components\Placeholder::make('status_visual')
                        ->label('Status da Cotação')
                        ->content(function ($record) {
                            $status = $record ? $record->status : 'Em Elaboração';
                            
                            $cor = match($status) {
                                'Em Elaboração' => '#f59e0b',      // Corresponde ao 'info' (Azul)
                                'Enviada ao Cliente' => '#3b82f6', // Corresponde ao 'warning' (Laranja)
                                'Em Subscrição' => '#eb84e6',
                                'Aceita' => '#10b981',             // Corresponde ao 'success' (Verde)
                                'Recusada' => '#ef4444',           // Corresponde ao 'danger' (Vermelho)
                                'Expirada' => '#6b7280',           // Corresponde ao 'gray' (Cinza)
                                default => '#6b7280',              
                            };
                            
                            return new \Illuminate\Support\HtmlString(
                                "<span style='color: {$cor}; font-weight: bold;'>{$status}</span>"
                            );
                        }),

                    Forms\Components\Hidden::make('status')
                        ->default('Em Elaboração'),
                        
                    Forms\Components\Hidden::make('user_id')
                        ->default(fn () => \Illuminate\Support\Facades\Auth::id()),

                    Forms\Components\Placeholder::make('user_visual')
                        ->label('Corretor Responsável')
                        ->content(function ($record) {
                            return $record ? $record->user?->name : \Illuminate\Support\Facades\Auth::user()->name;
                        }),

                    Forms\Components\Placeholder::make('filial_visual')
                        ->label('Filial')
                        ->content(function ($record) {
                            if ($record) {
                                return $record->filial ? $record->filial->nome : 'Nenhuma filial vinculada';
                            }

                            $user = \Illuminate\Support\Facades\Auth::user();
                            $filial = $user->filiais
                                ->where('pivot.perfil_acesso', 'Corretor')
                                ->first();
                                
                            return $filial ? $filial->nome :  new \Illuminate\Support\HtmlString(
                                '<span style="color: #ef4444; font-weight: bold;">⚠️ Nenhuma filial vinculada ao corretor</span>'
                            );
                        }),

                    Forms\Components\Hidden::make('filial_id')
                        ->default(function(){
                            $user = \Illuminate\Support\Facades\Auth::user();
                            
                            $filial = $user->filiais
                                ->where('pivot.perfil_acesso', 'Corretor')
                                ->first();
                                
                            return $filial?->id;
                        }),
                ])->columns(2),
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
                                ->disabled(fn (\Filament\Forms\Get $get): bool => $get('sem_placa') === true)
                                ->required(fn (\Filament\Forms\Get $get): bool => ! $get('sem_placa'))
                                ->dehydrated(), 
                            
                            Forms\Components\Checkbox::make('sem_placa')
                                ->label('Ainda não possui placa')
                                ->live() // O 'live()' é a mágica que faz a tela reagir na mesma hora ao clique
                                ->inline(false),
                        ])->columns(2),
                        Forms\Components\Select::make('tipo_veiculo')
                            ->label('Tipo de Veículo')
                            ->options(['carro' => 'Carro', 'moto' => 'Moto', 'caminhao' => 'Caminhão'])
                            ->required(),
                        Forms\Components\TextInput::make('modelo')
                            ->label('Modelo do Veículo')
                            ->required(),
                        Forms\Components\TextInput::make('ano')
                            ->label('Ano do Veículo')
                            ->numeric()
                            ->minValue(1900)
                            ->maxValue(now()->year + 1)
                            ->required(),
                        Forms\Components\TextInput::make('valor_base_risco')
                            ->label('Valor do Veículo (Ref. FIPE)')
                            ->numeric()
                            ->live(onBlur: true) // envia requisição HTTP apenas quando o usuário para de digitar
                            ->prefix('R$')
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
                                Forms\Components\TextInput::make('uf')
                                    ->label('UF')
                                    ->required()
                                    ->maxLength(2)
                                    ->extraAttributes(['style'=>'text-transform: uppercase']),
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
                            Forms\Components\TextInput::make('valor_base_risco')
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
    private static function getPerguntasSaudeSchema(): array
    {
        return [
            Forms\Components\Grid::make(2)
                ->schema([
                    Forms\Components\Toggle::make('possui_doenca_preexistente')
                        ->label('O proponente possui alguma doença preexistente?')
                        ->live()
                        ->columnSpanFull(),

                    Forms\Components\CheckboxList::make('doencas_diagnosticadas')
                        ->label('Selecione as condições já diagnosticadas')
                        ->options([
                            'cancer' => 'Câncer / Tumores',
                            'avc' => 'Acidente Vascular Cerebral (AVC)',
                            'infarto' => 'Infarto Agudo do Miocárdio',
                            'alzheimer' => 'Doença de Alzheimer',
                            'parkinson' => 'Mal de Parkinson',
                            'esclerose_multipla' => 'Esclerose Múltipla',
                            'osteomielite' => 'Osteomielite',
                            'embolia_pulmonar' => 'Embolia Pulmonar',
                            'outras' => 'Outras Condições (Especificar abaixo)',
                        ])
                        ->columns(2)
                        ->default([])
                        ->visible(fn (Forms\Get $get) => $get('possui_doenca_preexistente') === true),

                    Forms\Components\Textarea::make('detalhes_saude')
                                    ->label('Detalhes Adicionais do Histórico Médico')
                                    ->placeholder('Informe a data do diagnóstico, tratamentos realizados, uso contínuo de medicamentos, etc.')
                                    ->visible(fn (Forms\Get $get) => $get('possui_doenca_preexistente') === true)
                                    ->columnSpanFull(),

                                // Fatores de risco (Essenciais para a subscrição de Vida)
                                Forms\Components\Fieldset::make('Hábitos e Fatores de Risco')
                                    ->schema([
                                        Forms\Components\Toggle::make('fumante')
                                            ->label('Fumante?'),
                                            
                                        Forms\Components\Toggle::make('consome_alcool')
                                            ->label('Consome bebida alcoólica?'),
                                            
                                        Forms\Components\Toggle::make('pratica_esportes_radicais')
                                            ->label('Pratica esportes radicais?'),
                                    ])->columns(3),
                ])
        ];
    }

    private static function getVidaSchema(): Forms\Components\Component
    {
        return Forms\Components\Group::make()
            ->visible(function (Forms\Get $get) {
                $ramo = $get('../ramo');
                if (! $ramo) return false;
                return $ramo === 'Vida';
            })
            ->dehydrated(fn (Forms\Get $get) => $get('../ramo') === 'Vida')
            ->schema([
                // ---------------------------------------------------------
                // 1. DADOS DO TITULAR
                // ---------------------------------------------------------
                Forms\Components\Section::make('Dados de Saúde do Titular')
                    ->icon('heroicon-o-user')
                    ->schema([
                        Forms\Components\Tabs::make('TabsTitular')
                            ->tabs([
                                Forms\Components\Tabs\Tab::make('Dados Pessoais')
                                    ->icon('heroicon-o-users')
                                    ->schema([
                                        Forms\Components\TextInput::make('valor_base_risco')
                                            ->label('Capital Segurado (R$)')
                                            ->helperText('Valor principal da cobertura do titular')
                                            ->numeric()
                                            ->prefix('R$')
                                            ->required()
                                            ->live(onBlur: true),
                                        //puxar o nome e data de nascimento direto do banco de dados
                                        // Forms\Components\Placeholder::make('nome')
                                        //     ->label('Nome Completo')
                                        //     ->live(),
                                        // Forms\Components\Placeholder::make('data_nascimento')
                                        //     ->label('Data de Nascimento')
                                        //     ->live(),
                                        Forms\Components\Grid::make(2)->schema([
                                            Forms\Components\TextInput::make('peso')
                                                ->label('Peso (kg)')
                                                ->numeric()
                                                ->required(),
                                                
                                            Forms\Components\TextInput::make('altura')
                                                ->label('Altura (cm)')
                                                ->numeric()
                                                ->required(),
                                        ]),

                                        Forms\Components\Select::make('profissao_risco')
                                            ->label('Profissão de Risco?')
                                            ->options(['nao' => 'Não', 'sim' => 'Sim'])
                                            ->required(),
                                    ]) 
                                    ->columns(2),

                                Forms\Components\Tabs\Tab::make('Saúde')
                                    ->icon('heroicon-o-heart')
                                    ->schema(self::getPerguntasSaudeSchema())
                                    ->columns(2),
                            ])
                    ]),

                // ---------------------------------------------------------
                // 2. DEPENDENTES (Repeater, sem repetir o titular)
                // ---------------------------------------------------------
                Forms\Components\Repeater::make('dependentes_vida')
                    ->label('Dependentes do Plano')
                    ->schema([
                        Forms\Components\Tabs::make('TabsDependente')
                            ->tabs([
                                Forms\Components\Tabs\Tab::make('Dados Pessoais')
                                    ->icon('heroicon-o-users')
                                    ->schema([
                                        Forms\Components\TextInput::make('nome')
                                            ->label('Nome Completo')
                                            ->required(),
                                            
                                        Forms\Components\Select::make('parentesco')
                                            ->options([
                                                'conjuge' => 'Cônjuge/Companheiro(a)',
                                                'filho' => 'Filho(a)/Enteado(a)',
                                            ])
                                            ->live()
                                            ->required(),

                                        Forms\Components\DatePicker::make('data_nascimento')
                                            ->label('Data de Nascimento')
                                            ->required()
                                            ->rules([
                                                fn (Forms\Get $get) => function (string $attribute, $value, \Closure $fail) use ($get) {
                                                    $parentesco = $get('parentesco');
                                                    if ($parentesco === 'filho') {
                                                        $idade = \Carbon\Carbon::parse($value)->age;
                                                        if ($idade > 21) {
                                                            $fail('Filhos e enteados dependentes devem ter no máximo 21 anos.');
                                                        }
                                                    }
                                                },
                                            ]),
                                        Forms\Components\Grid::make(2)->schema([
                                            Forms\Components\TextInput::make('peso')
                                                ->label('Peso (kg)')
                                                ->numeric()
                                                ->required(),
                                                
                                            Forms\Components\TextInput::make('altura')
                                                ->label('Altura (cm)')
                                                ->numeric()
                                                ->required(),
                                        ]),
                                    ])->columns(2),

                                Forms\Components\Tabs\Tab::make('Saúde do Dependente')
                                    ->icon('heroicon-o-heart')
                                    ->schema(self::getPerguntasSaudeSchema()) 
                                    ->columns(2),
                            ])
                    ])
                    ->defaultItems(0) 
                    ->addActionLabel('Adicionar Dependente')
                    ->collapsible()
                    ->itemLabel(fn (array $state): ?string => $state['nome'] ?? null),

                // ---------------------------------------------------------
                // 3. BENEFICIÁRIOS (Repeater Manual Simples)
                // ---------------------------------------------------------
                Forms\Components\Section::make('Destinação da Indenização')
                    ->schema([
                        Forms\Components\Repeater::make('beneficiarios_vida')
                            ->label('Lista de Beneficiários')
                            ->schema([
                                Forms\Components\TextInput::make('nome')->required(),
                                Forms\Components\TextInput::make('cpf')
                                    ->label('CPF')
                                    ->mask('999.999.999-99'),
                                Forms\Components\TextInput::make('parentesco')
                                    ->label('Parentesco')
                                    ->required(),
                                Forms\Components\TextInput::make('percentual_rateio')
                                    ->label('Rateio (%)')
                                    ->numeric()
                                    ->suffix('%')
                                    ->maxValue(100)
                                    ->required(),
                                // TODO: Adicionar lógica para barrar soma total menor que 100%
                            ])
                            ->columns(3)
                            ->defaultItems(1)
                            ->helperText('A soma dos percentuais deve totalizar 100%.')
                            ->rule(function () {
                                return function (string $attribute, $value, \Closure $fail) {
                                    $totalPercentual = collect($value)->sum('percentual_rateio');

                                    if ($totalPercentual !== 100 && $totalPercentual !== 100.0) {
                                        $fail("A soma dos rateios deve ser exatamente 100%. (Total atual: {$totalPercentual}%)");
                                    }
                                };
                            }),
                    ]),
            ]);
    }

    // =========================================================================
    // COBERTURAS
    // =========================================================================

    // TODO: Adicionar campo com a listagem das coberturas opcionais que podem ser adicionadas a depender do plano
    private static function getCoberturasSchema(): Forms\Components\Component
    {
        return Forms\Components\Group::make()
            ->visible(fn (Forms\Get $get) => filled(($get('ramo'))))
            ->schema([

                Forms\Components\Repeater::make('cobertura_selecionada')
                    ->label('Coberturas Disponíveis no Plano')
                    ->schema([
                        Forms\Components\Hidden::make('cobertura_id'), 
                        Forms\Components\Hidden::make('obrigatoria'), 

                        // O GATILHO DE CONTRATAÇÃO
                        Forms\Components\Toggle::make('contratada')
                            ->label(fn (Forms\Get $get) => $get('obrigatoria') ? 'Obrigatória' : 'Opcional')
                            ->live()
                            ->afterStateHydrated(function (Forms\Components\Toggle $component, $state, Forms\Get $get) {
                                // Se a cobertura for obrigatória, ela nasce ligada independentemente de qualquer coisa
                                if ($get('obrigatoria')) {
                                    $component->state(true);
                                } 
                                // Se não for obrigatória e o estado estiver vazio (novo form), nasce desligada
                                elseif ($state === null || $state === '') {
                                    $component->state(false);
                                }
                            })
                            ->disabled(fn (Forms\Get $get) => (bool) $get('obrigatoria'))
                            ->dehydrated(),

                        Forms\Components\TextInput::make('nome_cobertura')
                            ->label('Cobertura')
                            ->readOnly(),
                            
                        Forms\Components\TextInput::make('limite_maximo')
                            ->label('Limite Máximo (R$)')
                            ->numeric()
                            ->prefix('R$')
                            ->disabled(fn (Forms\Get $get) => $get('contratada') === false)
                            ->required(fn (Forms\Get $get) => $get('contratada') === true)
                            ->dehydrated(),
                    ])
                    ->columns(3)
                    ->defaultItems(0)
                    ->addable(false)
                    ->deletable(false)
                    ->reorderable(false),
            ]);
    }

    // =========================================================================
    // RESUMO E FINALIZAÇÃO
    // =========================================================================

    private static function getResumoSchema(): Forms\Components\Component
    {
        return Forms\Components\Group::make()
            ->schema([
                Forms\Components\Section::make('Revisão dos Dados')
                    ->schema([
                        Forms\Components\Placeholder::make('resumo_cliente')
                            ->label('Cliente Selecionado')
                            ->content( function (Forms\Get $get){
                                $seguradoId = $get('segurado_id');

                                if (! $seguradoId) {
                                    return 'Nenhum cliente selecionado';
                                }

                                $cliente = \App\Models\Segurado::with(['seguradoPf','seguradoPj'])->find($seguradoId);

                                if(! $cliente){
                                    return 'Erro ao buscar cliente';
                                }
                                if($cliente->tipo === 'PF'){
                                    return "{$cliente->seguradoPf?->nome} (CPF:{$cliente->seguradoPf->cpf})";
                                } else{
                                    return "{$cliente->seguradoPj?->razao_social} (CNPJ:{$cliente->seguradoPj->cnpj})";
                                }

                            }),

                        // Mostra o Produto
                        Forms\Components\Placeholder::make('resumo_produto')
                            ->label('Plano Escolhido')
                            ->content(function (Forms\Get $get) {
                                if (!$get('produto_id')) return 'Nenhum plano selecionado';
                                $produto = \App\Models\Produto::find($get('produto_id'));
                                return $produto ? $produto->nome : 'Erro ao buscar';
                            }),
                    ])->columns(2),

                Forms\Components\Section::make('Finalização')
                    ->schema([
                        Forms\Components\Placeholder::make('premio_calculado_visual')
                            ->label('Prêmio Total Calculado')
                            ->content(function (Forms\Get $get, $livewire) {
                                $produtoId = $get('produto_id');
                                $seguradoId = $get('segurado_id');

                                if (!$produtoId || !$seguradoId) {
                                    return new \Illuminate\Support\HtmlString('<span style="color: #6b7280; font-style: italic;">Preencha o cliente e o produto nas etapas anteriores.</span>');
                                }

                                $produto = \App\Models\Produto::find($produtoId);
                                $segurado = \App\Models\Segurado::find($seguradoId);
                                
                                // Pega tudo que o corretor digitou até agora
                                $dadosDoFormulario = $livewire->form->getRawState();
                                
                                // Roda o serviço em tempo real para exibir
                                $calculadora = new \App\Services\CalculadoraPremioService();
                                $premioFinal = $calculadora->calcular($produto, $dadosDoFormulario, $segurado);

                                return new \Illuminate\Support\HtmlString(
                                    '<span style="font-size: 1.5rem; font-weight: bold; color: #10b981;">R$ ' . number_format($premioFinal, 2, ',', '.') . '</span>'
                                );
                            }),

                        Forms\Components\Hidden::make('valor_total')
                            ->dehydrateStateUsing(function (Forms\Get $get, $livewire) {
                                $produtoId = $get('produto_id');
                                $seguradoId = $get('segurado_id');

                                // Se faltar dados, salva zerado para não dar erro de null no banco
                                if (!$produtoId || !$seguradoId) return 0.0; 

                                $produto = \App\Models\Produto::find($produtoId);
                                $segurado = \App\Models\Segurado::find($seguradoId);
                                
                                // Pega os dados exatos do formulário no momento do clique em "Salvar"
                                $dadosDoFormulario = $livewire->form->getRawState();
                                
                                // Roda o cálculo e injeta o valor direto na coluna do banco!
                                $calculadora = new \App\Services\CalculadoraPremioService();
                                return $calculadora->calcular($produto, $dadosDoFormulario, $segurado);
                            }),

                        Forms\Components\Placeholder::make('info_alcada')
                            ->label('Informações de Subscrição')
                            ->content(function (Forms\Get $get) {
                                $produtoId = $get('produto_id');
                                if (!$produtoId) return 'Selecione um plano para visualizar os limites.';

                                $produto = \App\Models\Produto::find($produtoId);
                                
                                // Puxa o limite da alçada do banco (ou avisa se for ilimitado)
                                $limiteAlcada = $produto->valor_alcada 
                                    ? 'R$ ' . number_format($produto->valor_alcada, 2, ',', '.') 
                                    : 'Sem Limite (Aprovação Automática)';

                                // Faz a soma silenciosa das coberturas só para mostrar na tela
                                $coberturas = $get('cobertura_selecionada') ?? [];
                                $somaLmi = collect($coberturas)->sum(fn($c) => (float) ($c['limite_maximo'] ?? 0));
                                $riscoTotal = 'R$ ' . number_format($somaLmi, 2, ',', '.');

                                return new \Illuminate\Support\HtmlString(
                                    "<span style='color: #4b5563;'>
                                        <strong>Limite de Alçada do Produto:</strong> {$limiteAlcada} <br>
                                        <strong>Risco Total (Soma das Coberturas):</strong> {$riscoTotal}
                                    </span>"
                                );
                            })
                            ->columnSpanFull(),

                        Forms\Components\DatePicker::make('validade')
                            ->label('Validade da Proposta')
                            ->default(now()->addDays(30)) 
                            ->required(),
                    ]),
            ]);
    }

    // =========================================================================
    // CONFIGURAÇÃO DA TABELA (Listagem)
    // =========================================================================

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(fn (Builder $query) => $query->with([
                'segurado.seguradoPf', 
                'segurado.seguradoPj', 
                'user', 
                'produto', 
                'apolice'
            ]))
            ->columns([
                Tables\Columns\TextColumn::make('identificacao_segurado')
                    ->label('Cliente')
                    ->state(fn (Cotacao $record) => $record->segurado?->tipo === 'PF' ? $record->segurado?->seguradoPf?->nome : $record->segurado?->seguradoPj?->razao_social)
                    ->sortable(),
                    // ->searchable(),
                Tables\Columns\TextColumn::make('user.name')->label('Corretor Responsável')
                    ->sortable(),
                    // ->searchable(),
                Tables\Columns\TextColumn::make('produto.nome')->label('Produto')->sortable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'Em Elaboração' => 'info',
                        'Enviada ao Cliente' => 'warning',
                        'Em Subscrição' => '#ebb284',
                        'Aceita' => 'success',
                        'Recusada' => 'danger',
                        'Expirada' => 'gray',
                        default => 'gray',
                    }),
            ])
            ->recordUrl(null)
            ->recordAction(Tables\Actions\ViewAction::class)
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->label('Status da Cotação')
                    ->options([
                        'Em Elaboração' => 'Em Elaboração',
                        'Enviada ao Cliente' => 'Enviada ao Cliente',
                        'Em Subscrição' => 'Em Subscrição',
                        'Aceita' => 'Aceita',
                        'Recusada' => 'Recusada',
                        'Expirada' => 'Expirada',
                    ]),
                //filtro para pesquisar nome do corretor responsável
                Tables\Filters\SelectFilter::make('user_id')
                    ->label('Corretor Responsável')
                    ->relationship('user', 'name')
                    ->searchable(),
            ]) // TODO: Filters
            ->actions([
                ActionGroup::make([
                    EditAction::make(),
                    ViewAction::make(),

                    //  TODO: Ainda tenho que acertar isso, mas serve por enquanto
                    Tables\Actions\Action::make('emitir_apolice')
                        ->label('Emitir Apólice')
                        ->icon('heroicon-o-check-badge')
                        ->color('success')
                        ->requiresConfirmation()
                        ->modalHeading('Confirmar Aceite e Emissão')
                        ->modalDescription('Tem certeza que deseja converter esta cotação em uma apólice vigente? O pagamento da primeira parcela será registrado automaticamente.')
                        ->modalSubmitActionLabel('Sim, emitir apólice')
                        // Só mostra o botão se a cotação ainda não foi aceita
                        ->visible(fn (Cotacao $record) => in_array($record->status, ['Em Elaboração', 'Enviada ao Cliente']))
                        ->action(function (Cotacao $record, array $data) {
                            
                            // Chama a classe de serviço
                            $servico = new \App\Services\EmissaoApoliceService();
                            $apolice = $servico->emitir(
                                $record,
                                $data['forma_pagamento'],
                                (int) $data['quantidade_parcelas']    
                            );

                            // Mostra notificação de sucesso e redireciona para a nova apólice
                            \Filament\Notifications\Notification::make()
                                ->title('Apólice Emitida com Sucesso!')
                                ->success()
                                ->send();

                            redirect()->to('/admin/apolices/' . $apolice->id . '/view');
                        })
                        ->form([
                            Forms\Components\Select::make('forma_pagamento')
                                ->label('Forma de Pagamento')
                                ->options([
                                    'Cartão de Crédito' => 'Cartão de Crédito',
                                    'Boleto Bancário' => 'Boleto Bancário',
                                    'Pix' => 'Pix',
                                ])
                                ->required(),
                                
                            Forms\Components\Select::make('quantidade_parcelas')
                                ->label('Quantidade de Parcelas')
                                ->options([
                                    1 => '1x (À Vista)',
                                    2 => '2x',
                                    3 => '3x',
                                    4 => '4x',
                                    5 => '5x',
                                    6 => '6x',
                                    7 => '7x',
                                    8 => '8x',
                                    9 => '9x',
                                    10 => '10x',
                                    11 => '11x',
                                    12 => '12x',
                                ])
                                ->required(),
                        ]),
                    // TODO: Modificar para envio do link por email
                    Tables\Actions\Action::make('link_checkout')
                        ->label('Link de Pagamento')
                        ->icon('heroicon-o-link')
                        ->color('info')
                        ->visible(fn (Cotacao $record) => in_array($record->status, ['Em Elaboração', 'Enviada ao Cliente']))
                        ->action(function (Cotacao $record) {
                            $url = \Illuminate\Support\Facades\URL::temporarySignedRoute(
                                'checkout.cotacao', 
                                now()->addDays(30), // O link expira em 30 dias
                                ['cotacao' => $record] //aponta para o uuid das cotações
                            );

                            \Filament\Notifications\Notification::make()
                                ->title('Link Gerado com sucesso!')
                                ->body($url)
                                ->success()
                                ->send();
                        }),
                    Action::make('ver_apolice')
                        ->label('Abrir Apólice')
                        ->icon('heroicon-o-document-check')
                        ->color ('info')
                        ->visible(function (Model $record){
                            return $record->apolice()->exists();
                        })
                        ->url(function (Model $record) {
                            //mudei para edit->view
                            return ApoliceResource::getUrl('view', ['record' => $record->apolice->id]);
                        }),
                ])
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([Tables\Actions\DeleteBulkAction::make()])]);
    }

    public static function getRelations(): array { 
        return []; }
    public static function getPages(): array { 
        return [
            'index' => Pages\ListCotacaos::route('/'), 
            'create' => Pages\CreateCotacao::route('/create'), 
            'view' => Pages\ViewCotacao::route('/{record}/view'),
            'edit' => Pages\EditCotacao::route('/{record}/edit')
        ];
    }
}