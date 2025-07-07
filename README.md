API de Carteira Financeira
Este projeto implementa uma API RESTful para um sistema de carteira financeira, permitindo que os usuários gerenciem seus saldos, realizem depósitos e façam transferências. Ele é construído com Laravel 10 e utiliza Laravel Sanctum para autenticação via tokens de API.

🚀 Funcionalidades
Registro e Autenticação de Usuários: Crie contas e faça login para acessar os recursos da carteira.

Gestão de Saldo: Consulte o saldo atual da sua carteira.

Depósitos: Adicione fundos à sua carteira.

Transferências: Envie dinheiro para a carteira de outros usuários.

Histórico de Transações: Visualize todas as suas movimentações financeiras (depósitos, envios e recebimentos de transferências).

Reversão de Transações: Capacidade de reverter depósitos e transferências (funcionalidade sensível, idealmente para uso administrativo).

🛠️ Instalação e Configuração
Siga os passos abaixo para colocar a API em funcionamento no seu ambiente local.

Pré-requisitos
Certifique-se de ter instalado em sua máquina:

PHP >= 8.1

Composer

Node.js & npm (necessário para o Laravel Mix, embora não seja o foco principal de uma API, pode ser um requisito de build do Laravel)

Um servidor de banco de dados (MySQL, PostgreSQL, SQLite, etc. MySQL é o mais comum para Laravel)

Passos da Instalação
Clone o Repositório:

Bash

git clone <URL_DO_SEU_REPOSITORIO>
cd financial-wallet-api # Ou o nome da sua pasta do projeto
Instale as Dependências do Composer:

Bash

composer install
Configure o Arquivo de Ambiente (.env):

Copie o arquivo de exemplo:

Bash

cp .env.example .env
Abra o arquivo .env e configure as credenciais do seu banco de dados. Exemplo para MySQL:

Snippet de código

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

# Configuração do Sanctum

SANCTUM_STATEFUL_DOMAINS=localhost,localhost:3000,127.0.0.1,127.0.0.1:8000

# ... outras configurações (cache, mail, queue drivers podem ficar como padrão para desenvolvimento)

Crie o banco de dados financial_wallet_db (ou o nome que você escolheu) no seu sistema de gerenciamento de banco de dados (ex: phpMyAdmin, MySQL Workbench).

Gere a Chave da Aplicação:

Bash

php artisan key:generate
Execute as Migrações do Banco de Dados:
Isso criará as tabelas users, wallets, transactions, personal_access_tokens e reversals.

Bash

php artisan migrate

Inicie o Servidor de Desenvolvimento:

Bash

php artisan serve
A API estará acessível em http://localhost:8000.

🌍 Endpoints da API
Todos os endpoints retornam respostas em formato JSON.

Headers Padrão para Requisições
Para todas as requisições, é recomendado enviar:

Accept: application/json

Para rotas protegidas (que exigem autenticação), adicione também:

Authorization: Bearer <SEU_TOKEN_DE_API>
