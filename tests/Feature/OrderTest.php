<?php

test('Get orders', function () {
    \App\Models\Order::factory()->create();

    $response = $this->get(route('orders.index'));

    $response->assertStatus(200);
    $response->assertJsonStructure([
        'data' => [
            'data' => [
                '*' => [
                    'id',
                    'product_id',
                    'quantity',
                    'created_at',
                    'updated_at',
                ],
            ],
        ],
    ]);
});

test('Add order', function () {
    $product = \App\Models\Product::factory()->create();

    $response = $this->post(route('orders.store'), [
        'product_id' => $product->id,
        'quantity' => 1,
        'price' => 100,
        'date' => now(),
    ]);

    $response->assertStatus(201);
    $response->assertJsonStructure([
        'data' => [
            'id',
            'product_id',
            'quantity',
            'created_at',
            'updated_at',
        ],
    ]);
});

test('Get order', function () {
    $order = \App\Models\Order::factory()->create();

    $response = $this->get(route('orders.show', $order));

    $response->assertStatus(200);
    $response->assertJsonStructure([
        'data' => [
            'id',
            'product_id',
            'quantity',
            'created_at',
            'updated_at',
        ],
    ]);
});

test('Update order', function () {
    $order = \App\Models\Order::factory()->create();

    $response = $this->put(route('orders.update', $order), [
        'product_id' => $order->product_id,
        'quantity' => 2,
        'price' => 200,
        'date' => now(),
    ]);

    $response->assertStatus(200);
    $response->assertJsonStructure([
        'data' => [
            'id',
            'product_id',
            'quantity',
            'created_at',
            'updated_at',
        ],
    ]);
});

test('Delete order', function () {
    $order = \App\Models\Order::factory()->create();

    $response = $this->delete(route('orders.destroy', $order));

    $response->assertStatus(204);
    $this->assertDatabaseMissing('orders', [
        'id' => $order->id,
    ]);
});
