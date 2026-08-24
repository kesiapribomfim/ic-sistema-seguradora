# 🛡️ Relatório de Auditoria e Melhorias

Escopo: descritivo do projeto fornecido e código executável do repositório em 24/08/2026. Esta é uma auditoria estática: não foi executado teste que pudesse alterar a base configurada. A sintaxe dos arquivos críticos e o registro de rotas foram verificados. Os achados de autorização se alinham ao risco A01 (Broken Access Control), ainda o principal risco do OWASP Top 10 2025.

## 1. 🚨 Alertas Críticos de Segurança e LGPD

### P0 — PDF de apólice público e token de redefinição exposto

**Problema.** `routes/web.php` expõe `GET /teste-pdf/{apolice}` sem autenticação ou Policy e devolve, na rota de reset, o token e o e-mail diretamente na resposta. A apólice tem identificador numérico previsível.

**Explicação didática.** Uma URL não protegida é como deixar pastas numeradas num armário sem fechadura: basta testar 1, 2, 3 até encontrar documentos de outro segurado. O token de reset é uma credencial temporária; imprimi-lo na tela faz com que seja copiado por histórico, captura de tela, proxy ou logs.

**Impacto antes da mudança.** A troca elimina a rota de teste e substitui a página de texto por um formulário real de reset. Não altera regras de seguro, mas exige que o PDF passe sempre pela autorização do servidor.

**Solução (código).**

```php
// routes/web.php
use App\Http\Controllers\Auth\NewPasswordController;
use Illuminate\Support\Facades\Gate;

Route::get('/apolices/{apolice}/pdf', function (Request $request, Apolice $apolice) {
    Gate::authorize('view', $apolice);

    return Pdf::loadView('pdf.apolice', compact('apolice'))
        ->download("apolice-{$apolice->numero_apolice}.pdf");
})->middleware(['auth', 'throttle:30,1'])->name('apolices.pdf');

Route::get('/reset-password/{token}', [NewPasswordController::class, 'create'])
    ->middleware('guest')->name('password.reset');
Route::post('/reset-password', [NewPasswordController::class, 'store'])
    ->middleware(['guest', 'throttle:5,1'])->name('password.store');
```

O controller deve usar `Password::reset()` e nunca renderizar o token no HTML além do campo oculto necessário ao POST.

### P0 — Isolamento por filial e carteira não está garantido em todas as camadas

**Problema.** `PagamentoPolicy::view()` e `update()` autorizam somente por papel, sem verificar a filial ou o segurado do pagamento. `PagamentoResource` não sobrescreve `getEloquentQuery()`, portanto a listagem também não limita registros. Além disso, `CotacaoResource` pré-carrega todos os segurados e mostra CPF/CNPJ; `SeguradoPolicy` e `SeguradoResource` consultam `segurados.filial_id`, coluna que não existe.

**Explicação didática.** Um filtro de interface é uma placa de “acesso restrito”; uma Policy é a fechadura. Sem a segunda, alguém pode alterar a URL ou a requisição Livewire e operar dados de outra filial. O erro de coluna ainda torna a proteção inconsistente: ora falha, ora deixa o recurso sem o recorte correto.

**Impacto antes da mudança.** Centralizar o predicado de escopo pode ocultar registros que hoje aparecem indevidamente e exige testes com cada perfil. Isso é desejável: o requisito do projeto é que a filial e a carteira limitem o dado no back-end.

**Solução (código).** Faça a autorização de objeto e a query reutilizarem a mesma regra; exemplo para pagamento:

```php
// app/Policies/PagamentoPolicy.php
public function view(User $user, Pagamento $pagamento): bool
{
    if ($user->hasRole('Cliente')) {
        return $pagamento->apolice->segurado->user_id === $user->id;
    }

    return $user->hasAnyRole(['Gestor de Filial', 'Financeiro'])
        && $user->filiais()->whereKey($pagamento->apolice->filial_id)->exists();
}

public function update(User $user, Pagamento $pagamento): bool
{
    return $user->hasRole('Financeiro') && $this->view($user, $pagamento);
}
```

