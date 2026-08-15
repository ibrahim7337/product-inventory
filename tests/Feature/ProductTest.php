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
}
