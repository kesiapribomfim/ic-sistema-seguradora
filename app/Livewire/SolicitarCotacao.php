<?php

namespace App\Livewire;

use Livewire\Component;
use Filament\Forms\Concerns\InteractsWithForms;
use Filament\Forms\Contracts\HasForms;
use Filament\Forms\Form;
use Filament\Forms;
use App\Models\Segurado;
use App\Models\Cotacao;
use App\Models\Filial;
use Filament\Notifications\Notification;

class SolicitarCotacao extends Component implements HasForms
{
    use InteractsWithForms;

    public ?array $data = [];

    public function mount(): void
    {
        $this->form->fill();
    }

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Seus Dados')
                    ->description('Para onde enviaremos sua cotação?')
                    ->schema([
                        Forms\Components\ToggleButtons::make('tipo_pessoa')
                            ->label('Tipo de Cliente')
                            ->options(['PF' => 'Pessoa Física', 'PJ' => 'Pessoa Jurídica'])
                            ->default('PF')
                            ->inline()
                            ->live()
                            ->required()
                            ->columnSpanFull(),

                        Forms\Components\TextInput::make('nome')
                            ->label(fn (Forms\Get $get) => $get('tipo_pessoa') === 'PF' ? 'Nome Completo' : 'Razão Social')
                            ->required(),

                        Forms\Components\TextInput::make('documento')
                            ->label(fn (Forms\Get $get) => $get('tipo_pessoa') === 'PF' ? 'CPF' : 'CNPJ')
                            ->mask(fn (Forms\Get $get) => $get('tipo_pessoa') === 'PF' ? '999.999.999-99' : '99.999.999/9999-99')
                            ->required(),

                        Forms\Components\TextInput::make('email')->email()->required(),
                        
                        Forms\Components\TextInput::make('telefone')
                            ->mask('(99) 99999-9999')
                            ->required(),
                    ])->columns(2),

                Forms\Components\Section::make('O que deseja segurar?')
                    ->schema([
                        Forms\Components\Select::make('ramo')
                            ->options([
                                'Auto' => 'Seguro Auto',
                                'Residencial' => 'Seguro Residencial',
                                'Vida' => 'Seguro de Vida',
                            ])
                            ->required()
                            ->live(),
                            
                        Forms\Components\Select::make('filial_id')
                            ->label('Selecione a Filial mais próxima')
                            ->options(function () {
                                return \App\Models\Filial::pluck('nome', 'id')->toArray();
                            })
                            ->searchable()
                            ->required(),
                        Forms\Components\Textarea::make('dados_iniciais')
                            ->label('Detalhes (Ex: Placa do carro, Endereço do imóvel, etc)')
                            ->placeholder('Conte-nos um pouco sobre o que deseja segurar...')
                            ->required()
                            ->columnSpanFull(),
                    ])->columns(2),
            ])
            ->statePath('data');
    }

    public function submit()
    {
        $dados = $this->form->getState();
        $tipo = $dados['tipo_pessoa'];
        
        // Remove pontuações do documento para buscar/salvar limpo
        $documentoLimpo = preg_replace('/[^0-9]/', '', $dados['documento']);

        // 1. Busca o cliente nas tabelas filhas (A MÁGICA RELACIONAL AQUI)
        $segurado = Segurado::whereHas($tipo === 'PF' ? 'seguradoPf' : 'seguradoPj', function ($query) use ($documentoLimpo, $tipo) {
            $coluna = $tipo === 'PF' ? 'cpf' : 'cnpj';
            $query->where($coluna, $documentoLimpo);
        })->first();

        // 2. Se não existir, cria a estrutura completa
        if (!$segurado) {
            // Cria o pai
            $segurado = Segurado::create([
                'tipo' => $tipo,
                'email' => $dados['email'],
                'telefone' => $dados['telefone'],
                'status' => 1,
            ]);

            // Cria o filho correspondente
            if ($tipo === 'PF') {
                $segurado->seguradoPf()->create([
                    'nome' => $dados['nome'],
                    'cpf' => $documentoLimpo,
                ]);
            } else {
                $segurado->seguradoPj()->create([
                    'razao_social' => $dados['nome'],
                    'cnpj' => $documentoLimpo,
                ]);
            }
        }

        // 3. Cria a cotação no status inicial
        Cotacao::create([
            'segurado_id' => $segurado->id,
            'filial_id' => $dados['filial_id'],
            'status' => 'Em Elaboração', 
            'dados_especificos' => ['observacao_cliente' => $dados['dados_iniciais']],
            'ramo' => $dados['ramo'],
            'validade' => now()->addDays(30),
        ]);

        Notification::make()
            ->title('Solicitação Enviada!')
            ->body('Um de nossos corretores da filial selecionada entrará em contato em breve.')
            ->success()
            ->send();

        $this->form->fill();
    }

    public function render()
    {
        return view('livewire.solicitar-cotacao'); 
    }
}