```php
// app/Filament/Resources/PagamentoResource.php
public static function getEloquentQuery(): Builder
{
    $user = auth()->user();
    $query = parent::getEloquentQuery()->with('apolice.segurado');

    if ($user->hasAnyRole(['super_admin', 'Administrador Geral'])) return $query;
    if ($user->hasRole('Cliente')) {
        return $query->whereHas('apolice.segurado', fn ($q) => $q->where('user_id', $user->id));
    }

    return $query->whereHas('apolice', fn ($q) =>
        $q->whereIn('filial_id', $user->filiais()->select('filiais.id')));
}
```

Para segurados, remova a referência a `segurados.filial_id`: o recorte deve passar por `apolices.filial_id`; para corretor, use `corretor_id`, não `user_id` (que é o acesso do cliente). Em `CotacaoResource`, aplique o mesmo escopo no `relationship('segurado', ...)` e não mostre CPF/CNPJ completo na opção.

### P0 — Perfis “por filial” estão modelados globalmente

**Problema.** O descritivo exige papel por vínculo Usuário–Filial, porém `spatie/laravel-permission` está com `teams => false`. `hasRole('Corretor')` consulta papéis globais; o campo `filial_user.perfil_acesso` é paralelo e não é usado pelo pacote nas decisões.

**Explicação didática.** Um usuário que seja corretor em A e gestor em B passa a ter os dois crachás em qualquer contexto. As Policies tentam remediar isso manualmente, mas inevitavelmente algum Resource esquece uma das verificações, como já ocorreu em pagamentos.

**Impacto antes da mudança.** Ativar teams é uma migração de autorização: papéis existentes precisam ser associados às filiais e todas as telas devem definir a filial corrente. Não deve ser feita misturada a novas funcionalidades.

**Solução (código).** Adote o recurso Teams do Spatie com `filial_id` como chave de equipe, após migrar os vínculos existentes:

```php
// config/permission.php
'teams' => true,
'column_names' => [
    // demais chaves preservadas
    'team_foreign_key' => 'filial_id',
],

// antes de checar/atribuir papel no contexto escolhido
app(\Spatie\Permission\PermissionRegistrar::class)
    ->setPermissionsTeamId($filialId);
```

Enquanto a migração não estiver pronta, crie uma única classe `EscopoFilial` e proíba `hasRole()` isolado em Resources e Policies que tratem dados operacionais.

### P0 — Aceite pode emitir apólice e marcar pagamento sem confirmação; há vazamento de exceção

**Problema.** O checkout aceita uma URL assinada por 30 dias como credencial, não autentica o segurado e emite após um clique. `EmissaoApoliceService` não bloqueia status inválido, validade vencida, duplicidade ou chamador não autorizado. A ação do Filament chega a emitir cotações em “Em Elaboração” ou “Enviada ao Cliente”. Em caso de falha, o `dd()` devolve mensagem, arquivo e linha internos.

**Explicação didática.** Link assinado prova apenas que alguém possui o link; não prova que a pessoa ainda é quem pode aceitar a proposta. Requisições concorrentes ou repetidas podem entrar no mesmo intervalo e produzir dois contratos. Expor caminhos internos facilita exploração posterior.

**Impacto antes da mudança.** A emissão passa a falhar com mensagem controlada quando a cotação não está elegível e fica idempotente. Isso preserva o fluxo previsto — aceitar/aprovar e confirmar pagamento antes de emitir — em vez de criar uma regra nova.

**Solução (código).** Primeiro, escolha conscientemente entre login do segurado ou token opaco de uso único; não trate URL assinada longa como sessão. Depois, concentre as guardas no serviço:

```php
// migration: uma cotação gera no máximo uma apólice
Schema::table('apolices', fn (Blueprint $table) => $table->unique('cotacao_id'));

// EmissaoApoliceService::emitir()
return DB::transaction(function () use ($cotacao, $formaPagamento, $quantidadeParcelas) {
    $cotacao = Cotacao::with(['produto', 'apolice'])
        ->lockForUpdate()->findOrFail($cotacao->id);

    abort_unless($cotacao->status === 'Aceita' && $cotacao->validade?->isFuture(), 422);
    if ($cotacao->apolice) return $cotacao->apolice; // idempotência

    // criar apólice e parcelas dentro desta transação
});
```

