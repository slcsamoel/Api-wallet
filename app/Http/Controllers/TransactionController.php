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

/**
 * @OA\Schema(
 * schema="Transaction",
 * title="Transação Financeira",
 * description="Detalhes de uma transação de depósito ou transferência.",
 * @OA\Property(property="id", type="integer", example=1),
 * @OA\Property(property="type", type="string", enum={"Depósito", "Envio de Transferência", "Recebimento de Transferência", "Reversão de Depósito", "Reversão de Transferência Enviada", "Reversão de Transferência Recebida"}, example="Depósito"),
 * @OA\Property(property="amount", type="string", format="float", example="100.00"),
 * @OA\Property(property="status", type="string", enum={"completed", "failed", "reversed"}, example="completed"),
 * @OA\Property(property="description", type="string", example="Depósito de salário"),
 * @OA\Property(property="date", type="string", format="date-time", example="2025-07-04T18:20:27.000000Z"),
 * @OA\Property(property="other_party_name", type="string", example="Sistema"),
 * @OA\Property(property="direction", type="string", enum={"Entrada", "Saída"}, example="Entrada")
 * )
 */

class TransactionController extends Controller
{
    /**
     * @OA\Post(
     * path="/api/deposit",
     * summary="Realizar um depósito na carteira do usuário",
     * tags={"Transações"},
     * security={{"bearerAuth":{}}},
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * @OA\Property(property="amount", type="number", format="float", example=100.50),
     * @OA\Property(property="description", type="string", example="Depósito de salário", nullable=true)
     * )
     * ),
     * @OA\Response(
     * response=200,
     * description="Depósito realizado com sucesso!",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="Depósito realizado com sucesso!"),
     * @OA\Property(property="new_balance", type="string", format="float", example="223.95")
     * )
     * ),
     * @OA\Response(
     * response=401,
     * description="Não autenticado",
     * @OA\JsonContent(ref="#/components/schemas/UnauthorizedError")
     * ),
     * @OA\Response(
     * response=422,
     * description="Dados de validação inválidos",
     * @OA\JsonContent(ref="#/components/schemas/ValidationError")
     * ),
     * @OA\Response(
     * response=403,
     * description="Ação proibida (carteira com inconsistência)",
     * @OA\JsonContent(ref="#/components/schemas/ForbiddenError")
     * ),
     * @OA\Response(
     * response=500,
     * description="Erro interno do servidor",
     * @OA\JsonContent(ref="#/components/schemas/InternalServerError")
     * )
     * )
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
     * @OA\Post(
     * path="/api/transfer",
     * summary="Realizar uma transferência para outro usuário",
     * tags={"Transações"},
     * security={{"bearerAuth":{}}},
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * @OA\Property(property="to_user_email", type="string", format="email", example="destino@example.com"),
     * @OA\Property(property="amount", type="number", format="float", example=50.00),
     * @OA\Property(property="description", type="string", example="Pagamento de conta", nullable=true)
     * )
     * ),
     * @OA\Response(
     * response=200,
     * description="Transferência realizada com sucesso!",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="Transferência realizada com sucesso!"),
     * @OA\Property(property="your_new_balance", type="string", format="float", example="173.95")
     * )
     * ),
     * @OA\Response(
     * response=401,
     * description="Não autenticado",
     * @OA\JsonContent(ref="#/components/schemas/UnauthorizedError")
     * ),
     * @OA\Response(
     * response=422,
     * description="Dados de validação inválidos",
     * @OA\JsonContent(ref="#/components/schemas/ValidationError")
     * ),
     * @OA\Response(
     * response=403,
     * description="Ação proibida (carteira de destino com inconsistência)",
     * @OA\JsonContent(ref="#/components/schemas/ForbiddenError")
     * ),
     * @OA\Response(
     * response=500,
     * description="Erro interno do servidor",
     * @OA\JsonContent(ref="#/components/schemas/InternalServerError")
     * )
     * )
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

            return response()->json([
                'status' => 'error',
                'message' => 'Não é possível transferir para sua própria carteira.'
            ], 403);
        }

        // Regra: Um usuário deve ter saldo suficiente antes de fazer uma transferência.
        if ($fromWallet->balance < $amount) {
            return response()->json([
                'status' => 'error',
                'message' => 'Saldo insuficiente para a transferência.'
            ], 403);
        }

        // Regra: Se a carteira de destino estiver com a flag negativa, não permitir receber.
        if ($toWallet->is_negative) {
            return response()->json([
                'status' => 'error',
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
                'status' => 'success',
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
     * @OA\Get(
     * path="/api/transactions",
     * summary="Listar histórico de transações do usuário autenticado",
     * tags={"Transações"},
     * security={{"bearerAuth":{}}},
     * @OA\Response(
     * response=200,
     * description="Histórico de transações recuperado com sucesso!",
     * @OA\JsonContent(
     * type="array",
     * @OA\Items(ref="#/components/schemas/Transaction")
     * )
     * ),
     * @OA\Response(
     * response=401,
     * description="Não autenticado",
     * @OA\JsonContent(ref="#/components/schemas/UnauthorizedError")
     * )
     * )
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
                // Para transações de reversão, o outro partido é a transação original
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
                'other_party' => $otherPartyName,
                'direction' => $isIncoming ? 'Entrada' : 'Saída',
            ];
        });

        return response()->json($formattedTransactions, 200);
    }


    /**
     * @OA\Post(
     * path="/api/transactions/{id}/reverse",
     * summary="Reverter uma transação (depósito ou transferência)",
     * tags={"Transações"},
     * security={{"bearerAuth":{}}},
     * @OA\Parameter(
     * name="id",
     * in="path",
     * required=true,
     * description="ID da transação a ser revertida",
     * @OA\Schema(
     * type="integer",
     * format="int64",
     * example=1
     * )
     * ),
     * @OA\RequestBody(
     * required=false,
     * @OA\JsonContent(
     * @OA\Property(property="reason", type="string", example="Erro de digitação no valor.", nullable=true)
     * )
     * ),
     * @OA\Response(
     * response=200,
     * description="Transação revertida com sucesso!",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="Transação #1 revertida com sucesso!"),
     * @OA\Property(property="original_transaction_status", type="string", example="reversed")
     * )
     * ),
     * @OA\Response(
     * response=400,
     * description="Requisição inválida (transação já revertida, falhou ou tipo não suportado)",
     * @OA\JsonContent(ref="#/components/schemas/ForbiddenError")
     * ),
     * @OA\Response(
     * response=401,
     * description="Não autenticado",
     * @OA\JsonContent(ref="#/components/schemas/UnauthorizedError")
     * ),
     * @OA\Response(
     * response=422,
     * description="Dados de validação inválidos",
     * @OA\JsonContent(ref="#/components/schemas/ValidationError")
     * ),
     * @OA\Response(
     * response=500,
     * description="Erro interno do servidor (ex: saldo insuficiente para reversão)",
     * @OA\JsonContent(ref="#/components/schemas/InternalServerError")
     * )
     * )
     */
    public function reverse(Request $request, Transaction $transaction)
    {
        // verificar se a transação já foi revertida
        if ($transaction->status === 'reversed') {
            return response()->json(['status' => 'error', 'message' => 'Esta transação já foi revertida.'], 400);
        }
        // Não permitir reverter transações que já falharam
        if ($transaction->status === 'failed') {
            return response()->json(['status' => 'error', 'message' => 'Transações falhas não podem ser revertidas.'], 400);
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
                // Reverter débito da origem (creditar na origem)
                if ($fromWallet) {
                    $fromWallet->balance += $amount;
                    $fromWallet->save();
                }

                //Reverter crédito do destino (debitar do destino)
                if ($toWallet->balance < $amount) {
                    throw new \Exception('Saldo insuficiente para reverter o crédito na carteira de destino.');
                }
                $toWallet->balance -= $amount;
                $toWallet->save();

                // Cria duas transações de reversão: uma para a saída e outra para a entrada
                // A carteira que recebeu está agora 'enviando' de volta
                Transaction::create([
                    'from_wallet_id' => $toWallet->id,
                    'to_wallet_id' => $fromWallet ? $fromWallet->id : null,
                    'amount' => $amount,
                    'type' => 'reversal_transfer_out',
                    'status' => 'completed',
                    'description' => 'Reversão de transferência #' . $transaction->id . ' (débito do recebedor)',
                ]);

                // A carteira que enviou está agora 'recebendo' de volta
                Transaction::create([
                    'from_wallet_id' => $fromWallet ? $fromWallet->id : null,
                    'to_wallet_id' => $toWallet->id,
                    'amount' => $amount,
                    'type' => 'reversal_transfer_in',
                    'status' => 'completed',
                    'description' => 'Reversão de transferência #' . $transaction->id . ' (crédito no remetente)',
                ]);
            } else {
                DB::rollBack();
                return response()->json(['status' => 'error', 'message' => 'Tipo de transação não suportado para reversão.'], 400);
            }

            // Marcar a transação original como revertida
            $transaction->status = 'reversed';
            $transaction->save();

            // Registrar a reversão com usuário que solicitou a reversão
            Reversal::create([
                'transaction_id' => $transaction->id,
                'reversed_by_user_id' => $request->user()->id,
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
                'status' => 'error',
                'message' => 'Ocorreu um erro ao reverter a transação: ' . $e->getMessage()
            ], 500);
        }
    }
}
