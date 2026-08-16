<?php

declare(strict_types=1);

namespace App\Http\Requests\Product;

use App\Enums\ProductStatus;
use App\Models\Product;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;
use Illuminate\Validation\Rules\Unique;

class UpdateProductRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array{
     *     description: string[],
     *     low_stock_threshold: string[],
     *     name: string[],
     *     price: string[],
     *     sku: array<string|Unique>,
     *     status: array<string|Enum>,
     *     stock_quantity: string[]
     * }
     */
    public function rules(): array
    {
        /** @var Product $product */
        $product = $this->route('product');

        return [
            'sku' => [
                'sometimes',
                'string',
                'max:255',
                Rule::unique('products', 'sku')->ignore($product->id),
            ],
            'name' => [
                'sometimes',
                'string',
                'max:255',
            ],
            'description' => [
                'sometimes',
                'nullable',
                'string',
            ],
            'price' => [
                'sometimes',
                'numeric',
                'min:0',
            ],
            'stock_quantity' => [
                'sometimes',
                'integer',
                'min:0',
            ],
            'low_stock_threshold' => [
                'sometimes',
                'integer',
                'min:0',
            ],
            'status' => [
                'sometimes',
                Rule::enum(ProductStatus::class),
            ],
        ];
    }
}
