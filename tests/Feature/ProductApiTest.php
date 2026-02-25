<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductApiTest extends TestCase
{
    use RefreshDatabase;

    public function test_store_returns_validation_error_for_duplicate_sku(): void
    {
        $payload = [
            'name' => 'Wireless Mouse',
            'sku' => 'WM-1001',
            'price' => 1299.99,
            'stock_quantity' => 10,
        ];

        $this->postJson('/api/products', $payload)->assertCreated();

        $response = $this->postJson('/api/products', $payload);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['sku']);
    }
}
