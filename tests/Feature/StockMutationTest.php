<?php

namespace Tests\Feature;

use App\Models\Product;
use App\Models\StockMutation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Laravel\Passport\Passport;
use League\OAuth2\Server\ResourceServer;
use Tests\TestCase;

class StockMutationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        // Avoid requiring local OAuth keys for Passport::actingAs.
        $this->mock(ResourceServer::class);
        $this->user = User::factory()->create();
        Passport::actingAs($this->user);
    }

    public function test_can_list_stock_mutations(): void
    {
        $product = Product::factory()->create(['stock' => 10]);
        $stockMutation = StockMutation::factory()->for($product)->for($this->user)->create([
            'quantity' => 10,
        ]);

        $this->getJson('/api/stock-mutations')
            ->assertOk()
            ->assertJsonCount(1)
            ->assertJsonPath('0.id', $stockMutation->id)
            ->assertJsonPath('0.product.id', $product->id)
            ->assertJsonPath('0.user.id', $this->user->id);
    }

    public function test_can_create_stock_mutation(): void
    {
        $product = Product::factory()->create();
        $data = [
            'product_id' => $product->id,
            'type' => 'in',
            'quantity' => 10,
            'notes' => 'Stok awal.',
        ];

        $response = $this->postJson('/api/stock-mutations', $data)
            ->assertCreated()
            ->assertJsonPath('type', 'in')
            ->assertJsonPath('quantity', 10)
            ->assertJsonPath('notes', 'Stok awal.')
            ->assertJsonPath('product.id', $product->id)
            ->assertJsonPath('product.stock', 10)
            ->assertJsonPath('user.id', $this->user->id);

        $this->assertDatabaseHas('stock_mutations', [
            'id' => $response->json('id'),
            'user_id' => $this->user->id,
            ...$data,
        ]);
        $this->assertSame(10, $product->fresh()->stock);
    }

    public function test_can_show_stock_mutation(): void
    {
        $product = Product::factory()->create(['stock' => 10]);
        $stockMutation = StockMutation::factory()->for($product)->for($this->user)->create([
            'quantity' => 10,
        ]);

        $this->getJson('/api/stock-mutations/'.$stockMutation->id)
            ->assertOk()
            ->assertJsonPath('id', $stockMutation->id)
            ->assertJsonPath('type', 'in')
            ->assertJsonPath('quantity', 10)
            ->assertJsonPath('product.id', $product->id)
            ->assertJsonPath('user.id', $this->user->id);
    }

    public function test_can_update_stock_mutation(): void
    {
        $product = Product::factory()->create(['stock' => 10]);
        $stockMutation = StockMutation::factory()->for($product)->for($this->user)->create([
            'quantity' => 10,
        ]);
        $data = [
            'product_id' => $product->id,
            'type' => 'in',
            'quantity' => 15,
            'notes' => 'Koreksi stok awal.',
        ];

        $this->putJson('/api/stock-mutations/'.$stockMutation->id, $data)
            ->assertOk()
            ->assertJsonPath('id', $stockMutation->id)
            ->assertJsonPath('quantity', 15)
            ->assertJsonPath('notes', 'Koreksi stok awal.')
            ->assertJsonPath('product.stock', 15);

        $this->assertDatabaseHas('stock_mutations', [
            'id' => $stockMutation->id,
            'user_id' => $this->user->id,
            ...$data,
        ]);
        $this->assertSame(15, $product->fresh()->stock);
    }

    public function test_can_delete_stock_mutation(): void
    {
        $product = Product::factory()->create(['stock' => 10]);
        $stockMutation = StockMutation::factory()->for($product)->for($this->user)->create([
            'quantity' => 10,
        ]);

        $this->deleteJson('/api/stock-mutations/'.$stockMutation->id)
            ->assertOk()
            ->assertJsonPath('message', 'Stock mutation successfully deleted.');

        $this->assertDatabaseMissing('stock_mutations', ['id' => $stockMutation->id]);
        $this->assertSame(0, $product->fresh()->stock);
    }
}