Troque o `dd()` por `report($e)` e uma `ValidationException` ou notificação genérica. A action customizada deve chamar `Gate::authorize('create', Apolice::class)` e o serviço, nunca depender de `visible()` como segurança.

### P1 — Dados pessoais e dados sensíveis permanecem em claro e sem política de logs

**Problema.** CPF, RG, CNPJ, endereço e dados de saúde do seguro de vida ficam em colunas/JSON normais. O projeto registra dados financeiros do cálculo em `CalculadoraPremioService`; não há uma camada de auditoria com redaction, retenção e acesso mínimo.

**Explicação didática.** LGPD não é apenas esconder um campo na tabela: é reduzir quem lê o dado, registrar acesso e limitar o dano se backup, log ou banco forem indevidamente acessados. Saúde é dado pessoal sensível; JSON não fornece proteção criptográfica por si só.

**Impacto antes da mudança.** Criptografar CPF remove a busca e unicidade direta. Para continuar verificando duplicidade sem revelar o valor, guarde um hash determinístico separado e o valor cifrado. Planeje migração e rotação de chave antes de ativar em produção.

**Solução (código).**

```php
// migration: cpf_hash indexado/único; cpf passa a conter valor cifrado
$table->string('cpf_hash', 64)->unique();

// ao salvar, depois de normalizar somente dígitos
$model->cpf_hash = hash_hmac('sha256', $cpfNormalizado, config('app.key'));
$model->cpf = Crypt::encryptString($cpfNormalizado);

// logging: nunca inclua CPF, e-mail, endereço, dados de saúde ou token
Log::info('premio_calculado', [
    'produto_id' => $produto->id,
    'cotacao_id' => $cotacaoId,
    'faixa_premio' => $faixa,
]);
```

Adicione um log de auditoria imutável para leitura/exportação de apólices e sinistros, com ator, recurso, ação, data e filial — sem gravar o conteúdo sensível.

### P1 — Upload de evidências precisa de defesa em profundidade

**Problema.** O Relation Manager aceita PDF e imagens e limita tamanho, mas não há validação de assinatura real, antivírus, política explícita de download nem autorização específica da ação de upload.

**Explicação didática.** Tipo MIME informado pelo navegador é uma etiqueta, não uma perícia. Arquivos maliciosos podem usar uma etiqueta permitida; e mesmo um PDF válido pode explorar o leitor de quem o baixar. O disco local privado atual é uma boa base, mas não basta sozinho.

**Impacto antes da mudança.** A análise assíncrona pode atrasar a disponibilidade de um anexo; a UI deve informar “em verificação”. Isso protege operadores sem mudar o fluxo de sinistro.

**Solução (código).** Mantenha o arquivo fora do webroot, gere nome pelo servidor, valide conteúdo no back-end e libere download somente por controller com Policy:

```php
FileUpload::make('anexos')
    ->disk('local')->directory("sinistros/{$sinistroId}")
    ->acceptedFileTypes(['application/pdf', 'image/jpeg', 'image/png'])
    ->maxSize(5120)->preserveFilenames(false);

// após upload: Job de antivírus/validação de assinatura; status = pendente/liberado/rejeitado
Gate::authorize('view', $sinistro);
return Storage::disk('local')->download($anexo->path);
```

O checklist oficial do OWASP recomenda allowlist, validação do tipo real, nome gerado, armazenamento fora do webroot, autorização e varredura antimalware.

### P1 — Conta desativada ainda não é bloqueada no acesso ao painel

**Problema.** `users.status` existe, mas `User` não o usa para impedir acesso ao painel. A consulta de corretor filtra status, porém autenticação não.

**Explicação didática.** Marcar um usuário como inativo sem bloquear a porta é apenas ocultá-lo de uma lista. Um colaborador desligado pode continuar usando sessão ou credenciais válidas.

