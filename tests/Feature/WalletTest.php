<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Wallet;

class WalletTest extends TestCase
{
    use RefreshDatabase; // Limpa o banco de dados antes de cada teste

    /** @test */
    public function an_authenticated_user_can_view_their_wallet_balance()
    {
        // Cria um usuário e uma carteira com saldo para o teste
        $user = User::factory()->create();
        $wallet = $user->wallet()->create(['balance' => 123.45]);
        $token = $user->createToken('test_token')->plainTextToken;

        $response = $this->getJson('/api/wallet', [
            'Authorization' => 'Bearer ' . $token // Autentica a requisição
        ]);

        $response->assertStatus(200)
            ->assertJson([
                'user_name' => $user->name,
                'wallet_id' => $wallet->id,
                'balance' => '123.45', // Valores decimais são frequentemente retornados como strings em JSON
                'is_negative_flag' => false,
            ]);
    }

    /** @test */
    public function an_unauthenticated_user_cannot_view_wallet_balance()
    {
        // Tenta acessar a rota da carteira sem autenticação
        $response = $this->getJson('/api/wallet');
        $response->assertStatus(401); // Espera status "Unauthorized"
    }

    /** @test */
    public function wallet_is_created_for_new_user_on_registration()
    {
        // Simula um registro de usuário
        $response = $this->postJson('/api/register', [
            'name' => 'New User',
            'email' => 'newuser@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201); // Espera status "Created"

        // Verifica se a carteira foi criada e com saldo inicial de 0
        $user = User::where('email', 'newuser@example.com')->first();
        $this->assertNotNull($user->wallet);
        $this->assertEquals(0.00, $user->wallet->balance);
    }
}
