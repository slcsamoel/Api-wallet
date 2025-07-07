<?php

namespace App\Http\Controllers;

use App\Models\User;
use App\Models\Wallet;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

/**
 * @OA\Info(
 * title="API de Carteira Financeira",
 * version="1.0.0",
 * description="API RESTful para gerenciamento de carteiras, depósitos e transferências."
 * )
 * @OA\Server(
 * url=L5_SWAGGER_CONST_HOST,
 * description="Servidor API Local"
 * )
 * @OA\SecurityScheme(
 * securityScheme="bearerAuth",
 * type="http",
 * scheme="bearer",
 * bearerFormat="JWT"
 * )
 */

class AuthController extends Controller
{

    /**
     * @OA\Post(
     * path="/api/register",
     * summary="Registrar um novo usuário",
     * tags={"Autenticação"},
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * @OA\Property(property="name", type="string", example="João Silva"),
     * @OA\Property(property="email", type="string", format="email", example="joao.silva@example.com"),
     * @OA\Property(property="password", type="string", format="password", example="senhaforte123"),
     * @OA\Property(property="password_confirmation", type="string", format="password", example="senhaforte123")
     * )
     * ),
     * @OA\Response(
     * response=201,
     * description="Usuário registrado e carteira criada com sucesso!",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="Usuário registrado e carteira criada com sucesso!"),
     * @OA\Property(property="user", type="object", ref="#/components/schemas/User"),
     * @OA\Property(property="token", type="string", example="SEU_TOKEN_DE_AUTENTICACAO")
     * )
     * ),
     * @OA\Response(
     * response=422,
     * description="Dados de validação inválidos",
     * @OA\JsonContent(ref="#/components/schemas/ValidationError")
     * )
     * )
     */
    public function register(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|string|email|max:255|unique:users',
            'password' => 'required|string|min:8|confirmed',
        ]);

        $user = User::create([
            'name' => $request->name,
            'email' => $request->email,
            'password' => Hash::make($request->password),
        ]);

        // Cria uma carteira para o novo usuário
        $user->wallet()->create(['balance' => 0.00]);

        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Usuário registrado e carteira criada com sucesso!',
            'user' => $user,
            'token' => $token,
        ], 201);
    }


    /**
     * @OA\Post(
     * path="/api/login",
     * summary="Fazer login do usuário",
     * tags={"Autenticação"},
     * @OA\RequestBody(
     * required=true,
     * @OA\JsonContent(
     * @OA\Property(property="email", type="string", format="email", example="joao.silva@example.com"),
     * @OA\Property(property="password", type="string", format="password", example="senhaforte123")
     * )
     * ),
     * @OA\Response(
     * response=200,
     * description="Login realizado com sucesso!",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="Login realizado com sucesso!"),
     * @OA\Property(property="user", type="object", ref="#/components/schemas/User"),
     * @OA\Property(property="token", type="string", example="NOVO_TOKEN_DE_AUTENTICACAO")
     * )
     * ),
     * @OA\Response(
     * response=422,
     * description="Credenciais inválidas",
     * @OA\JsonContent(ref="#/components/schemas/ValidationError")
     * )
     * )
     */
    public function login(Request $request)
    {
        $request->validate([
            'email' => 'required|string|email',
            'password' => 'required|string',
        ]);

        $user = User::where('email', $request->email)->first();

        if (! $user || ! Hash::check($request->password, $user->password)) {

            return response()->json(['status' => 'error', 'message' => 'Credenciais fornecidas estão incorretas.'], 422);
        }

        $user->tokens()->delete(); // Revoga tokens antigos
        $token = $user->createToken('api-token')->plainTextToken;

        return response()->json([
            'status' => 'success',
            'message' => 'Login realizado com sucesso!',
            'user' => $user,
            'token' => $token,
        ], 200);
    }

    /**
     * @OA\Post(
     * path="/api/logout",
     * summary="Fazer logout do usuário",
     * tags={"Autenticação"},
     * security={{"bearerAuth":{}}},
     * @OA\Response(
     * response=200,
     * description="Logout realizado com sucesso!",
     * @OA\JsonContent(
     * @OA\Property(property="message", type="string", example="Logout realizado com sucesso!")
     * )
     * ),
     * @OA\Response(
     * response=401,
     * description="Não autenticado",
     * @OA\JsonContent(ref="#/components/schemas/UnauthorizedError")
     * )
     * )
     */
    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'status' => 'success',
            'message' => 'Logout realizado com sucesso!'
        ], 200);
    }
}
