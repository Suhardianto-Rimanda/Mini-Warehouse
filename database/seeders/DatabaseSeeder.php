<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\StockMutation;
use App\Models\User;
use Database\Factories\ProductFactory;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    public function run(): void
    {
        DB::transaction(function () {
            $faker = fake('id_ID');
            $firstDay = now()->subDays(5)->startOfDay();
            $createdAt = $firstDay->copy()->subDay();

            $users = User::factory()->count(10)->create([
                'email_verified_at' => $createdAt,
                'created_at' => $createdAt,
                'updated_at' => $createdAt,
            ]);

            foreach (ProductFactory::CATALOG as $categoryName => $items) {
                $category = Category::factory()->create([
                    'name' => $categoryName,
                    'created_at' => $createdAt,
                    'updated_at' => $createdAt,
                ]);

                foreach ($items as $item) {
                    $product = Product::factory()->for($category)->create([
                        'name' => $item['name'],
                        'unit' => $item['unit'],
                        'created_at' => $createdAt,
                        'updated_at' => $createdAt,
                    ]);

                    $balance = 0;

                    for ($day = 0; $day < 5; $day++) {
                        $date = $firstDay->copy()->addDays($day);
                        $incoming = $faker->numberBetween(20, 60);
                        $outgoing = $faker->numberBetween(1, $incoming);
                        $receivedAt = $date->copy()->setTime(8, 0);
                        $issuedAt = $date->copy()->setTime(15, 0);

                        StockMutation::factory()
                            ->for($product)
                            ->for($users->random())
                            ->create([
                                'type' => 'in',
                                'quantity' => $incoming,
                                'notes' => 'Penerimaan barang dari pemasok.',
                                'created_at' => $receivedAt,
                                'updated_at' => $receivedAt,
                            ]);

                        StockMutation::factory()
                            ->for($product)
                            ->for($users->random())
                            ->create([
                                'type' => 'out',
                                'quantity' => $outgoing,
                                'notes' => 'Pengeluaran barang untuk pesanan pelanggan.',
                                'created_at' => $issuedAt,
                                'updated_at' => $issuedAt,
                            ]);

                        $balance += $incoming - $outgoing;
                    }

                    $product->stock = $balance;
                    $product->save();
                }
            }
        });
    }
}