<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\WalletController;
use App\Http\Controllers\TransactionController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Rotas públicas (Registro e Login)
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

// Rotas protegidas (requerem token de autenticação)
Route::middleware('auth:sanctum')->group(function () {
    Route::get('/user', function (Request $request) {
        return $request->user();
    });
    Route::post('/logout', [AuthController::class, 'logout']);

    // Rotas da Carteira
    Route::get('/wallet', [WalletController::class, 'show']);

    // Rotas de Transações
    Route::post('/deposit', [TransactionController::class, 'deposit']);
    Route::post('/transfer', [TransactionController::class, 'transfer']);
    Route::get('/transactions', [TransactionController::class, 'index']);
    Route::post('/transactions/{transaction}/reverse', [TransactionController::class, 'reverse']);
});
