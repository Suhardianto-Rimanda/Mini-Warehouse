<?php

namespace Database\Factories;

use App\Models\Product;
use App\Models\StockMutation;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<StockMutation> */
class StockMutationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'product_id' => Product::factory(),
            'user_id' => User::factory(),
            'type' => 'in',
            'quantity' => fake('id_ID')->numberBetween(20, 60),
            'notes' => 'Penerimaan barang dari pemasok.',
        ];
    }
}