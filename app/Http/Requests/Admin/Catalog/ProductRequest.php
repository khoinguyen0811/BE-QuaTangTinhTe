<?php

namespace App\Http\Requests\Admin\Catalog;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $productId = $this->route('product')?->id;

        return [
            'category_id' => ['nullable', 'integer', 'exists:categories,id'],
            'category_ids' => ['nullable', 'array', 'max:3'],
            'category_ids.*' => ['integer', 'exists:categories,id'],
            'brand_id' => ['nullable', 'integer', 'exists:brands,id'],
            'name' => ['required', 'string', 'max:255'],
            'slug' => ['nullable', 'string', 'max:255', Rule::unique('products', 'slug')->ignore($productId)],
            'sku' => ['nullable', 'string', 'max:100', Rule::unique('products', 'sku')->ignore($productId)],
            'short_description' => ['nullable', 'string'],
            'description' => ['nullable', 'string'],
            'image_url' => ['nullable', 'string', 'max:255'],
            'image_file' => ['nullable', 'file', 'image', 'max:5120'],
            'price' => ['required', 'numeric', 'min:0'],
            'compare_at_price' => ['nullable', 'numeric', 'min:0'],
            'cost_price' => ['nullable', 'numeric', 'min:0'],
            'stock_quantity' => ['nullable', 'integer', 'min:0'],
            'manage_stock' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
            'is_featured' => ['nullable', 'boolean'],
            'published_at' => ['nullable', 'date'],
            
            // Nested variants validation
            'variants' => ['nullable', 'array'],
            'variants.*.id' => ['nullable', 'integer'],
            'variants.*.sku' => ['required', 'string', 'max:100'],
            'variants.*.price' => ['nullable', 'numeric', 'min:0'],
            'variants.*.compare_at_price' => ['nullable', 'numeric', 'min:0'],
            'variants.*.stock_quantity' => ['nullable', 'integer', 'min:0'],
            'variants.*.allow_out_of_stock_order' => ['nullable', 'boolean'],
            'variants.*.image_url' => ['nullable', 'string', 'max:2048'],
            'variants.*.images' => ['nullable', 'array', 'max:10'],
            'variants.*.images.*.url' => ['required', 'string', 'max:2048'],
            'variants.*.images.*.alt' => ['nullable', 'string', 'max:255'],
            'variants.*.images.*.is_primary' => ['nullable', 'boolean'],
            'variants.*.images.*.sort_order' => ['nullable', 'integer'],
            'variants.*.is_active' => ['nullable', 'boolean'],
            'variants.*.sort_order' => ['nullable', 'integer', 'min:0'],
        ];
    }

    public function withValidator($validator)
    {
        $validator->after(function ($validator) {
            $this->validateDuplicateVariantSkus($validator);
            
            $productId = $this->route('product')?->id;
            
            foreach ($this->input('variants', []) as $index => $variant) {
                $price = $variant['price'] ?? null;
                $compareAtPrice = $variant['compare_at_price'] ?? null;
                if ($compareAtPrice !== null && $price !== null && (float) $compareAtPrice < (float) $price) {
                    $validator->errors()->add("variants.$index.compare_at_price", 'Giá so sánh của biến thể phải lớn hơn hoặc bằng giá bán.');
                }

                $sku = $variant['sku'] ?? null;
                if ($sku !== null && trim($sku) !== '') {
                    $normalizedSku = mb_strtoupper(trim((string) $sku));
                    $variantId = $variant['id'] ?? null;

                    if ($variantId && $productId) {
                        $belongs = \App\Models\ProductVariant::query()
                            ->whereKey($variantId)
                            ->where('product_id', $productId)
                            ->exists();
                        if (!$belongs) {
                            $validator->errors()->add("variants.$index.id", 'ID biến thể không hợp lệ.');
                            continue;
                        }
                    }

                    $exists = \App\Models\ProductVariant::query()
                        ->whereRaw('UPPER(TRIM(sku)) = ?', [$normalizedSku])
                        ->when($variantId, fn ($query) => $query->whereKeyNot($variantId))
                        ->exists();

                    if ($exists) {
                        $validator->errors()->add("variants.$index.sku", "SKU '{$sku}' đã được sử dụng bởi một biến thể khác.");
                    }
                }
            }
        });
    }

    protected function validateDuplicateVariantSkus($validator): void
    {
        $variants = collect($this->input('variants', []));

        $normalizedSkus = $variants
            ->pluck('sku')
            ->filter(fn ($sku) => filled($sku))
            ->map(fn ($sku) => mb_strtoupper(trim((string) $sku)));

        $duplicates = $normalizedSkus->duplicates()->unique();

        if ($duplicates->isEmpty()) {
            return;
        }

        foreach ($variants as $index => $variant) {
            $normalizedSku = mb_strtoupper(trim((string) ($variant['sku'] ?? '')));

            if ($normalizedSku !== '' && $duplicates->contains($normalizedSku)) {
                $validator->errors()->add(
                    "variants.$index.sku",
                    'SKU biến thể đang bị trùng với một biến thể khác trong biểu mẫu.'
                );
            }
        }
    }
}
