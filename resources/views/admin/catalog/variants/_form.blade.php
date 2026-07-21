@php
    $fallbackLocale = config('app.fallback_locale', config('app.locale'));
    $name = old('name', $variant->getTranslation('name', app()->getLocale(), false) ?: $variant->getTranslation('name', $fallbackLocale, false));
    $options = old('option_names') !== null
        ? collect(old('option_names'))->map(fn ($name, $index) => ['name' => $name, 'value' => old('option_values.'.$index)])
        : collect($variant->option_values ?: [])->map(fn ($value, $name) => ['name' => $name, 'value' => $value])->values();

    if ($options->isEmpty()) {
        $options = collect([
            ['name' => 'Color', 'value' => ''],
            ['name' => 'Size', 'value' => ''],
        ]);
    }
@endphp

<div class="card">
    <div class="card-body">
        <div class="row">
            <div class="col-md-12 mb-3">
                <label class="form-label" for="name">{{ __('catalog.fields.name') }}</label>
                <input type="text" class="form-control" id="name" name="name" value="{{ $name }}">
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label" for="sku">{{ __('catalog.fields.sku') }}</label>
                <input type="text" class="form-control" id="sku" name="sku" value="{{ old('sku', $variant->sku) }}" required>
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label" for="price">{{ __('catalog.fields.price') }}</label>
                <input type="number" min="0" step="0.01" class="form-control" id="price" name="price" value="{{ old('price', $variant->price) }}">
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label" for="compare_at_price">{{ __('catalog.fields.compare_at_price') ?? 'Giá so sánh' }}</label>
                <input type="number" min="0" step="0.01" class="form-control" id="compare_at_price" name="compare_at_price" value="{{ old('compare_at_price', $variant->compare_at_price) }}">
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label" for="stock_quantity">{{ __('catalog.fields.stock_quantity') }}</label>
                <input type="number" min="0" class="form-control" id="stock_quantity" name="stock_quantity" value="{{ old('stock_quantity', $variant->stock_quantity ?? 0) }}">
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label" for="image_url">{{ __('catalog.fields.image_url') ?? 'Đường dẫn ảnh' }}</label>
                <input type="text" class="form-control" id="image_url" name="image_url" value="{{ old('image_url', $variant->image_url) }}" placeholder="e.g. /storage/products/variant.webp">
            </div>
            <div class="col-md-4 mb-3">
                <label class="form-label" for="sort_order">{{ __('catalog.fields.sort_order') }}</label>
                <input type="number" min="0" class="form-control" id="sort_order" name="sort_order" value="{{ old('sort_order', $variant->sort_order ?? 0) }}">
            </div>
            <div class="col-md-8 mb-3">
                <label class="form-label">{{ __('catalog.fields.options') }}</label>
                <div class="row g-2">
                    @foreach($options as $index => $option)
                        <div class="col-md-6">
                            <input type="text" class="form-control" name="option_names[]" value="{{ $option['name'] }}" placeholder="{{ __('catalog.fields.option_name') }}">
                        </div>
                        <div class="col-md-6">
                            <input type="text" class="form-control" name="option_values[]" value="{{ $option['value'] }}" placeholder="{{ __('catalog.fields.option_value') }}">
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <input type="hidden" name="is_active" value="0">
                <div class="form-check">
                    <input class="form-check-input primary" type="checkbox" name="is_active" value="1" id="is_active" @checked(old('is_active', $variant->is_active))>
                    <label class="form-check-label" for="is_active">{{ __('catalog.fields.is_active') }}</label>
                </div>
            </div>
            <div class="col-md-6 mb-3">
                <input type="hidden" name="allow_out_of_stock_order" value="0">
                <div class="form-check">
                    <input class="form-check-input primary" type="checkbox" name="allow_out_of_stock_order" value="1" id="allow_out_of_stock_order" @checked(old('allow_out_of_stock_order', $variant->allow_out_of_stock_order))>
                    <label class="form-check-label" for="allow_out_of_stock_order">{{ __('catalog.fields.allow_out_of_stock_order') ?? 'Cho phép đặt hàng khi hết hàng' }}</label>
                </div>
            </div>
        </div>
    </div>
</div>

@include('admin.shared.form-actions', ['cancelUrl' => route('admin.products.show', $product)])
