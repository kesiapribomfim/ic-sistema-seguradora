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

## Principais Funcionalidades

1. **Multi-tenancy e Controle de Acesso (RBAC):**
   Implementação de perfis distintos (Corretor, Subscritor, Gestor, Financeiro, Analista e Cliente). Os dados são isolados por Filial, garantindo que um corretor ou financeiro só tenha acesso aos dados da sua própria jurisdição.

2. **Máquina de Estados e Alçadas de Aprovação (Sinistros):**
   Sinistros não podem ser aprovados livremente em formulários abertos. Utilizei as `Actions` do Filament para criar modais de interação. Se o valor do sinistro ultrapassa a alçada comercial parametrizada no Produto, o sistema bloqueia a aprovação e exige uma dupla validação (Analista + Gestor).

3. **Imutabilidade de Contratos e Snapshot (Apólices):**
   Produtos podem ter seus preços e taxas alterados no futuro, mas isso **não pode** afetar apólices já emitidas. Para garantir isso, a emissão da apólice salva um `Snapshot` (JSON) com as regras do produto no exato momento da compra.
   - **Endossos:** Alterações em apólices vigentes não sobrescrevem os dados originais. O sistema gera uma nova versão (clonagem) da apólice e inativa a anterior, preservando o histórico e a validade jurídica.

4. **Trilha de Auditoria (Compliance):**
   Utilização do `Spatie Activitylog` conectado ao ciclo de vida (lifecycle hooks) das páginas do Filament para registrar passivamente (sem intervenção do usuário) todo acesso de leitura e escrita aos recursos sensíveis (Apólices e Sinistros).

## Como Executar o Projeto Localmente

Siga o passo a passo abaixo para rodar a aplicação na sua máquina:

**1. Clone o repositório**

```bash
git clone https://github.com/kesiapribomfim/ic-sistema-seguradora.git
cd ic-sistema-seguradora
```

**2. Prepare o ambiente Backend**

```bash
composer install
cp .env.example .env
php artisan key:generate
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

## Perfis de Acesso para Teste
*(Utilize a senha `password` para todos os usuários)*

| Perfil de Acesso | Nome do Usuário | E-mail de Teste | O que avaliar? |
| :--- | :--- | :--- | :--- |
| **Super Admin** | Super Admin | `super_admin@exemplo.com` | Acesso absoluto ao sistema, roles e permissões técnicas. |
| **Administrador Geral** | Admin Geral | `admin@geral.com` | Visão total da seguradora, filiais e parametrizações gerais. |
| **Gestor de Filial** | Gestor Teste | `gestor@filial.com` | Controle da sua filial e aprovação de sinistros acima da alçada. |
| **Subscritor** | Subscritor Teste | `subscritor@seguradora.com` | Aprovação de Cotações acima da alçada do corretor |
| **Corretor** | Corretor Teste | `corretor@seguradora.com` | Geração de cotações, emissão de apólices e restrições de alçada. |
| **Analista de Sinistros** | Analista de Sinistros Teste | `analista@sinistros.com` | Regulação de sinistros, trilha de movimentações e laudos. |
| **Financeiro** | Financeiro Teste | `financeiro@seguradora.com` | Acompanhamento de apólices, pagamentos e inadimplências. |
| **Cliente** | Cliente Teste | `cliente@seguradora.com` | Visão completamente restrita apenas às suas próprias apólices, parcelas e criação de sinistros. |

## Testando as Rotinas Automáticas (Cron Jobs)

```bash
php artisan seguradora:processar-inadimplencia
php artisan seguradora:processar-renovacoes
```