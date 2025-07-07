<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * @OA\Schema(
 * schema="Wallet",
 * title="Carteira do Usuário",
 * description="Detalhes da carteira financeira de um usuário.",
 * @OA\Property(property="wallet_id", type="integer", example=1),
 * @OA\Property(property="user_name", type="string", example="Nome do Usuário"),
 * @OA\Property(property="balance", type="string", format="float", example="123.45"),
 * @OA\Property(property="is_negative_flag", type="boolean", example=false)
 * )
 * @OA\Schema(
 * schema="UnauthorizedError",
 * title="Erro de Autenticação",
 * description="Resposta para requisições não autenticadas.",
 * @OA\Property(property="message", type="string", example="Unauthenticated.")
 * )
 * @OA\Schema(
 * schema="NotFoundError",
 * title="Recurso Não Encontrado",
 * description="Resposta para recursos não encontrados.",
 * @OA\Property(property="message", type="string", example="Carteira não encontrada para este usuário.")
 * )
 * @OA\Schema(
 * schema="ForbiddenError",
 * title="Acesso Proibido",
 * description="Resposta para ações proibidas (ex: carteira com inconsistência).",
 * @OA\Property(property="message", type="string", example="Não é possível depositar. Sua carteira está com uma inconsistência que impede novos depósitos. Por favor, contate o suporte.")
 * )
 * @OA\Schema(
 * schema="ValidationError",
 * title="Erro de Validação",
 * description="Resposta para falhas de validação de dados.",
 * @OA\Property(property="message", type="string", example="The given data was invalid."),
 * @OA\Property(property="errors", type="object", example={"field_name": {"The field_name field is required."}})
 * )
 * @OA\Schema(
 * schema="InternalServerError",
 * title="Erro Interno do Servidor",
 * description="Resposta para erros inesperados do servidor.",
 * @OA\Property(property="message", type="string", example="Ocorreu um erro interno do servidor."),
 * @OA\Property(property="error", type="string", example="Detalhes do erro para debug (remover em produção)")
 * )
 */
class WalletController extends Controller
{

    /**
     * @OA\Get(
     * path="/api/wallet",
     * summary="Consultar saldo da carteira do usuário autenticado",
     * tags={"Carteira"},
     * security={{"bearerAuth":{}}},
     * @OA\Response(
     * response=200,
     * description="Saldo da carteira consultado com sucesso!",
     * @OA\JsonContent(ref="#/components/schemas/Wallet")
     * ),
     * @OA\Response(
     * response=401,
     * description="Não autenticado",
     * @OA\JsonContent(ref="#/components/schemas/UnauthorizedError")
     * ),
     * @OA\Response(
     * response=404,
     * description="Carteira não encontrada",
     * @OA\JsonContent(ref="#/components/schemas/NotFoundError")
     * )
     * )
     */
    public function show(Request $request)
    {
        // O usuário autenticado tem um relacionamento direto com sua carteira
        $wallet = $request->user()->wallet;

        if (!$wallet) {
            return response()->json(['status' => 'error', 'message' => 'Carteira não encontrada para este usuário.'], 404);
        }

        return response()->json([
            'status' => 'success',
            'user_name' => $request->user()->name,
            'wallet_id' => $wallet->id,
            'balance' => $wallet->balance,
            'is_negative_flag' => $wallet->is_negative,
        ], 200);
    }
}
