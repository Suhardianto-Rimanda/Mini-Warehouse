<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use League\OAuth2\Server\ResourceServer;
use Tests\TestCase;

class CategoryTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Avoid requiring local OAuth keys for Passport::actingAs.
        $this->mock(ResourceServer::class);
        Passport::actingAs(User::factory()->create());
    }

    public function test_can_list_categories(): void
    {
        $categories = Category::factory()->count(2)->create();

        $this->getJson('/api/categories')
            ->assertOk()
            ->assertJsonCount(2)
            ->assertJsonFragment(['id' => $categories[0]->id, 'name' => $categories[0]->name])
            ->assertJsonFragment(['id' => $categories[1]->id, 'name' => $categories[1]->name]);
    }

    public function test_can_create_a_category(): void
    {
        $data = ['name' => 'Sembako'];

        $response = $this->postJson('/api/categories', $data)
            ->assertCreated()
            ->assertJsonPath('name', $data['name']);

        $this->assertDatabaseHas('categories', [
            'id' => $response->json('id'),
            ...$data,
        ]);
    }

    public function test_can_show_a_category(): void
    {
        $category = Category::factory()->create();

        $this->getJson('/api/categories/'.$category->id)
            ->assertOk()
            ->assertJsonPath('id', $category->id)
            ->assertJsonPath('name', $category->name);
    }

    public function test_can_update_a_category(): void
    {
        $category = Category::factory()->create(['name' => 'Sembako']);
        $data = ['name' => 'Minuman'];

        $this->putJson('/api/categories/'.$category->id, $data)
            ->assertOk()
            ->assertJsonPath('id', $category->id)
            ->assertJsonPath('name', $data['name']);

        $this->assertDatabaseHas('categories', ['id' => $category->id, ...$data]);
    }

    public function test_can_delete_a_category(): void
    {
        $category = Category::factory()->create();

        $this->deleteJson('/api/categories/'.$category->id)
            ->assertOk()
            ->assertJsonPath('message', 'Kategori berhasil dihapus.');

        $this->assertDatabaseMissing('categories', ['id' => $category->id]);
    }
}