**Impacto antes da mudança.** Usuários inativos passam a receber 403 no painel; inclua teste de revogação de sessão se a exigência for imediata.

**Solução (código).**

```php
// App\Models\User
use Filament\Panel;

public function canAccessPanel(Panel $panel): bool
{
    return (bool) $this->status;
}
```

## 2. ⚡ Gargalos de Performance e Escalabilidade

### P1 — N+1 nas tabelas administrativas

**Problema.** A tabela de apólices lê segurado, PF/PJ e corretor por linha; a de sinistros também atravessa apólice e segurado. As queries base não usam `with()`. A tabela de filial consulta o gestor dentro do callback de cada linha.

**Explicação didática.** Em uma lista de 50 apólices, uma consulta inicial pode virar mais de 150 consultas pequenas. Parece rápido com poucos dados e fica lento exatamente quando o sistema ganha carteira.

**Impacto antes da mudança.** Eager loading aumenta a consulta inicial e a memória da página, mas reduz drasticamente idas e voltas ao banco. Carregue somente relações mostradas na tabela.

**Solução (código).**

```php
// ApoliceResource::getEloquentQuery()
$query = parent::getEloquentQuery()->with([
    'user:id,name',
    'segurado:id,tipo',
    'segurado.seguradoPf:segurado_id,nome',
    'segurado.seguradoPj:segurado_id,razao_social',
]);
```

Repita a ideia em `SinistroResource` com `apolice.segurado.seguradoPf` e `seguradoPj`. Para gestor de filial, crie uma relação `gestor()` em `Filial`, faça eager load e elimine a query do callback de coluna.

### P1 — Índices não acompanham filtros operacionais e a consulta de renovação inutiliza índice

**Problema.** As migrations não declaram índices compostos para filial/status/data. Em `ProcessarRenovacoes`, `DATE(data_fim)` aplica uma função sobre a coluna; PostgreSQL tende a não usar um índice normal de `data_fim` nesse filtro.

**Explicação didática.** Índice é o catálogo do banco. Filtrar milhares de apólices sem catálogo obriga a ler a estante inteira. Aplicar uma função na coluna é como pedir o catálogo por uma versão transformada do título.

**Impacto antes da mudança.** Índices tornam escrita um pouco mais cara e ocupam disco, mas são apropriados para os filtros que o próprio produto exige (filial, status, período e carteira).

**Solução (código).**

```php
// migration nova
Schema::table('apolices', function (Blueprint $table) {
    $table->index(['filial_id', 'status', 'data_fim']);
    $table->unique('cotacao_id');
});
Schema::table('sinistros', fn (Blueprint $table) =>
    $table->index(['apolice_id', 'status', 'data_hora_ocorrencia']));
Schema::table('pagamentos', fn (Blueprint $table) =>
    $table->index(['apolice_id', 'status', 'data_vencimento']));

// consulta sargable
Apolice::where('status', 'Vigente')
    ->whereIn('data_fim', [$data60Dias, $data30Dias, $data15Dias]);
```

### P2 — Processamento diário carrega tudo em memória e não é robusto contra repetição

**Problema.** O comando de renovação usa `get()` e executa a geração em laço. As comparações de status usam valores com caixa diferente do restante do sistema (`Em elaboração` versus `Em Elaboração`), de modo que a proteção de “já possui renovação” pode falhar.

**Explicação didática.** Um comando que funciona para dez apólices pode esgotar memória ou falhar no meio para dezenas de milhares. Strings de estado copiadas são como semáforos com grafias diferentes: cada parte acha que a luz tem outra cor.

**Impacto antes da mudança.** Jobs individualizam tentativas e falhas; `chunkById` diminui uso de memória. O estado deve ser migrado/testado sem alterar significados existentes.

**Solução (código).**

```php
Apolice::query()->where('status', ApoliceStatus::VIGENTE)
    ->whereIn('data_fim', $datasDeGatilho)
    ->orderBy('id')
    ->chunkById(500, function ($apolices) {
        foreach ($apolices as $apolice) {
            ProcessarRenovacaoJob::dispatch($apolice->id);
        }
    });
```

