<?php

declare(strict_types=1);

namespace App\Models;

use App\Enums\ProductStatus;
use Database\Factories\ProductFactory;
use Illuminate\Database\Eloquent\Concerns\HasUuids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @use HasFactory<ProductFactory>
 *
 * @property string $id
 * @property string $sku
 * @property string $name
 * @property string|null $description
 * @property string $price
 * @property int $stock_quantity
 * @property int $low_stock_threshold
 * @property ProductStatus $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 * @property Carbon|null $deleted_at
 */
class Product extends Model
{
    /** @use HasFactory<ProductFactory> */
    use HasFactory;

    use HasUuids;
    use SoftDeletes;

    protected $fillable = [
        'sku',
        'name',
        'description',
        'price',
        'stock_quantity',
        'low_stock_threshold',
        'status',
    ];

    protected function casts(): array
    {
        return [
            'price' => 'decimal:2',
            'status' => ProductStatus::class,
            'stock_quantity' => 'integer',
            'low_stock_threshold' => 'integer',
        ];
    }
}
