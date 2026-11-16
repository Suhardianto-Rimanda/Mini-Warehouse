<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use League\OAuth2\Server\ResourceServer;
use Tests\TestCase;

class MeTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_get_the_authenticated_user(): void
    {
        $user = User::factory()->create();
        $this->mock(ResourceServer::class);
        Passport::actingAs($user);

        $this->getJson('/api/auth/me')
            ->assertOk()
            ->assertJsonPath('user.id', $user->id)
            ->assertJsonPath('user.name', $user->name)
            ->assertJsonPath('user.email', $user->email)
            ->assertJsonMissingPath('user.password');
    }
}
