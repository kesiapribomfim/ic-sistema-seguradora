# SISTEMA DE GESTÃO DE SEGURADORA 

Sistema completo de gestão de apólices, cotações, sinistros e inadimplências, desenvolvido como Projeto Acadêmico por **Késia Priscilla da Silva Bomfim**. 
Arquitetura baseada em Multi-Tenancy (Filiais), controle rigoroso de acesso (ACL) e processos assíncronos (Jobs/Schedules).

## Tecnologias Utilizadas

- **Laravel v12:** Framework PHP Backend.
- **Filament v3:** Construção ágil do painel administrativo (Resources, Forms e Tables).
- **Livewire 3 + Alpine.js + Tailwind CSS:** Stack TALL nativa do Filament para reatividade no front-end sem a necessidade de uma SPA separada.
- **PostgreSQL:** Banco de dados Relacional.

## Pré-requisitos de Ambiente

Para executar o projeto localmente, fazem-se necessários os seguintes componentes:

- **PHP v8.3**
- **Composer 2.10**
- **PostgreSQL** 
- **Node.js e NPM** (para compilar o CSS/JS)

## Principais Funcionalidades e Decisões de Arquitetura

1. **Multi-tenancy e Controle de Acesso (RBAC):**
   Implementação de perfis distintos (Corretor, Subscritor, Gestor, Financeiro, Analista e Cliente). Os dados são isolados por Filial, garantindo que um corretor ou financeiro só tenha acesso aos dados da sua própria jurisdição.

2. **Máquina de Estados e Alçadas de Aprovação (Sinistros):**
   Sinistros não podem ser aprovados livremente em formulários abertos. Utilizei as `Actions` do Filament para criar modais de interação. Se o valor do sinistro ultrapassa a alçada comercial parametrizada no Produto, o sistema bloqueia a aprovação e exige uma dupla validação (Analista + Gestor).

3. **Imutabilidade de Contratos e Snapshot (Apólices):**
   Produtos podem ter seus preços e taxas alterados no futuro, mas isso **não pode** afetar apólices já emitidas. Para garantir isso, a emissão da apólice salva um `Snapshot` (JSON) com as regras do produto no exato momento da compra.
   - **Endossos:** Alterações em apólices vigentes não sobrescrevem os dados originais. O sistema gera uma nova versão (clonagem) da apólice e inativa a anterior, preservando o histórico e a validade jurídica.

4. **Automação de Inadimplência e Risco (Jobs e Commands):**
   Foi criado um `Command` do Artisan (`seguradora:processar-inadimplencia`) desenhado para rodar diariamente (via Cron). Ele identifica parcelas vencidas, suspende a apólice automaticamente via `DB::transaction` e penaliza o `score_risco` do segurado, demonstrando processamento assíncrono e integridade relacional.

5. **Trilha de Auditoria (Compliance):**
   Utilização do `Spatie Activitylog` conectado ao ciclo de vida (lifecycle hooks) das páginas do Filament para registrar passivamente (sem intervenção do usuário) todo acesso de leitura e escrita aos recursos sensíveis (Apólices e Sinistros).

## Como Executar o Projeto Localmente

Siga o passo a passo abaixo para rodar a aplicação na sua máquina:

**1. Clone o repositório**

```bash
git clone https://github.com/kesiapribomfim/ic-sistema-seguradora.git
cd sistema-seguradora
```

**2. Prepare o ambiente Backend**

```bash
npm install
npm run build
```

**3. Compile o Frontend**
```bash
npm install
npm run build
```

**4. Banco de Dados e Migrações (Seeders)**
Crie um banco PostgreSQL com o nome sistema_seguradora e configure as suas credenciais no arquivo .env gerado no passo 2.

O sistema possui uma base de dados pronta para testes, incluindo usuários com diferentes níveis de acesso e filiais configuradas. Rode o comando:

```bash
php artisan migrate:fresh --seed
```
**5. Inicie o Servidor**

```bash
php artisan serve
```

*Testando as Rotinas Automáticas (Cron Jobs)*

php artisan seguradora:processar-inadimplencia
php artisan seguradora:processar-renovacoes
```