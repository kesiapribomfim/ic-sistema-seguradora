<?php

namespace App\Filament\Resources\SinistroResource\RelationManagers;

use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\RelationManagers\RelationManager;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class MovimentacoesRelationManager extends RelationManager
{
    protected static string $relationship = 'movimentacoes';   
    protected static ?string $title = 'Movimentações';
    

    public function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Select::make('acao_realizada')
                    ->label('Ação Realizada')
                    ->options([
                        'Abertura' => 'Abertura de Sinistro',
                        'Análise' => 'Análise de Documentos',
                        'Perícia' => 'Solicitação de Perícia',
                        'Aprovação' => 'Aprovação de Indenização',
                        'Negação' => 'Negação de Cobertura',
                        'Pagamento' => 'Confirmação de Pagamento',
                        'Encerramento' => 'Encerramento do Sinistro',
                    ])
                    ->required()
                    ->columnSpanFull(),

                Forms\Components\Textarea::make('descricao')
                    ->label('Descrição / Parecer')
                    ->required()
                    ->rows(3)
                    ->columnSpanFull(),

                Forms\Components\FileUpload::make('anexos')
                    ->label('Anexos Comprobatórios')
                    ->multiple()
                    ->disk('local') 
                    ->directory('sinistros-anexos')
                    ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png', 'image/webp']) 
                    ->maxSize(5120) 
                    ->columnSpanFull(),
            ]);
    }

    public function table(Table $table): Table
    {
        return $table
            ->recordTitleAttribute('acao_realizada')
            ->columns([
                Tables\Columns\TextColumn::make('data_hr_movimentacao')
                    ->label('Data/Hora')
                    ->dateTime('d/m/Y H:i')
                    ->sortable(),
                    
                Tables\Columns\TextColumn::make('acao_realizada')
                    ->label('Ação')
                    ->badge(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label('Autor')
                    ->description(fn ($record) => $record->user->getRoleNames()->first() ?? 'Usuário'),

                Tables\Columns\TextColumn::make('descricao')
                    ->label('Descrição')
                    ->wrap() // Permite que o texto quebre a linha se for muito grande
                    ->limit(50),
            ])
            ->defaultSort('data_hr_movimentacao', 'desc') // Exibe em ordem cronológica decrescente como exigido!
            ->filters([
                //
            ])
            ->headerActions([
                Tables\Actions\CreateAction::make()
                    ->label('Nova Movimentação')
                    ->icon('heroicon-o-plus')
                    ->mutateFormDataUsing(function (array $data): array {
                        $data['user_id'] = auth()->id();
                        $data['data_hr_movimentacao'] = now();
                        return $data;
                    }),
            ])
            ->actions([
                // Regra da Imutabilidade: Só deixamos a ViewAction! Removemos o EditAction e DeleteAction.
                Tables\Actions\ViewAction::make(), 
            ])
            ->bulkActions([
                //
            ]);
    }
}
