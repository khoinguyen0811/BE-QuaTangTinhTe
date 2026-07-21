<?php

namespace App\Http\Requests\Admin\Catalog;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductVariantRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $variantId = $this->route('variant')?->id;

        return [
            'name' => ['nullable', 'string', 'max:255'],
            'sku' => ['required', 'string', 'max:100', Rule::unique('product_variants', 'sku')->ignore($variantId)],
            'option_names' => ['nullable', 'array'],
            'option_names.*' => ['nullable', 'string', 'max:100'],
            'option_values' => ['nullable', 'array'],
            'option_values.*' => ['nullable', 'string', 'max:100'],
            'price' => ['nullable', 'numeric', 'min:0'],
            'compare_at_price' => ['nullable', 'numeric', 'min:0'],
            'stock_quantity' => ['nullable', 'integer', 'min:0'],
            'allow_out_of_stock_order' => ['nullable', 'boolean'],
            'image_url' => ['nullable', 'string', 'max:2048'],
            'images' => ['nullable', 'array', 'max:10'],
            'images.*.url' => ['required', 'string', 'max:2048'],
            'images.*.alt' => ['nullable', 'string', 'max:255'],
            'images.*.is_primary' => ['nullable', 'boolean'],
            'images.*.sort_order' => ['nullable', 'integer'],
            'is_active' => ['nullable', 'boolean'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $price = $this->input('price') ?? null;
            $compareAtPrice = $this->input('compare_at_price') ?? null;
            if ($compareAtPrice !== null && $price !== null && (float) $compareAtPrice < (float) $price) {
                $validator->errors()->add('compare_at_price', 'Giá so sánh của biến thể phải lớn hơn hoặc bằng giá bán.');
            }
        });
    }
}
