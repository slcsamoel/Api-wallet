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

Autenticação

1. Registrar Usuário
   URL: /api/register

Método: POST

Corpo da Requisição (JSON):

JSON

{
"name": "Nome do Usuário",
"email": "email@example.com",
"password": "senhaforte123",
"password_confirmation": "senhaforte123"
}
Resposta Esperada (201 Created):

JSON

{
"message": "Usuário registrado e carteira criada com sucesso!",
"user": {
"name": "Nome do Usuário",
"email": "email@example.com",
"updated_at": "YYYY-MM-DDTHH:MM:SS.000000Z",
"created_at": "YYYY-MM-DDTHH:MM:SS.000000Z",
"id": 1
},
"token": "SEU_TOKEN_DE_AUTENTICACAO_GERADO_AQUI"
}
Erros:

422 Unprocessable Entity: Se o email já existe, senhas não batem ou validação falha.

2. Login de Usuário
   URL: /api/login

Método: POST

Corpo da Requisição (JSON):

JSON

{
"email": "email@example.com",
"password": "senhaforte123"
}
Resposta Esperada (200 OK):

JSON

{
"message": "Login realizado com sucesso!",
"user": {
"id": 1,
"name": "Nome do Usuário",
"email": "email@example.com",
"email_verified_at": null,
"created_at": "YYYY-MM-DDTHH:MM:SS.000000Z",
"updated_at": "YYYY-MM-DDTHH:MM:SS.000000Z"
},
"token": "NOVO_TOKEN_DE_AUTENTICACAO_GERADO_AQUI"
}
Erros:

422 Unprocessable Entity: Se as credenciais estiverem incorretas.

3. Dados do Usuário Autenticado
   URL: /api/user

Método: GET

Autenticação Necessária: Sim (Token Bearer)

Corpo da Requisição: Nenhum

Resposta Esperada (200 OK):

JSON

{
"id": 1,
"name": "Nome do Usuário",
"email": "email@example.com",
"email_verified_at": null,
"created_at": "YYYY-MM-DDTHH:MM:SS.000000Z",
"updated_at": "YYYY-MM-DDTHH:MM:SS.000000Z"
}
Erros:

401 Unauthorized: Se o token for inválido ou não fornecido.

4. Logout de Usuário
   URL: /api/logout

Método: POST

Autenticação Necessária: Sim (Token Bearer)

Corpo da Requisição: Nenhum

Resposta Esperada (200 OK):

JSON

{
"message": "Logout realizado com sucesso!"
}
Erros:

401 Unauthorized: Se o token for inválido ou não fornecido.

Carteira

1. Consultar Saldo da Carteira
   URL: /api/wallet

Método: GET

Autenticação Necessária: Sim (Token Bearer)

Corpo da Requisição: Nenhum

Resposta Esperada (200 OK):

JSON

{
"user_name": "Nome do Usuário",
"wallet_id": 1,
"balance": "123.45",
"is_negative_flag": false
}
Erros:

401 Unauthorized: Se o token for inválido ou não fornecido.

404 Not Found: Se a carteira do usuário não for encontrada (improvável se o registro cria a carteira).

Transações

1. Realizar Depósito
   URL: /api/deposit

Método: POST

Autenticação Necessária: Sim (Token Bearer)

Corpo da Requisição (JSON):

JSON

{
"amount": 100.50,
"description": "Depósito de salário"
}
Resposta Esperada (200 OK):

JSON

{
"message": "Depósito realizado com sucesso!",
"new_balance": "223.95"
}
Erros:

401 Unauthorized: Se o token for inválido ou não fornecido.

422 Unprocessable Entity: Se amount for inválido (ex: negativo, não numérico).

403 Forbidden: Se a carteira estiver com a flag is_negative = true (indicando inconsistência).

500 Internal Server Error: Se ocorrer um erro inesperado no processamento do depósito (ex: problema de banco de dados).

2. Realizar Transferência
   URL: /api/transfer

Método: POST

Autenticação Necessária: Sim (Token Bearer)

Corpo da Requisição (JSON):

JSON

{
"to_user_email": "destino@example.com",
"amount": 50.00,
"description": "Pagamento de conta"
}
Resposta Esperada (200 OK):

JSON

{
"message": "Transferência realizada com sucesso!",
"your_new_balance": "173.95"
}
Erros:

401 Unauthorized: Se o token for inválido ou não fornecido.

422 Unprocessable Entity:

Se amount for inválido.

Se to_user_email não existir ou for igual ao seu próprio email.

Se o saldo for insuficiente (Saldo insuficiente para a transferência.).

403 Forbidden: Se a carteira de destino estiver com a flag is_negative = true.

500 Internal Server Error: Se ocorrer um erro inesperado no processamento da transferência.

3. Listar Histórico de Transações
   URL: /api/transactions

Método: GET

Autenticação Necessária: Sim (Token Bearer)

Corpo da Requisição: Nenhum

Resposta Esperada (200 OK):

JSON

[
{
"id": 1,
"type": "Depósito",
"amount": "100.00",
"status": "completed",
"description": "Depósito de salário",
"date": "YYYY-MM-DDTHH:MM:SS.000000Z",
"other_party_name": "Sistema",
"direction": "Entrada"
},
{
"id": 2,
"type": "Envio de Transferência",
"amount": "50.00",
"status": "completed",
"description": "Pagamento de conta para Nome do Destino",
"date": "YYYY-MM-DDTHH:MM:SS.000000Z",
"other_party_name": "Nome do Destino",
"direction": "Saída"
},
{
"id": 3,
"type": "Recebimento de Transferência",
"amount": "25.00",
"status": "completed",
"description": "Transferência de Nome do Remetente",
"date": "YYYY-MM-DDTHH:MM:SS.000000Z",
"other_party_name": "Nome do Remetente",
"direction": "Entrada"
}
]
Erros:

401 Unauthorized: Se o token for inválido ou não fornecido.

4. Reverter Transação
   URL: /api/transactions/{id}/reverse (onde {id} é o ID da transação a ser revertida)

Método: POST

Autenticação Necessária: Sim (Token Bearer)

Corpo da Requisição (JSON):

JSON

{
"reason": "Erro de digitação no valor."
}
Resposta Esperada (200 OK):

JSON

{
"message": "Transação #1 revertida com sucesso!",
"original_transaction_status": "reversed"
}
Erros:

401 Unauthorized: Se o token for inválido ou não fornecido.

400 Bad Request:

Se a transação já estiver reversed.

Se a transação for do tipo failed.

Se o tipo de transação não for suportado para reversão.

422 Unprocessable Entity: Se a validação do reason falhar.

500 Internal Server Error:

Se o saldo for insuficiente para realizar a reversão (ex: debitar da carteira que recebeu).

Qualquer outro erro inesperado durante o processo de reversão (ex: rollback).
