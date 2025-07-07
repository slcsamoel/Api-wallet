API de Carteira Financeira
Este projeto implementa uma API RESTful para um sistema de carteira financeira, permitindo que os usuários gerenciem seus saldos, realizem depósitos e façam transferências. Ele é construído com Laravel 10 e utiliza Laravel Sanctum para autenticação via tokens de API.

🚀 Funcionalidades
Registro e Autenticação de Usuários: Crie contas e faça login para acessar os recursos da carteira.

Gestão de Saldo: Consulte o saldo atual da sua carteira.

Depósitos: Adicione fundos à sua carteira.

Transferências: Envie dinheiro para a carteira de outros usuários.

Histórico de Transações: Visualize todas as suas movimentações financeiras (depósitos, envios e recebimentos de transferências).

Reversão de Transações: Capacidade de reverter depósitos e transferências (funcionalidade sensível, idealmente para uso administrativo).

### 🛠️ Instalação e Configuração

Siga os passos abaixo para colocar a API em funcionamento no seu ambiente local.

Pré-requisitos
Certifique-se de ter instalado em sua máquina:

PHP >= 8.1

Composer

Um servidor de banco de dados (MySQL, PostgreSQL, SQLite, etc. MySQL é o mais comum para Laravel)

```sh
composer install
```

Configure o Arquivo de Ambiente (.env):
Copie o arquivo de exemplo:

```sh
cp .env.example .env
```

Abra o arquivo .env e configure as credenciais do seu banco de dados. Exemplo para MySQL:

```js
APP_NAME="Financial Wallet API"
APP_ENV=local
APP_KEY=
APP_DEBUG=true
APP_URL=http://localhost:8000

DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=financial_wallet_db # Crie este banco de dados
DB_USERNAME=root
DB_PASSWORD=
```

Crie o banco de dados financial_wallet_db (ou o nome que você escolheu) no seu sistema de gerenciamento de banco de dados (ex: phpMyAdmin, MySQL Workbench).
Gere a Chave da Aplicação:

```sh
php artisan key:generate
```

Execute as Migrações do Banco de Dados:
Isso criará as tabelas users, wallets, transactions, personal_access_tokens e reversals.

```sh
php artisan migrate
```

Inicie o Servidor de Desenvolvimento:

```sh
php artisan serve
```

A API estará acessível em http://localhost:8000.

🌍 Endpoints da API
Todos os endpoints retornam respostas em formato JSON.

Headers Padrão para Requisições
Para todas as requisições, é recomendado enviar:

Accept: application/json

Para rotas protegidas (que exigem autenticação), adicione também:

Authorization: Bearer <SEU_TOKEN_DE_API>
