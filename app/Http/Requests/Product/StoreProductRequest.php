<?php

declare(strict_types=1);

namespace App\Http\Requests\Product;

use App\Enums\ProductStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Enum;

class StoreProductRequest extends FormRequest
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
     *     sku: string[],
     *     status: array<string|Enum>,
     *     stock_quantity: string[]
     * }
     */
    public function rules(): array
    {
        return [
            'sku' => [
                'required',
                'string',
                'max:255',
                'unique:products,sku',
            ],
            'name' => [
                'required',
                'string',
                'max:255',
            ],
            'description' => [
                'nullable',
                'string',
            ],
            'price' => [
                'required',
                'numeric',
                'min:0',
            ],
            'stock_quantity' => [
                'required',
                'integer',
                'min:0',
            ],
            'low_stock_threshold' => [
                'required',
                'integer',
                'min:0',
            ],
            'status' => [
                'required',
                Rule::enum(ProductStatus::class),
            ],
        ];
    }
}