Use `enum`/constantes para todos os status e uma restrição única ou tabela de renovação para garantir idempotência, em vez de procurar um id temporário dentro de JSON.

## 3. 🧹 Clean Code e Qualidade de Software

### P0 — Regras de emissão estão duplicadas e o serviço não protege seus invariantes

**Problema.** A regra de alçada aparece no Observer, o checkout modifica status e emite, e uma action de tabela chama o serviço diretamente com estados diferentes. O serviço aceita qualquer cotação, usa número aleatório sujeito à colisão e a migration não torna `cotacao_id` único.

**Explicação didática.** A regra essencial deve morar no “cofre”, não em três portas. Quando a mesma regra está em telas e Observers, uma nova entrada invariavelmente esquece uma verificação — como a emissão por cotação ainda em elaboração.

**Impacto antes da mudança.** Transformar o serviço em única porta de emissão requer adaptar os dois chamadores. Em compensação, API, Filament e checkout ganham a mesma proteção e o banco impede duplicidade mesmo sob concorrência.

**Solução (código).**

```php
final class EmissaoApoliceService
{
    public function emitir(User $ator, int $cotacaoId, DadosPagamento $pagamento): Apolice
    {
        return DB::transaction(function () use ($ator, $cotacaoId, $pagamento) {
            $cotacao = Cotacao::query()->lockForUpdate()->findOrFail($cotacaoId);
            Gate::forUser($ator)->authorize('emitir', $cotacao);
            $this->assertElegivel($cotacao, $pagamento);

            return $cotacao->apolice()->firstOr(fn () => $this->criarApolice($cotacao, $pagamento));
        });
    }
}
```

Crie a habilidade específica `emitir` na `CotacaoPolicy`; `create_apolice` é genérica demais para uma transição financeira/contratual.

### P1 — Modelo, Policy e Resource de Segurado divergem

**Problema.** O modelo define `corretor_id` e `user_id`; a Policy usa `filial_id` inexistente e o Resource filtra corretor por `user_id`. O resultado é falha SQL ou carteira errada, além de dificultar manutenção.

**Explicação didática.** É como um mapa que chama a mesma rua por três nomes: cada leitor chega a um lugar diferente. Tipagem, relações nomeadas e testes evitam que a divergência apareça só em produção.

**Impacto antes da mudança.** A correção pode revelar dados que estavam invisíveis por erro; valide a carteira esperada de cada corretor após migrar.

**Solução (código).**

```php
// escopo explícito no modelo
public function scopeDaCarteira(Builder $query, User $corretor): Builder
{
    return $query->where('corretor_id', $corretor->id);
}

// SeguradoResource
if ($user->hasRole('Corretor')) {
    return $query->daCarteira($user);
}

// demais perfis: relacionar pelo histórico operacional, não por coluna inexistente
return $query->whereHas('apolices', fn ($q) =>
    $q->whereIn('filial_id', $user->filiais()->select('filiais.id')));
```

### P1 — Auditoria de sinistro é parcialmente imutável, mas não possui transição central nem autor confiável

**Problema.** A interface remove editar/excluir movimentação, porém o Observer aceita qualquer criação, atualiza status direto e, sem sessão, atribui autoria ao usuário `1`. Não há guarda explícita no Relation Manager para bloquear nova movimentação após estado final ou implementar dupla aprovação.

**Explicação didática.** Ocultar o botão de editar preserva a tela, não a evidência. Auditoria confiável exige que toda transição tenha ator real, estado anterior, estado novo e autorização validada no servidor.

**Impacto antes da mudança.** Seeders devem passar um autor técnico explícito; o fluxo web deve exigir usuário autenticado. A timeline continua imutável, agora com proveniência verificável.

**Solução (código).**

```php
public function registrarMovimentacao(User $ator, Sinistro $sinistro, AcaoSinistro $acao, string $descricao): void
{
    Gate::forUser($ator)->authorize('movimentar', $sinistro);
    throw_if($sinistro->status->finalizado(), DomainException::class);

    $sinistro->movimentacoes()->create([
        'user_id' => $ator->id,
        'acao_realizada' => $acao->value,
        'descricao' => $descricao,
        'data_hr_movimentacao' => now(),
    ]);
}
```

