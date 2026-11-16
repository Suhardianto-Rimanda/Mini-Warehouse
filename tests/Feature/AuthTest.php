<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\ClientRepository;
use Tests\TestCase;

class AuthTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $key = openssl_pkey_new([
            'private_key_bits' => 2048,
            'private_key_type' => OPENSSL_KEYTYPE_RSA,
        ]);
        openssl_pkey_export($key, $privateKey);

        config([
            'passport.private_key' => $privateKey,
            'passport.public_key' => openssl_pkey_get_details($key)['key'],
        ]);

        app(ClientRepository::class)->createPersonalAccessGrantClient('Testing', 'users');
    }

    public function test_user_can_register(): void
    {
        $response = $this->postJson('/api/auth/register', [
            'name' => 'Suhardi Testing',
            'email' => 'suhardi@testing.com',
            'password' => 'password',
            'password_confirmation' => 'password',
        ])
            ->assertCreated()
            ->assertJsonPath('message', 'User registered successfully')
            ->assertJsonPath('user.name', 'Suhardi Testing')
            ->assertJsonPath('user.email', 'suhardi@testing.com')
            ->assertJsonMissingPath('user.password');

        $this->assertDatabaseHas('users', [
            'id' => $response->json('user.id'),
            'name' => 'Suhardi Testing',
            'email' => 'suhardi@testing.com',
        ]);

        $user = User::findOrFail($response->json('user.id'));

        $this->assertTrue(Hash::check('password', $user->password));
    }

    public function test_user_can_login(): void
    {
        $user = User::factory()->create();

        $response = $this->postJson('/api/auth/login', [
            'email' => $user->email,
            'password' => 'password',
        ])
            ->assertOk()
            ->assertJsonPath('message', 'Login Success.')
            ->assertJsonPath('token_type', 'Bearer')
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('user.email', $user->email)
            ->assertJsonMissingPath('user.password');

        $this->assertIsString($response->json('access_token'));
        $this->assertGreaterThan(0, $response->json('expires_in'));
        $this->assertDatabaseHas('oauth_access_tokens', [
            'user_id' => $user->id,
            'name' => 'Mini-Warehouse',
            'revoked' => false,
        ]);

        $this->withToken($response->json('access_token'))
            ->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('user.id', $user->id);
    }

    public function test_user_can_logout(): void
    {
        $user = User::factory()->create();
        $token = $user->createToken('Mini-Warehouse');

        $this->withToken($token->accessToken)
            ->postJson('/api/auth/logout')
            ->assertOk()
            ->assertJsonPath('message', 'Logged out successfully.');

        $this->assertDatabaseHas('oauth_access_tokens', [
            'id' => $token->accessTokenId,
            'user_id' => $user->id,
            'revoked' => true,
        ]);

        $this->app['auth']->forgetGuards();

        $this->withToken($token->accessToken)
            ->getJson('/api/auth/me')
            ->assertUnauthorized();
    }
}
