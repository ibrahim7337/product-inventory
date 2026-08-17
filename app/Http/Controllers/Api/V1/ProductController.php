<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Requests\Product\StockAdjustmentRequest;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Services\StockAdjustmentService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Resources\Json\AnonymousResourceCollection;

class ProductController extends Controller
{
    /**
     * A listing of the resource.
     */
    public function index(): AnonymousResourceCollection
    {
        return ProductResource::collection(
            Product::query()->latest()->paginate(10)
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProductRequest $request): ProductResource
    {
        $product = Product::create($request->validated());

        return new ProductResource($product);
    }

    /**
     * Display the specified resource.
     */
    public function show(Product $product): ProductResource
    {
        return new ProductResource($product);
    }

    /**
     * Update the specified resource in storage.
     *
     * @return ProductResource
     */
    public function update(
        UpdateProductRequest $request,
        Product $product
    ) {
        /**
         * @var array{type: string, quantity: int} $data
         */
        $data = $request->validated();

        $product->update($data);

        return new ProductResource($product->refresh());
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Product $product): JsonResponse
    {
        $product->delete();

        return response()->json([
            'message' => 'Product deleted successfully.',
        ]);
    }

    /**
     * Adjust Stock
     */
    public function adjustStock(
        StockAdjustmentRequest $request,
        Product $product,
        StockAdjustmentService $service
    ): ProductResource {
        /**
         * @var array{type: string, quantity: int} $data
         */
        $data = $request->validated();

        $adjustedProduct = $service->adjust(
            $product,
            $data['type'],
            $data['quantity'],
        );

        return new ProductResource($adjustedProduct);
    }
}
