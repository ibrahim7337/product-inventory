<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Enums\ProductStatus;
use App\Models\Product;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductTest extends TestCase
{
    use RefreshDatabase;

    public function test_product_can_be_created(): void
    {
        $product = Product::factory()->create();

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'sku' => $product->sku,
            'name' => $product->name,
        ]);
    }

    public function test_product_has_uuid(): void
    {
        $product = Product::factory()->create();

        $this->assertNotNull($product->id);
        $this->assertIsString($product->id);
    }

    public function test_product_status_is_cast_to_enum(): void
    {
        $product = Product::factory()->create([
            'status' => ProductStatus::ACTIVE,
        ]);

        $this->assertInstanceOf(
            ProductStatus::class,
            $product->status
        );

        $this->assertSame(
            ProductStatus::ACTIVE,
            $product->status
        );
    }

    public function test_product_can_be_soft_deleted(): void
    {
        $product = Product::factory()->create();

        $product->delete();

        $this->assertSoftDeleted('products', [
            'id' => $product->id,
        ]);

        $this->assertNull(
            Product::find($product->id)
        );

        $this->assertNotNull(
            Product::withTrashed()->find($product->id)
        );
    }

    public function test_product_sku_is_unique(): void
    {
        $product = Product::factory()->create([
            'sku' => 'SKU-001',
        ]);

        $this->expectException(QueryException::class);

        Product::factory()->create([
            'sku' => $product->sku,
        ]);
    }

    public function test_product_can_be_listed(): void
    {
        Product::factory()->count(15)->create();

        $response = $this->getJson('/api/v1/products');

        $response->assertStatus(200)
            ->assertJsonStructure([
                'data' => [
                    '*' => [
                        'id',
                        'sku',
                        'name',
                        'description',
                        'price',
                        'stock_quantity',
                        'low_stock_threshold',
                        'status',
                        'created_at',
                        'updated_at',
                    ],
                ],
                'links',
                'meta',
            ]);
    }

    public function test_product_can_be_created_by_api(): void
    {
        $payload = [
            'sku' => 'SKU-001',
            'name' => 'Test Product',
            'description' => 'This is a test product.',
            'price' => 99.99,
            'stock_quantity' => 10,
            'low_stock_threshold' => 5,
            'status' => ProductStatus::ACTIVE->value,
        ];

        $response = $this->postJson('/api/v1/products', $payload);

        $response->
            assertCreated()
                ->assertJsonPath('data.id', $response['data']['id'])
                ->assertJsonPath('data.sku', $payload['sku'])
                ->assertJsonPath('data.name', $payload['name'])
                ->assertJsonPath('data.description', $payload['description'])
                ->assertJsonPath('data.price', (string) $payload['price'])
                ->assertJsonPath('data.stock_quantity', $payload['stock_quantity'])
                ->assertJsonPath('data.low_stock_threshold', $payload['low_stock_threshold'])
                ->assertJsonPath('data.status', $payload['status']);

        $this->assertDatabaseHas('products', [
            'id' => $response['data']['id'],
            'sku' => $payload['sku'],
            'name' => $payload['name'],
            'description' => $payload['description'],
            'price' => (string) $payload['price'],
            'stock_quantity' => $payload['stock_quantity'],
            'low_stock_threshold' => $payload['low_stock_threshold'],
            'status' => $payload['status'],
        ]);
    }

    public function test_product_can_be_shown_by_api(): void
    {
        $product = Product::factory()->create();

        $response = $this->getJson("/api/v1/products/{$product->id}");

        $response->assertOk()
            ->assertJsonPath('data.id', $product->id)
            ->assertJsonPath('data.sku', $product->sku)
            ->assertJsonPath('data.name', $product->name);
    }

    public function test_product_can_be_updated_by_api(): void
    {
        $product = Product::factory()->create();

        $payload = [
            'name' => 'Updated Product Name',
            'price' => 199.99,
        ];

        $response = $this->putJson("/api/v1/products/{$product->id}", $payload);

        $response->assertOk()
            ->assertJsonPath('data.id', $product->id)
            ->assertJsonPath('data.name', $payload['name'])
            ->assertJsonPath('data.price', (string) $payload['price']);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'name' => $payload['name'],
            'price' => (string) $payload['price'],
        ]);
    }

    public function test_product_can_be_deleted_by_api(): void
    {
        $product = Product::factory()->create();

        $response = $this->deleteJson("/api/v1/products/{$product->id}");

        $response->assertJson([
            'message' => 'Product deleted successfully.',
        ]);

        $this->assertSoftDeleted('products', [
            'id' => $product->id,
        ]);
    }

    public function test_product_creation_requires_required_fields(): void
    {
        $response = $this->postJson('/api/v1/products', []);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'sku',
                'name',
                'price',
                'stock_quantity',
                'low_stock_threshold',
                'status',
            ]);
    }

    public function test_product_creation_rejects_duplicate_sku(): void
    {
        Product::factory()->create([
            'sku' => 'SKU-001',
        ]);

        $response = $this->postJson('/api/v1/products', [
            'sku' => 'SKU-001',
            'name' => 'Another Product',
            'price' => 100,
            'stock_quantity' => 10,
            'low_stock_threshold' => 5,
            'status' => ProductStatus::ACTIVE->value,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['sku']);
    }

    public function test_product_price_must_not_be_negative(): void
    {
        $response = $this->postJson('/api/v1/products', [
            'sku' => 'SKU-002',
            'name' => 'Test Product',
            'price' => -10,
            'stock_quantity' => 10,
            'low_stock_threshold' => 5,
            'status' => ProductStatus::ACTIVE->value,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['price']);
    }

    public function test_product_stock_quantity_must_not_be_negative(): void
    {
        $response = $this->postJson('/api/v1/products', [
            'sku' => 'SKU-003',
            'name' => 'Test Product',
            'price' => 100,
            'stock_quantity' => -1,
            'low_stock_threshold' => 5,
            'status' => ProductStatus::ACTIVE->value,
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['stock_quantity']);
    }

    public function test_product_status_must_be_valid(): void
    {
        $response = $this->postJson('/api/v1/products', [
            'sku' => 'SKU-004',
            'name' => 'Test Product',
            'price' => 100,
            'stock_quantity' => 10,
            'low_stock_threshold' => 5,
            'status' => 'invalid-status',
        ]);

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['status']);
    }

    public function test_product_can_be_updated_without_changing_its_sku(): void
    {
        $product = Product::factory()->create([
            'sku' => 'SKU-001',
        ]);

        $response = $this->putJson(
            "/api/v1/products/{$product->id}",
            [
                'sku' => 'SKU-001',
                'name' => 'Updated Product',
            ]
        );

        $response->assertOk()
            ->assertJsonPath('data.sku', 'SKU-001');
    }

    public function test_product_cannot_use_another_products_sku(): void
    {
        Product::factory()->create([
            'sku' => 'SKU-001',
        ]);

        $product = Product::factory()->create([
            'sku' => 'SKU-002',
        ]);

        $response = $this->putJson(
            "/api/v1/products/{$product->id}",
            [
                'sku' => 'SKU-001',
            ]
        );

        $response->assertUnprocessable()
            ->assertJsonValidationErrors(['sku']);
    }

    public function test_product_stock_can_be_increased(): void
    {
        $product = Product::factory()->create([
            'stock_quantity' => 10,
        ]);

        $response = $this->postJson(
            "/api/v1/products/{$product->id}/stock",
            [
                'type' => 'increase',
                'quantity' => 5,
            ],
        );

        $response->assertOk()
            ->assertJsonPath('data.id', $product->id)
            ->assertJsonPath('data.stock_quantity', 15);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock_quantity' => 15,
        ]);
    }

    public function test_product_stock_can_be_decreased(): void
    {
        $product = Product::factory()->create([
            'stock_quantity' => 10,
        ]);

        $response = $this->postJson(
            "/api/v1/products/{$product->id}/stock",
            [
                'type' => 'decrease',
                'quantity' => 3,
            ],
        );

        $response->assertOk()
            ->assertJsonPath('data.id', $product->id)
            ->assertJsonPath('data.stock_quantity', 7);

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock_quantity' => 7,
        ]);
    }

    public function test_product_stock_cannot_be_decreased_below_zero(): void
    {
        $product = Product::factory()->create([
            'stock_quantity' => 5,
        ]);

        $response = $this->postJson(
            "/api/v1/products/{$product->id}/stock",
            [
                'type' => 'decrease',
                'quantity' => 10,
            ],
        );

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('quantity');

        $this->assertDatabaseHas('products', [
            'id' => $product->id,
            'stock_quantity' => 5,
        ]);
    }

    public function test_stock_adjustment_requires_positive_quantity(): void
    {
        $product = Product::factory()->create([
            'stock_quantity' => 10,
        ]);

        $response = $this->postJson(
            "/api/v1/products/{$product->id}/stock",
            [
                'type' => 'increase',
                'quantity' => 0,
            ],
        );

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('quantity');
    }

    public function test_stock_adjustment_rejects_negative_quantity(): void
    {
        $product = Product::factory()->create();

        $response = $this->postJson(
            "/api/v1/products/{$product->id}/stock",
            [
                'type' => 'increase',
                'quantity' => -5,
            ],
        );

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('quantity');
    }

    public function test_stock_adjustment_requires_valid_type(): void
    {
        $product = Product::factory()->create();

        $response = $this->postJson(
            "/api/v1/products/{$product->id}/stock",
            [
                'type' => 'invalid',
                'quantity' => 5,
            ],
        );

        $response->assertUnprocessable()
            ->assertJsonValidationErrors('type');
    }

    public function test_stock_adjustment_requires_type_and_quantity(): void
    {
        $product = Product::factory()->create();

        $response = $this->postJson(
            "/api/v1/products/{$product->id}/stock",
            [],
        );

        $response->assertUnprocessable()
            ->assertJsonValidationErrors([
                'type',
                'quantity',
            ]);
    }
}
