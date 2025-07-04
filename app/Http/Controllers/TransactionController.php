<?php

namespace App\Http\Controllers;

use App\Models\Wallet;
use App\Models\Transaction;
use App\Models\Reversal;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Facades\Log;
use App\Models\User;

class TransactionController extends Controller
{
    /**
     * Realiza um depósito na carteira do usuário.
     */
    public function deposit(Request $request)
    {
        $request->validate([
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:255',
        ]);

        $user = $request->user();
        $wallet = $user->wallet;
        $amount = $request->amount;

        // Regra: Se o saldo for negativo devido a um problema, não aceitar depósitos.
        if ($wallet->is_negative) {
            return response()->json([
                'message' => 'Não é possível depositar. Sua carteira está com uma flag de saldo negativo inconsistente.'
            ], 403);
        }

        DB::beginTransaction();
        try {
            $wallet->balance += $amount;
            $wallet->save();

            Transaction::create([
                'to_wallet_id' => $wallet->id,
                'amount' => $amount,
                'type' => 'deposit',
                'status' => 'completed',
                'description' => $request->description,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Depósito realizado com sucesso!',
                'new_balance' => $wallet->balance,
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro no depósito para o usuário ' . $user->id . ': ' . $e->getMessage());
            return response()->json([
                'message' => 'Ocorreu um erro ao processar o depósito. Tente novamente mais tarde.'
            ], 500);
        }
    }

    /**
     * Realiza uma transferência de dinheiro para outro usuário.
     */
    public function transfer(Request $request)
    {
        $request->validate([
            'to_user_email' => 'required|email|exists:users,email', // Valida se o email do destinatário existe
            'amount' => 'required|numeric|min:0.01',
            'description' => 'nullable|string|max:255',
        ]);

        $fromUser = $request->user();
        $fromWallet = $fromUser->wallet;
        $toUser = User::where('email', $request->to_user_email)->first();
        $toWallet = $toUser->wallet;
        $amount = $request->amount;

        // Não permitir transferência para si mesmo
        if ($fromWallet->id === $toWallet->id) {
            throw ValidationException::withMessages([
                'to_user_email' => 'Não é possível transferir para sua própria carteira.'
            ]);
        }

        // Regra: Um usuário deve ter saldo suficiente antes de fazer uma transferência.
        if ($fromWallet->balance < $amount) {
            throw ValidationException::withMessages([
                'amount' => 'Saldo insuficiente para a transferência.'
            ]);
        }

        // Regra: Se a carteira de destino estiver com a flag negativa, não permitir receber.
        if ($toWallet->is_negative) {
            return response()->json([
                'message' => 'Não é possível transferir para este usuário. A carteira dele está com uma inconsistência.'
            ], 403);
        }

        DB::beginTransaction();
        try {
            // Débito da carteira de origem
            $fromWallet->balance -= $amount;
            $fromWallet->save();

            // Crédito na carteira de destino
            $toWallet->balance += $amount;
            $toWallet->save();

            // Registro da transação de saída
            Transaction::create([
                'from_wallet_id' => $fromWallet->id,
                'to_wallet_id' => $toWallet->id,
                'amount' => $amount,
                'type' => 'transfer',
                'status' => 'completed',
                'description' => $request->description . ' (Envio)',
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Transferência realizada com sucesso!',
                'your_new_balance' => $fromWallet->balance,
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro na transferência de ' . $fromUser->id . ' para ' . $toUser->id . ': ' . $e->getMessage());
            return response()->json([
                'message' => 'Ocorreu um erro ao processar a transferência. Tente novamente mais tarde.'
            ], 500);
        }
    }

    /**
     * Lista as transações do usuário autenticado.
     */
    public function index(Request $request)
    {
        $user = $request->user();
        $walletId = $user->wallet->id;

        $transactions = Transaction::where(function ($query) use ($walletId) {
            $query->where('from_wallet_id', $walletId)
                ->orWhere('to_wallet_id', $walletId);
        })
            ->orderBy('created_at', 'desc')
            ->with(['fromWallet.user', 'toWallet.user']) // Carrega usuários relacionados
            ->get();

        // Formata a saída para ser mais amigável
        $formattedTransactions = $transactions->map(function ($transaction) use ($walletId) {
            $isIncoming = ($transaction->to_wallet_id === $walletId);
            $typeDisplay = $transaction->type;
            $otherPartyName = null;

            if ($transaction->type === 'transfer') {
                if ($isIncoming) {
                    $typeDisplay = 'Recebimento de Transferência';
                    $otherPartyName = $transaction->fromWallet->user->name ?? 'Usuário Desconhecido';
                } else {
                    $typeDisplay = 'Envio de Transferência';
                    $otherPartyName = $transaction->toWallet->user->name ?? 'Usuário Desconhecido';
                }
            } elseif ($transaction->type === 'deposit') {
                $typeDisplay = 'Depósito';
            } elseif (str_starts_with($transaction->type, 'reversal')) {
                if ($transaction->type === 'reversal_deposit') {
                    $typeDisplay = 'Reversão de Depósito';
                } elseif ($transaction->type === 'reversal_transfer_out') {
                    $typeDisplay = 'Reversão de Transferência Enviada';
                } elseif ($transaction->type === 'reversal_transfer_in') {
                    $typeDisplay = 'Reversão de Transferência Recebida';
                }
                // Para reversões, podemos tentar identificar a transação original se houver um relacionamento
                $otherPartyName = $transaction->reversal ? 'Reversão da Transação #' . $transaction->reversal->transaction_id : null;
            }


            return [
                'id' => $transaction->id,
                'type' => $typeDisplay,
                'amount' => $transaction->amount,
                'status' => $transaction->status,
                'description' => $transaction->description,
                'date' => $transaction->created_at->toDateTimeString(),
                'from_user' => $transaction->fromWallet->user->name ?? ($transaction->type === 'deposit' ? 'Sistema' : null),
                'to_user' => $transaction->toWallet->user->name ?? null,
                'other_party' => $otherPartyName, // Adiciona quem foi o outro envolvido na transação
                'direction' => $isIncoming ? 'Entrada' : 'Saída', // Indica se é entrada ou saída para a carteira do usuário
            ];
        });

        return response()->json($formattedTransactions, 200);
    }


    /**
     * Reverte uma transação específica.
     * Esta é uma função sensível e deve ter controle de acesso rigoroso (e.g., admin only).
     */
    public function reverse(Request $request, Transaction $transaction)
    {
        // Validação básica: verificar se a transação já foi revertida
        if ($transaction->status === 'reversed') {
            return response()->json(['message' => 'Esta transação já foi revertida.'], 400);
        }
        // Validação: Não permitir reverter transações que já falharam
        if ($transaction->status === 'failed') {
            return response()->json(['message' => 'Transações falhas não podem ser revertidas.'], 400);
        }

        $request->validate([
            'reason' => 'nullable|string|max:500',
        ]);

        DB::beginTransaction();
        try {
            $fromWallet = $transaction->fromWallet;
            $toWallet = $transaction->toWallet;
            $amount = $transaction->amount;
            $reversalType = null;

            if ($transaction->type === 'deposit') {
                // Reverter depósito: subtrair da carteira de destino (onde o depósito foi feito)
                if ($toWallet->balance < $amount) {
                    throw new \Exception('Saldo insuficiente para reverter o depósito na carteira de destino.');
                }
                $toWallet->balance -= $amount;
                $toWallet->save();
                $reversalType = 'reversal_deposit';
            } elseif ($transaction->type === 'transfer') {
                // Reverter transferência:
                // 1. Reverter débito da origem (creditar na origem)
                if ($fromWallet) { // fromWallet pode ser null para depósitos
                    $fromWallet->balance += $amount;
                    $fromWallet->save();
                }

                // 2. Reverter crédito do destino (debitar do destino)
                if ($toWallet->balance < $amount) {
                    throw new \Exception('Saldo insuficiente para reverter o crédito na carteira de destino.');
                }
                $toWallet->balance -= $amount;
                $toWallet->save();

                // Cria duas transações de reversão: uma para a saída e outra para a entrada
                Transaction::create([
                    'from_wallet_id' => $toWallet->id, // A carteira que recebeu está agora 'enviando' de volta
                    'to_wallet_id' => $fromWallet ? $fromWallet->id : null, // A carteira que enviou está agora 'recebendo' de volta
                    'amount' => $amount,
                    'type' => 'reversal_transfer_out',
                    'status' => 'completed',
                    'description' => 'Reversão de transferência #' . $transaction->id . ' (débito do recebedor)',
                ]);
                Transaction::create([
                    'from_wallet_id' => $fromWallet ? $fromWallet->id : null, // A carteira que enviou está agora 'recebendo' de volta
                    'to_wallet_id' => $toWallet->id, // A carteira que recebeu está agora 'enviando' de volta
                    'amount' => $amount,
                    'type' => 'reversal_transfer_in',
                    'status' => 'completed',
                    'description' => 'Reversão de transferência #' . $transaction->id . ' (crédito no remetente)',
                ]);
            } else {
                DB::rollBack();
                return response()->json(['message' => 'Tipo de transação não suportado para reversão.'], 400);
            }

            // Marcar a transação original como revertida
            $transaction->status = 'reversed';
            $transaction->save();

            // Registrar a reversão
            Reversal::create([
                'transaction_id' => $transaction->id,
                'reversed_by_user_id' => $request->user()->id, // O usuário que solicitou a reversão
                'reason' => $request->reason,
            ]);

            DB::commit();

            return response()->json([
                'message' => 'Transação #' . $transaction->id . ' revertida com sucesso!',
                'original_transaction_status' => $transaction->status,
            ], 200);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Erro ao reverter transação ' . $transaction->id . ': ' . $e->getMessage());
            return response()->json([
                'message' => 'Ocorreu um erro ao reverter a transação: ' . $e->getMessage()
            ], 500);
        }
    }
}
