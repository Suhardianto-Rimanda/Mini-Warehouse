<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use League\OAuth2\Server\ResourceServer;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->mock(ResourceServer::class);
        Passport::actingAs(User::factory()->create());
    }

    public function test_can_list_products(): void
    {
        $product = Product::factory()->create();

        $this->getJson('/api/products')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', $product->id)
            ->assertJsonPath('0.name', $product->name)
            ->assertJsonPath('0.category.id', $product->category_id);
    }

    public function test_can_create_product(): void
    {
        $category = Category::factory()->create();
        $data = [
            'category_id' => $category->id,
            'sku' => 'BRG-001',
            'name' => 'Beras 5 kg',
            'unit' => 'karung',
        ];

        $response = $this->postJson('/api/products', $data)
            ->assertOk()
            ->assertJsonPath('sku', $data['sku'])
            ->assertJsonPath('name', $data['name'])
            ->assertJsonPath('unit', $data['unit'])
            ->assertJsonPath('stock', 0)
            ->assertJsonPath('category.id', $category->id);

        $this->assertDatabaseHas('products', [
            'id' => $response->json('id'),
            ...$data,
            'stock' => 0,
        ]);
    }

    public function test_can_show_product(): void
    {
        $product = Product::factory()->create();

        $this->getJson("/api/products/{$product->id}")
            ->assertOk()
            ->assertJsonPath('id', $product->id)
            ->assertJsonPath('sku', $product->sku)
            ->assertJsonPath('name', $product->name)
            ->assertJsonPath('unit', $product->unit)
            ->assertJsonPath('stock', $product->stock)
            ->assertJsonPath('category.id', $product->category_id);
    }

    public function test_can_update_product(): void
    {
        $product = Product::factory()->create();
        $category = Category::factory()->create();
        $data = [
            'category_id' => $category->id,
            'sku' => 'BRG-UPDATED',
            'name' => 'Gula Pasir 1 kg',
            'unit' => 'bungkus',
        ];

        $this->putJson("/api/products/{$product->id}", $data)
            ->assertOk()
            ->assertJsonPath('id', $product->id)
            ->assertJsonPath('sku', $data['sku'])
            ->assertJsonPath('name', $data['name'])
            ->assertJsonPath('unit', $data['unit'])
            ->assertJsonPath('stock', 0)
            ->assertJsonPath('category.id', $category->id);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            ...$data,
            'stock' => 0,
        ]);
    }

    public function test_can_delete_product(): void
    {
        $product = Product::factory()->create();

        $this->deleteJson("/api/products/{$product->id}")
            ->assertOk()
            ->assertJsonPath('message', 'Product successfully deleted.');

        $this->assertDatabaseMissing('products', ['id' => $product->id]);
    }
}
