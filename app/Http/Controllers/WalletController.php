<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

class WalletController extends Controller
{
    public function show(Request $request)
    {
        // O usuário autenticado tem um relacionamento direto com sua carteira
        $wallet = $request->user()->wallet;

        if (!$wallet) {
            return response()->json(['message' => 'Carteira não encontrada para este usuário.'], 404);
        }

        return response()->json([
            'user_name' => $request->user()->name,
            'wallet_id' => $wallet->id,
            'balance' => $wallet->balance,
            'is_negative_flag' => $wallet->is_negative,
        ], 200);
    }
}
