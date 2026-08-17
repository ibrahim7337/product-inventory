<?php

declare(strict_types=1);

namespace App\Services;

use App\Models\Product;
use Illuminate\Validation\ValidationException;

class StockAdjustmentService
{
    /**
     * Summary of adjust
     */
    public function adjust(
        Product $product,
        string $type,
        int $quantity,
    ): Product {
        if ($type === 'increase') {
            $product->stock_quantity += $quantity;
            $product->save();

            return $product->refresh();
        }

        if ($product->stock_quantity < $quantity) {
            throw ValidationException::withMessages([
                'quantity' => 'Insufficient stock.',
            ]);
        }

        $product->stock_quantity -= $quantity;
        $product->save();

        return $product->refresh();
    }
}
