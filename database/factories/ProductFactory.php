<?php

namespace Database\Factories;

use App\Models\Category;
use App\Models\Product;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<Product> */
class ProductFactory extends Factory
{
    public const CATALOG = [
        'Sembako' => [
            ['name' => 'Beras Pandan Wangi 5 kg', 'unit' => 'karung'],
            ['name' => 'Gula Pasir 1 kg', 'unit' => 'bungkus'],
            ['name' => 'Minyak Goreng 1 liter', 'unit' => 'botol'],
        ],
        'Minuman' => [
            ['name' => 'Air Mineral 600 ml', 'unit' => 'botol'],
            ['name' => 'Teh Melati Celup', 'unit' => 'kotak'],
            ['name' => 'Kopi Bubuk 200 gram', 'unit' => 'bungkus'],
        ],
        'Alat Tulis' => [
            ['name' => 'Buku Tulis 38 Lembar', 'unit' => 'buku'],
            ['name' => 'Pulpen Tinta Hitam', 'unit' => 'buah'],
            ['name' => 'Kertas HVS A4', 'unit' => 'rim'],
        ],
        'Peralatan Kebersihan' => [
            ['name' => 'Sabun Cuci Piring 750 ml', 'unit' => 'botol'],
            ['name' => 'Sapu Lantai', 'unit' => 'buah'],
            ['name' => 'Cairan Pembersih Lantai 800 ml', 'unit' => 'botol'],
        ],
        'Perlengkapan Dapur' => [
            ['name' => 'Piring Makan Keramik', 'unit' => 'buah'],
            ['name' => 'Gelas Minum Kaca', 'unit' => 'buah'],
            ['name' => 'Sendok Makan', 'unit' => 'lusin'],
        ],
    ];

    public function definition(): array
    {
        $faker = fake('id_ID');
        $category = $faker->randomElement(array_keys(self::CATALOG));
        $product = $faker->randomElement(self::CATALOG[$category]);

        return [
            'category_id' => Category::factory()->state(['name' => $category]),
            'sku' => 'PRD-Factory-'.Str::uuid(),
            'name' => $product['name'],
            'unit' => $product['unit'],
            'stock' => 0,
        ];
    }
}