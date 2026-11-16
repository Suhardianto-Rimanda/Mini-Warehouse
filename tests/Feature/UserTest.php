<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Passport\Passport;
use League\OAuth2\Server\ResourceServer;
use Tests\TestCase;

class UserTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Avoid requiring local OAuth keys for Passport::actingAs.
        $this->mock(ResourceServer::class);
        Passport::actingAs(User::factory()->create());
    }

    public function test_authenticated_user_can_list_users(): void
    {
        $users = User::factory()->count(2)->create();

        $this->getJson('/api/users')
            ->assertOk()
            ->assertJsonCount(3)
            ->assertJsonFragment([
                'id' => $users->first()->id,
                'name' => $users->first()->name,
                'email' => $users->first()->email,
            ]);
    }

    public function test_authenticated_user_can_create_user(): void
    {
        $response = $this->postJson('/api/users', [
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertCreated()
            ->assertJsonPath('name', 'Budi Santoso')
            ->assertJsonPath('email', 'budi@example.com')
            ->assertJsonMissingPath('password');

        $this->assertDatabaseHas('users', [
            'id' => $response->json('id'),
            'name' => 'Budi Santoso',
            'email' => 'budi@example.com',
        ]);

        $user = User::findOrFail($response->json('id'));

        $this->assertTrue(Hash::check('password123', $user->password));
    }

    public function test_authenticated_user_can_show_user(): void
    {
        $user = User::factory()->create();

        $this->getJson('/api/users/'.$user->id)
            ->assertOk()
            ->assertJsonPath('id', $user->id)
            ->assertJsonPath('name', $user->name)
            ->assertJsonPath('email', $user->email)
            ->assertJsonMissingPath('password');
    }

    public function test_authenticated_user_can_update_user(): void
    {
        $user = User::factory()->create();
        $password = $user->password;

        $this->putJson('/api/users/'.$user->id, [
            'name' => 'Budi Diperbarui',
            'email' => 'budi.updated@example.com',
        ])
            ->assertOk()
            ->assertJsonPath('data.id', $user->id)
            ->assertJsonPath('data.name', 'Budi Diperbarui')
            ->assertJsonPath('data.email', 'budi.updated@example.com');

        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Budi Diperbarui',
            'email' => 'budi.updated@example.com',
            'email_verified_at' => null,
            'password' => $password,
        ]);
    }

    public function test_authenticated_user_can_delete_user(): void
    {
        $user = User::factory()->create();

        $this->deleteJson('/api/users/'.$user->id)
            ->assertOk()
            ->assertJsonPath('message', 'User successfully deleted.');

        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }
}
