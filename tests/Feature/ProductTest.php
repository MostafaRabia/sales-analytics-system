<?php

test('Get products', function () {
    \App\Models\Product::factory()->create();

    $response = $this->get(route('products.index'));

    $response->assertStatus(200);
    $response->assertJsonStructure([
        'data' => [
            'data' => [
                '*' => [
                    'id',
                    'name',
                    'created_at',
                    'updated_at',
                ],
            ],
        ],
    ]);
});

test('Add product', function () {
    $response = $this->post(route('products.store'), [
        'name' => 'Test Product',
    ]);

    $response->assertStatus(201);
    $response->assertJsonStructure([
        'data' => [
            'id',
            'name',
            'created_at',
            'updated_at',
        ],
    ]);
});

test('Get product', function () {
    $product = \App\Models\Product::factory()->create();

    $response = $this->get(route('products.show', $product));

    $response->assertStatus(200);
    $response->assertJsonStructure([
        'data' => [
            'id',
            'name',
            'created_at',
            'updated_at',
        ],
    ]);
});

test('Update product', function () {
    $product = \App\Models\Product::factory()->create();

    $response = $this->put(route('products.update', $product), [
        'name' => 'Updated Product',
    ]);

    $response->assertStatus(200);
    $response->assertJsonStructure([
        'data' => [
            'id',
            'name',
            'created_at',
            'updated_at',
        ],
    ]);
});

test('Delete product', function () {
    $product = \App\Models\Product::factory()->create();

    $response = $this->delete(route('products.destroy', $product));

    $response->assertStatus(204);
    $this->assertDatabaseMissing('products', [
        'id' => $product->id,
    ]);
});
