<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use App\Models\User;
use App\Models\Wallet; // Importe o modelo Wallet
use Illuminate\Support\Facades\Hash;

class AuthTest extends TestCase
{
    use RefreshDatabase; // Garante um banco de dados limpo para cada teste

    /** @test */
    public function a_user_can_register()
    {
        $response = $this->postJson('/api/register', [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertStatus(201)
            ->assertJsonStructure(['message', 'user', 'token'])
            ->assertJson([
                'message' => 'Usuário registrado e carteira criada com sucesso!',
                'user' => [
                    'email' => 'test@example.com',
                ],
            ]);

        // Verifica se o usuário e a carteira foram criados no banco de dados
        $this->assertDatabaseHas('users', ['email' => 'test@example.com']);
        $this->assertDatabaseHas('wallets', ['user_id' => User::where('email', 'test@example.com')->first()->id, 'balance' => 0.00]);
    }

    /** @test */
    public function a_user_can_login_with_correct_credentials()
    {
        // Cria um usuário e sua carteira para o teste de login
        $user = User::factory()->create([
            'email' => 'login@example.com',
            'password' => Hash::make('password123'),
        ]);
        $user->wallet()->create(['balance' => 0.00]);

        $response = $this->postJson('/api/login', [
            'email' => 'login@example.com',
            'password' => 'password123',
        ]);

        $response->assertStatus(200)
            ->assertJsonStructure(['message', 'user', 'token']);
        $this->assertNotNull($response->json('token'));
    }

    /** @test */
    public function a_user_cannot_login_with_incorrect_credentials()
    {
        // Cria um usuário e sua carteira para o teste de login
        $user = User::factory()->create([
            'email' => 'login@example.com',
            'password' => Hash::make('password123'),
        ]);
        $user->wallet()->create(['balance' => 0.00]);

        $response = $this->postJson('/api/login', [
            'email' => 'login@example.com',
            'password' => 'wrong-password',
        ]);

        $response->assertStatus(422) // Unprocessable Entity
            ->assertJsonValidationErrors(['email']); // Espera um erro de validação no campo email
    }

    /** @test */
    public function an_authenticated_user_can_logout()
    {
        // Cria um usuário, sua carteira e um token para o teste de logout
        $user = User::factory()->create();
        $user->wallet()->create(['balance' => 0.00]);
        $token = $user->createToken('test_token')->plainTextToken;

        $response = $this->postJson('/api/logout', [], [
            'Authorization' => 'Bearer ' . $token // Envia o token no cabeçalho
        ]);

        $response->assertStatus(200)
            ->assertJson(['message' => 'Logout realizado com sucesso!']);

        // Assertiva para verificar se o token foi deletado do banco de dados
        $this->assertDatabaseMissing('personal_access_tokens', [
            'token' => hash('sha256', $token) // Tokens são armazenados como hashes SHA-256
        ]);
    }

    /** @test */
    public function an_authenticated_user_can_access_user_data()
    {
        // Cria um usuário, sua carteira e um token para acessar dados protegidos
        $user = User::factory()->create();
        $user->wallet()->create(['balance' => 0.00]);
        $token = $user->createToken('test_token')->plainTextToken;

        $response = $this->getJson('/api/user', [
            'Authorization' => 'Bearer ' . $token
        ]);

        $response->assertStatus(200)
            ->assertJson(['email' => $user->email]);
    }
}
