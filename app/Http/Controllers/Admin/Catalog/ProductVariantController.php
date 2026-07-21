<?php

namespace App\Http\Controllers\Admin\Catalog;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\Catalog\ProductVariantRequest;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\Catalog\ProductService;

class ProductVariantController extends Controller
{
    public function __construct(private readonly ProductService $products)
    {
    }

    public function create(string $locale, Product $product)
    {
        return view('admin.catalog.variants.create', [
            'product' => $product,
            'variant' => new ProductVariant([
                'stock_quantity' => 0,
                'sort_order' => 0,
                'is_active' => true,
            ]),
        ]);
    }

    public function store(ProductVariantRequest $request, string $locale, Product $product)
    {
        $this->products->createVariant($product, $request->validated());

        return redirect()
            ->route('admin.products.show', $product)
            ->with('success', __('catalog.variants.created'));
    }

    public function edit(string $locale, Product $product, ProductVariant $variant)
    {
        abort_unless($variant->product_id === $product->id, 404);

        return view('admin.catalog.variants.edit', [
            'product' => $product,
            'variant' => $variant,
        ]);
    }

    public function update(ProductVariantRequest $request, string $locale, Product $product, ProductVariant $variant)
    {
        abort_unless($variant->product_id === $product->id, 404);

        $expectedUpdatedAt = $request->input('updated_at');
        if ($expectedUpdatedAt) {
            $originalUpdatedAt = \Illuminate\Support\Carbon::parse($expectedUpdatedAt);
            
            try {
                \Illuminate\Support\Facades\DB::transaction(function () use ($variant, $originalUpdatedAt) {
                    $lockedVariant = ProductVariant::query()->lockForUpdate()->findOrFail($variant->id);
                    if (!$lockedVariant->updated_at->equalTo($originalUpdatedAt)) {
                        throw new \RuntimeException('VARIANT_UPDATE_CONFLICT');
                    }
                });
            } catch (\RuntimeException $e) {
                if ($e->getMessage() === 'VARIANT_UPDATE_CONFLICT') {
                    if ($request->expectsJson()) {
                        return response()->json([
                            'message' => 'Biến thể đã được người khác cập nhật. Vui lòng tải lại dữ liệu trước khi lưu.',
                            'code' => 'VARIANT_UPDATE_CONFLICT',
                        ], 409);
                    }
                    return back()
                        ->withInput()
                        ->withErrors([
                            'conflict' => 'Biến thể đã thay đổi ở một phiên khác. Vui lòng tải lại trang trước khi lưu lại.',
                        ]);
                }
                throw $e;
            }
        }

        $this->products->updateVariant($variant, $request->validated());

        return redirect()
            ->route('admin.products.show', $product)
            ->with('success', __('catalog.variants.updated'));
    }

    public function destroy(string $locale, Product $product, ProductVariant $variant)
    {
        abort_unless($variant->product_id === $product->id, 404);

        $this->products->deleteVariant($variant);

        return redirect()
            ->route('admin.products.show', $product)
            ->with('success', __('catalog.variants.deleted'));
    }
}
