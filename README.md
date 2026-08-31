## SISTEMA DE GESTÃO DE SEGURADORA 

Aplicação web full-stack desenvolvida para a gestão de uma seguradora multi-ramo, contemplando produtos de Seguro Auto, Seguro de Vida e Seguro Residencial.
O sistema gerencia todo o ciclo de vida de uma apólice, desde a cotação inicial até a regulação de sinistros.


### Tecnologias Utilizadas

- **Laravel v12:** Framework PHP Backend
- **Fillament v3:** Construção ágil do painel administrativo (Resources, Forms e Tables).
- **Livewire 3 + Alpine.js + Tailwind CSS:** Stack TALL nativa do Filament para reatividade no front-end sem a necessidade de uma SPA separada.
- **PostgreSQL:** Banco de dados Relacional.


### Pré-requisitos de Ambiente

Para executar projeto localmente, faz-se necessário as seguintes componentes:

- **PHP V. 8.3**
- **Composer 2.10**
- **PostgreSQL** 

### Principais Funcionalidades e Decisões de Arquitetura


1.  **Multi-tenancy e Controle de Acesso (RBAC):**
    Implementação de perfis distintos (Corretor, Subscritor, Gestor, Financeiro, Analista e Cliente). Os dados são isolados por Filial, garantindo que um corretor ou financeiro só tenha acesso aos dados da sua própria jurisdição.

2.  **Máquina de Estados e Alçadas de Aprovação (Sinistros):**
    Sinistros não podem ser aprovados livremente em formulários abertos. Utilizei as `Actions` do Filament para criar modais de interação. Se o valor do sinistro ultrapassa a alçada comercial parametrizada no Produto, o sistema bloqueia a aprovação e exige uma dupla validação (Analista + Gestor).

3.  **Imutabilidade de Contratos e Snapshot (Apólices):**
    Produtos podem ter seus preços e taxas alterados no futuro, mas isso **não pode** afetar apólices já emitidas. Para garantir isso, a emissão da apólice salva um `Snapshot` (JSON) com as regras do produto no exato momento da compra.
    *   **Endossos:** Alterações em apólices vigentes não sobrescrevem os dados originais. O sistema gera uma nova versão (clonagem) da apólice e inativa a anterior, preservando o histórico e a validade jurídica.

4.  **Automação de Inadimplência e Risco (Jobs e Commands):**
    *   *Decisão:* Foi criado um `Command` do Artisan (`seguradora:processar-inadimplencia`) desenhado para rodar diariamente (via Cron). Ele identifica parcelas vencidas, suspende a apólice automaticamente via `DB::transaction` e penaliza o `score_risco` do segurado, demonstrando processamento assíncrono e integridade relacional.

5.  **Trilha de Auditoria (Compliance):**
    *   *Decisão:* Utilização do `Spatie Activitylog` conectado ao ciclo de vida (lifecycle hooks) das páginas do Filament para registrar passivamente (sem intervenção do usuário) todo acesso de leitura aos recursos sensíveis (Apólices e Sinistros).

## 💻 Como Executar o Projeto Localmente

Siga o passo a passo abaixo para rodar a aplicação na sua máquina:

**1. Clone o repositório e acesse a pasta:**
```bash
git clone [https://github.com/SEU-USUARIO/sistema-seguradora.git](https://github.com/SEU-USUARIO/sistema-seguradora.git)
cd sistema-seguradora
