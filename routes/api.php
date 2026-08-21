<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1\ProductController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {
    Route::get(
        'products/low-stock',
        [ProductController::class, 'lowStock']
    );

    Route::apiResource('products', ProductController::class)
        ->except(['create', 'edit']);

    Route::post(
        'products/{product}/stock',
        [ProductController::class, 'adjustStock']
    );
});