Remova o fallback `auth()->id() ?? 1`; use uma factory/seeder que declare o ator de sistema quando isso for realmente necessário.

### P1 — A suíte não testa os fluxos e os controles mais arriscados

**Problema.** Há somente os dois testes de exemplo. Não há testes de Policy, escopo por filial, emissão concorrente, token de reset, upload ou transições de sinistro.

**Explicação didática.** O teste é o alarme do cofre: sem ele, uma refatoração pode remover uma trava de filial sem que ninguém perceba. Segurança e regra de contrato precisam de testes de negação, não apenas de sucesso.

**Impacto antes da mudança.** Testes com `RefreshDatabase` usam uma base isolada; configure `DB_DATABASE` de teste para nunca apontar à base de desenvolvimento/produção.

**Solução (código).**

```php
use Illuminate\Foundation\Testing\RefreshDatabase;

public function test_cliente_nao_visualiza_pagamento_de_outro_segurado(): void
{
    $this->actingAs($clienteA)
        ->get(PagamentoResource::getUrl('view', ['record' => $pagamentoDoClienteB]))
        ->assertForbidden();
}

public function test_cotacao_emite_apenas_uma_apolice_em_requisicoes_repetidas(): void
{
    $this->emitirDuasVezes($cotacaoAceita);
    $this->assertDatabaseCount('apolices', 1);
}
```

### P2 — Higiene de dependências e configuração precisa virar rotina

**Problema.** `composer.json` usa `minimum-stability: dev` e um alias incomum para `spatie/laravel-permission` (`8.0.0 as 6.4.0`). O executável Composer não está disponível neste ambiente, portanto esta auditoria não pôde consultar advisories do lockfile.

**Explicação didática.** Dependência é código com o mesmo privilégio do seu aplicativo. Versões de desenvolvimento e aliases difíceis de entender aumentam a chance de atualização incompatível ou de uma correção de segurança ser adiada.

**Impacto antes da mudança.** Pinagem/atualização deve ser testada em CI, pois pode exigir ajustes de API. Não altere o lockfile manualmente.

**Solução (código).**

```json
// composer.json: após validar compatibilidade real
"minimum-stability": "stable",
"prefer-stable": true
```

No CI, execute `composer validate --strict`, `composer audit --locked` e `php artisan test`; abra PR separado para cada atualização de dependência.

## 4. 📝 Plano de Ação para a Nova Branch

1. Criar `feature/auditoria-qa-sec`, sem misturar funcionalidades de negócio ou as alterações já presentes no diretório de trabalho.
2. **P0 imediato:** remover/proteger `/teste-pdf`, substituir a rota de reset por fluxo real e retirar o `dd()` de produção.
3. **P0 imediato:** impedir emissão fora do estado elegível, adicionar idempotência (`lockForUpdate` + `unique(cotacao_id)`) e autorização explícita na action de emissão.
4. **P0 imediato:** corrigir escopo de `Pagamento`, `Segurado` e seleção de segurado/cotação; escrever testes negativos entre filiais, corretor e cliente.
5. **P0 de arquitetura:** planejar migração para papéis por filial (Teams do Spatie ou uma camada única equivalente), com script de migração e rollout por ambiente.
6. **P1:** definir classificação LGPD, criptografar/hashear identificadores sensíveis, redigir logs e implantar trilha de auditoria de acesso/exportação.
7. **P1:** reforçar upload privado com validação de conteúdo, scanner e download autorizado.
8. **P1:** adicionar eager loading, índices compostos e `chunkById`/Jobs para renovação; medir antes/depois com query log e `EXPLAIN ANALYZE` em ambiente de homologação.
9. **P1:** substituir strings de status por enum/constantes e centralizar transições de sinistro e emissão em serviços de domínio.
10. **P1:** construir a suíte de testes de autorização, emissão, timeline, reset e uploads; somente então executar `composer audit --locked` em CI e estabilizar dependências.
