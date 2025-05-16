<?php

namespace Database\Seeders;

use App\Models\Order;
use App\Models\Product;
use App\Models\User;
// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $juices = [
            'Apple Juice',
            'Orange Juice',
            'Grape Juice',
            'Tea',
            'Coffee',
        ];

        for ($i = 0; $i < 5; $i++) {
            $product = Product::factory()->create([
                'name' => $juices[$i],
            ]);

            for ($c = 0; $c < 10; $c++) {
                Order::factory()->create([
                    'product_id' => $product->id,
                ]);
            }
        }
    }
}
