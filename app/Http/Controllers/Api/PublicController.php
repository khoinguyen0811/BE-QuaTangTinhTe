<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Brand;
use App\Models\Category;
use App\Models\Order;
use App\Models\Post;
use App\Models\PostCategory;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\ProjectSetting;
use App\Models\Review;
use App\Models\Voucher;

use App\Services\CloudinaryService;
use App\Support\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class PublicController extends Controller
{
    /**
     * App health check.
     */
    public function health()
    {
        return ApiResponse::success([
            'app' => __('api.app_name'),
        ]);
    }

    /**
     * Display Swagger API documentation page.
     */
    public function docs()
    {
        return view('api.docs');
    }

    /**
     * Serve the OpenAPI specification for the Swagger UI.
     */
    public function openapi()
    {
        $path = public_path('docs/openapi.json');

        if (! is_file($path)) {
            abort(404);
        }

        return response(file_get_contents($path), 200, [
            'Content-Type' => 'application/json; charset=utf-8',
            'Cache-Control' => 'no-cache, max-age=0, must-revalidate',
        ]);
    }

    /**
     * Get system settings.
     */
    public function settings()
    {
        $settings = ProjectSetting::query()
            ->pluck('setting_value', 'setting_key')
            ->all();

        return ApiResponse::success($settings);
    }

    /**
     * Get category tree list.
     */
    public function categories()
    {
        $categories = Category::query()
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->with(['children' => function ($q) {
                $q->where('is_active', true)->orderBy('sort_order')->orderBy('id');
            }])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return ApiResponse::success($categories);
    }

    /**
     * Get brand list.
     */
    public function brands()
    {
        $brands = Brand::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();

        return ApiResponse::success($brands);
    }

    /**
     * Get filterable products list.
     */
    public function products(Request $request)
    {
        $query = Product::query();
        if (!$request->boolean('include_inactive')) {
            $query->where('is_active', true);
        }

        // Filter by specific IDs and maintain exact order
        if ($request->filled('ids')) {
            $ids = $request->input('ids');
            if (is_string($ids)) {
                $ids = explode(',', $ids);
            }
            $ids = array_filter(array_map('intval', $ids));
            if (!empty($ids)) {
                $query->whereIn('id', $ids);
                
                $idsString = implode(',', $ids);
                if (config('database.default') === 'sqlite') {
                    $cases = [];
                    foreach ($ids as $index => $id) {
                        $cases[] = "WHEN id = {$id} THEN {$index}";
                    }
                    $query->orderByRaw("CASE " . implode(' ', $cases) . " ELSE 9999 END");
                } else {
                    $query->orderByRaw("FIELD(id, {$idsString})");
                }
            }
        }

        // Filter by Category
        $cat = null;
        if ($request->filled('category')) {
            $cat = $request->input('category');
        } elseif ($request->filled('category_slug')) {
            $cat = $request->input('category_slug');
        } elseif ($request->filled('subcategory_slug')) {
            $cat = $request->input('subcategory_slug');
        }

        if ($cat !== null && $cat !== '' && $cat !== 'toan-bo-san-pham') {
            $matchedCategory = Category::query()
                ->where('id', $cat)
                ->orWhere('slug', $cat)
                ->first();

            if ($matchedCategory) {
                $categoryIds = $this->getDescendantCategoryIds($matchedCategory);
                $query->whereHas('categories', function ($q) use ($categoryIds) {
                    $q->whereIn('categories.id', $categoryIds);
                });
            } else {
                $query->whereRaw('1 = 0');
            }
        }

        // Filter by Brand
        if ($request->filled('brand')) {
            $brand = $request->input('brand');
            $query->whereHas('brand', function ($q) use ($brand) {
                $q->where('id', $brand)->orWhere('slug', $brand);
            });
        }

        // Search by keyword
        $q = null;
        if ($request->filled('q')) {
            $q = $request->input('q');
        } elseif ($request->filled('search')) {
            $q = $request->input('search');
        }

        if ($q !== null && trim((string) $q) !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', "%{$q}%")
                    ->orWhere('description', 'like', "%{$q}%")
                    ->orWhere('sku', 'like', "%{$q}%");
            });
        }

        // Filter by Price range
        $minPrice = $request->input('min_price') ?? $request->input('price_min');
        if ($minPrice !== null && $minPrice !== '') {
            $query->where('price', '>=', (float) $minPrice);
        }

        $maxPrice = $request->input('max_price') ?? $request->input('price_max');
        if ($maxPrice !== null && $maxPrice !== '') {
            $query->where('price', '<=', (float) $maxPrice);
        }

        // Sort results (bypass default sort if specific ids are requested to maintain manually selected order)
        if (!$request->filled('ids')) {
            $sortBy = $request->input('sort') ?? $request->input('sort_by') ?? 'latest';
            if ($sortBy === 'price_asc') {
                $query->orderBy('price', 'asc');
            } elseif ($sortBy === 'price_desc') {
                $query->orderBy('price', 'desc');
            } else {
                $query->latest();
            }
        }

        $limit = min(200, max(1, $request->integer('limit', 12)));
        $products = $query->paginate($limit)->withQueryString();

        return ApiResponse::success($products->items(), 'Lấy danh sách sản phẩm thành công.', [
            'current_page' => $products->currentPage(),
            'last_page' => $products->lastPage(),
            'per_page' => $products->perPage(),
            'total' => $products->total(),
        ]);
    }

    /**
     * Get single product details.
     */
    public function productDetail($idOrSlug)
    {
        $product = Product::query()
            ->where('is_active', true)
            ->where(function ($q) use ($idOrSlug) {
                $q->where('id', $idOrSlug)->orWhere('slug', $idOrSlug);
            })
            ->with(['category', 'categories', 'brand', 'variants', 'reviews' => function ($q) {
                $q->where('is_visible', true)->latest();
            }])
            ->first();

        if (!$product) {
            return ApiResponse::error('Sản phẩm không tồn tại.', 404);
        }

        return ApiResponse::success($product);
    }

    /**
     * Get public post categories.
     */
    public function postCategories()
    {
        $categories = PostCategory::query()
            ->where('is_active', true)
            ->withCount(['posts' => function ($query) {
                $query->where('is_active', true)
                    ->where(function ($subQuery) {
                        $subQuery->whereNull('published_at')
                            ->orWhere('published_at', '<=', now());
                    });
            }])
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get()
            ->map(fn (PostCategory $category) => $this->transformPostCategory($category))
            ->values();

        return ApiResponse::success($categories);
    }

    /**
     * Get public posts list.
     */
    public function posts(Request $request)
    {
        $query = Post::query()
            ->where('is_active', true)
            ->where(function ($subQuery) {
                $subQuery->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            })
            ->with('category');

        if ($request->filled('category')) {
            $category = $request->input('category');
            $query->whereHas('category', function ($categoryQuery) use ($category) {
                $categoryQuery->where('id', $category)->orWhere('slug', $category);
            });
        }

        if ($request->filled('q')) {
            $keyword = trim((string) $request->input('q'));
            if ($keyword !== '') {
                $query->where(function ($searchQuery) use ($keyword) {
                    $searchQuery
                        ->where('title', 'like', "%{$keyword}%")
                        ->orWhere('summary', 'like', "%{$keyword}%")
                        ->orWhere('content', 'like', "%{$keyword}%")
                        ->orWhere('seo_keys', 'like', "%{$keyword}%");
                });
            }
        }

        $sort = $request->input('sort', $request->input('sort_by', 'latest'));
        if ($sort === 'oldest') {
            $query->orderByRaw('COALESCE(published_at, created_at) asc');
        } else {
            $query->orderByRaw('COALESCE(published_at, created_at) desc');
        }

        // Static SEO generation requests the full article catalogue so no published URL is omitted.
        $perPage = min(max((int) $request->input('limit', $request->input('per_page', 12)), 1), 200);
        $posts = $query->paginate($perPage)->withQueryString();
        $withContent = $request->boolean('include_content');

        return ApiResponse::success(
            $posts->getCollection()
                ->map(fn (Post $post) => $this->transformPost($post, $withContent))
                ->values(),
            'Lấy danh sách bài viết thành công.',
            [
                'current_page' => $posts->currentPage(),
                'last_page' => $posts->lastPage(),
                'per_page' => $posts->perPage(),
                'total' => $posts->total(),
            ]
        );
    }

    /**
     * Get public post detail.
     */
    public function postDetail($idOrSlug)
    {
        $post = Post::query()
            ->where('is_active', true)
            ->where(function ($subQuery) {
                $subQuery->whereNull('published_at')
                    ->orWhere('published_at', '<=', now());
            })
            ->where(function ($query) use ($idOrSlug) {
                $query->where('id', $idOrSlug)->orWhere('slug', $idOrSlug);
            })
            ->with('category')
            ->first();

        if (! $post) {
            return ApiResponse::error('Bài viết không tồn tại.', 404);
        }

        return ApiResponse::success($this->transformPost($post, true));
    }

    private function transformPost(Post $post, bool $withContent = false): array
    {
        $summary = $this->localizedField($post, 'summary');
        $content = $this->localizedField($post, 'content');
        $plainContent = trim(strip_tags($summary ?: $content));
        $articleText = trim(preg_replace('/\s+/u', ' ', strip_tags($content)) ?? '');
        $description = $this->localizedField($post, 'seo_description') ?: Str::limit($plainContent, 155, '');

        $data = [
            'id' => $post->id,
            'title' => $this->localizedField($post, 'title', 'Bài viết'),
            'slug' => $post->slug,
            'summary' => $summary ?: Str::limit($plainContent, 180, ''),
            'image_url' => $post->image_url,
            'seo_title' => $this->localizedField($post, 'seo_title') ?: $this->localizedField($post, 'title', 'Bài viết'),
            'seo_description' => $description,
            'seo_keys' => $post->seo_keys,
            'word_count' => $articleText === '' ? 0 : count(preg_split('/\s+/u', $articleText, -1, PREG_SPLIT_NO_EMPTY) ?: []),
            'in_language' => (app()->getLocale() ?: 'vi') === 'vi' ? 'vi-VN' : str_replace('_', '-', app()->getLocale()),
            'published_at' => optional($post->published_at)->toIso8601String(),
            'updated_at' => optional($post->updated_at)->toIso8601String(),
            'category' => $post->category ? $this->transformPostCategory($post->category) : null,
        ];

        if ($withContent) {
            $data['content'] = $content;
        }

        return $data;
    }

    private function transformPostCategory(PostCategory $category): array
    {
        return [
            'id' => $category->id,
            'name' => $this->localizedField($category, 'name', 'Bài viết'),
            'slug' => $category->slug,
            'description' => $this->localizedField($category, 'description'),
            'posts_count' => (int) ($category->posts_count ?? 0),
        ];
    }

    private function localizedField($model, string $field, string $fallback = ''): string
    {
        $locales = array_values(array_unique([app()->getLocale(), 'vi', 'en']));

        if (method_exists($model, 'getTranslations')) {
            $translations = $model->getTranslations($field);
            foreach ($locales as $locale) {
                $value = $translations[$locale] ?? null;
                if (is_string($value) && trim($value) !== '') {
                    return trim($value);
                }
            }
        }

        $value = $model->{$field} ?? null;
        if (is_array($value)) {
            foreach ($locales as $locale) {
                if (! empty($value[$locale]) && is_string($value[$locale])) {
                    return trim($value[$locale]);
                }
            }
        }

        return is_string($value) && trim($value) !== '' ? trim($value) : $fallback;
    }

    /**
     * Return active vouchers that can currently apply to a subtotal.
     */
    public function eligibleVouchers(Request $request)
    {
        $subtotal = (float) $request->input('amount', $request->input('subtotal', 0));

        $vouchers = Voucher::query()
            ->where('is_active', true)
            ->orderByDesc('id')
            ->get()
            ->filter(fn (Voucher $voucher) => $voucher->isValidForOrder($subtotal))
            ->map(function (Voucher $voucher) use ($subtotal) {
                return [
                    'id' => $voucher->id,
                    'code' => $voucher->code,
                    'name' => $voucher->name,
                    'description' => $voucher->description,
                    'type' => $voucher->type,
                    'value' => (float) $voucher->value,
                    'discount_type' => $voucher->type,
                    'discount_value' => (float) $voucher->value,
                    'discount_amount' => $voucher->calculateDiscount($subtotal),
                    'min_order_amount' => (float) $voucher->min_order_amount,
                    'max_discount_amount' => (float) $voucher->max_discount_amount,
                    'end_date' => optional($voucher->end_date)->toIso8601String(),
                ];
            })
            ->values();

        return ApiResponse::success($vouchers);
    }

    /**
     * Recalculate cart totals from database prices.
     */
    public function recalculateCart(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.variant_id' => 'nullable|exists:product_variants,id',
            'items.*.product_variant_id' => 'nullable|exists:product_variants,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.custom_text' => 'nullable|string|max:500',
            'items.*.custom_image_name' => 'nullable|string|max:255',
            'items.*.custom_image_url' => 'nullable|string|max:2048',
            'code' => 'nullable|string',
            'voucher_code' => 'nullable|string',
            'shipping_fee' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Dữ liệu không hợp lệ.', 422, $validator->errors()->toArray());
        }

        $pricing = $this->buildCartPricing(
            $request->input('items'),
            $request->input('voucher_code', $request->input('code')),
            (float) $request->input('shipping_fee', 0)
        );

        if (! empty($pricing['error'])) {
            return ApiResponse::error($pricing['error'], 422);
        }

        return ApiResponse::success([
            'items' => $pricing['items'],
            'subtotal' => $pricing['subtotal'],
            'discount_amount' => $pricing['discount'],
            'shipping_fee' => $pricing['shipping_fee'],
            'grand_total' => $pricing['grand_total'],
            'voucher_code' => $pricing['voucher']?->code,
            'applied_promotions' => $pricing['voucher'] ? [[
                'code' => $pricing['voucher']->code,
                'promotion_type' => 'coupon',
                'discount_amount' => $pricing['discount'],
                'type' => $pricing['voucher']->type,
                'value' => (float) $pricing['voucher']->value,
            ]] : [],
            'gifts' => [],
            'is_free_shipping' => $pricing['shipping_fee'] <= 0,
        ], 'Tính lại giỏ hàng thành công.');
    }

    private function buildCartPricing(array $items, ?string $voucherCode = null, float $shippingFee = 0.0): array
    {
        $subtotal = 0.0;
        $orderItemsData = [];

        foreach ($items as $item) {
            $product = Product::query()
                ->where('id', $item['product_id'])
                ->where('is_active', true)
                ->first();

            if (!$product) {
                return ['error' => "Sản phẩm ID {$item['product_id']} không tồn tại hoặc đã ngừng kinh doanh."];
            }

            $price = (float) $product->price;
            $variant = null;
            $variantName = null;
            $sku = $product->sku;
            $variantId = $item['variant_id'] ?? $item['product_variant_id'] ?? null;

            if (!empty($variantId)) {
                $variant = ProductVariant::query()
                    ->where('id', $variantId)
                    ->where('product_id', $product->id)
                    ->first();

                if (!$variant || !$variant->is_active) {
                    return ['error' => "Biến thể sản phẩm không hợp lệ cho {$product->name}."];
                }

                if ($variant->price !== null) {
                    $price = (float) $variant->price;
                }

                $variantName = $variant->name;
                $sku = $variant->sku ?: $product->sku;
            }

            $qty = (int) $item['quantity'];

            if ($product->manage_stock && $product->stock_quantity < $qty) {
                return ['error' => "Sản phẩm {$product->name} đã hết hàng hoặc không đủ tồn kho."];
            }

            if ($variant && $variant->stock_quantity !== null && $variant->stock_quantity < $qty) {
                return ['error' => "Biến thể {$variant->name} không đủ tồn kho."];
            }

            $total = $price * $qty;
            $subtotal += $total;
            $customText = trim((string) ($item['custom_text'] ?? $item['customText'] ?? ''));
            $customImageName = trim((string) ($item['custom_image_name'] ?? $item['customImageName'] ?? ''));
            $customImageUrl = $this->sanitizeCustomizationImageUrl(
                $item['custom_image_url'] ?? $item['customImageUrl'] ?? ''
            );

            $orderItemsData[] = [
                'product_id' => $product->id,
                'product_variant_id' => $variant?->id,
                'product_name' => $product->name,
                'variant_name' => $variantName,
                'sku' => $sku,
                'price' => $price,
                'quantity' => $qty,
                'total' => $total,
                'custom_text' => $customText !== '' ? Str::limit($customText, 500, '') : null,
                'custom_image_name' => $customImageName !== '' ? Str::limit($customImageName, 255, '') : null,
                'custom_image_url' => $customImageUrl,
            ];
        }

        $discount = 0.0;
        $voucher = null;
        if ($voucherCode) {
            $voucher = Voucher::query()->where('code', strtoupper($voucherCode))->first();
            if (!$voucher || !$voucher->isValidForOrder($subtotal)) {
                return ['error' => 'Mã giảm giá không hợp lệ cho đơn hàng này.'];
            }
            $discount = $voucher->calculateDiscount($subtotal);
        }

        return [
            'items' => $orderItemsData,
            'subtotal' => $subtotal,
            'discount' => $discount,
            'shipping_fee' => max(0.0, $shippingFee),
            'grand_total' => max(0.0, $subtotal - $discount) + max(0.0, $shippingFee),
            'voucher' => $voucher,
        ];
    }

    private function sanitizeCustomizationImageUrl(mixed $url): ?string
    {
        $url = trim((string) $url);
        if ($url === '') {
            return null;
        }

        if (Str::startsWith($url, ['https://', 'http://', '/storage/', 'storage/'])) {
            return Str::limit($url, 2048, '');
        }

        return null;
    }

    private function normalizeUploadedPublicUrl(Request $request, string $url): string
    {
        $backendBase = rtrim($request->getSchemeAndHttpHost() . $request->getBaseUrl(), '/');
        if (Str::startsWith($url, ['/storage/', 'storage/'])) {
            return $backendBase . '/' . ltrim($url, '/');
        }

        $parts = parse_url($url);
        $host = $parts['host'] ?? '';
        $path = $parts['path'] ?? '';
        if ($host === $request->getHost() && Str::startsWith($path, '/storage/')) {
            $query = isset($parts['query']) ? '?' . $parts['query'] : '';
            return $backendBase . $path . $query;
        }

        return $url;
    }

    /**
     * Validate and apply voucher discount to cart.
     */
    public function applyVoucher(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'code' => 'required|string',
            'subtotal' => 'required|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Dữ liệu không hợp lệ.', 422, $validator->errors()->toArray());
        }

        $code = strtoupper($request->input('code'));
        $subtotal = (float) $request->input('subtotal');

        $voucher = Voucher::query()->where('code', $code)->first();

        if (!$voucher) {
            return ApiResponse::error('Mã giảm giá không tồn tại.', 422);
        }

        if (!$voucher->is_active) {
            return ApiResponse::error('Mã giảm giá đã bị khóa.', 422);
        }

        $now = now();
        if ($voucher->start_date && $voucher->start_date->isAfter($now)) {
            return ApiResponse::error('Chương trình giảm giá chưa bắt đầu.', 422);
        }
        if ($voucher->end_date && $voucher->end_date->isBefore($now)) {
            return ApiResponse::error('Mã giảm giá đã hết hạn sử dụng.', 422);
        }

        if ($voucher->quantity !== null && $voucher->used_count >= $voucher->quantity) {
            return ApiResponse::error('Mã giảm giá đã hết lượt sử dụng.', 422);
        }

        if ($subtotal < (float) $voucher->min_order_amount) {
            return ApiResponse::error('Đơn hàng chưa đạt giá trị tối thiểu để áp dụng mã này.', 422);
        }

        $discount = $voucher->calculateDiscount($subtotal);

        return ApiResponse::success([
            'code' => $voucher->code,
            'type' => $voucher->type,
            'value' => $voucher->value,
            'discount_amount' => $discount,
        ], 'Áp dụng mã giảm giá thành công.');
    }

    public function uploadCustomizationImage(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'file' => 'required|image|max:5120',
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Ảnh không hợp lệ. Vui lòng chọn ảnh tối đa 5MB.', 422, $validator->errors()->toArray());
        }

        try {
            $file = $request->file('file');
            $url = app(CloudinaryService::class)->uploadFile($file, 'order-customizations');
            $url = $this->normalizeUploadedPublicUrl($request, $url);

            return ApiResponse::success([
                'url' => $url,
                'name' => $file->getClientOriginalName(),
                'size' => $file->getSize(),
                'mime_type' => $file->getMimeType(),
            ], 'Tải ảnh cá nhân hóa thành công.');
        } catch (\Throwable $e) {
            Log::error('Customization image upload failed: ' . $e->getMessage());
            return ApiResponse::error('Không thể tải ảnh lên. Vui lòng thử lại.', 500);
        }
    }

    /**
     * Create checkout order.
     */
    public function checkout(Request $request)
    {
        $input = $request->all();
        $paymentAliases = [
            'bank' => 'bank_transfer',
            'momo' => 'online',
            'zalopay' => 'online',
        ];
        if (isset($input['payment_method'])) {
            $input['payment_method'] = $paymentAliases[$input['payment_method']] ?? $input['payment_method'];
        }
        if (isset($input['note']) && !isset($input['notes'])) {
            $input['notes'] = $input['note'];
        }
        if (empty($input['voucher_code']) && !empty($input['applied_vouchers'][0])) {
            $input['voucher_code'] = $input['applied_vouchers'][0];
        }
        $request->merge($input);

        $validator = Validator::make($request->all(), [
            'customer_name' => 'required|string|max:255',
            'customer_email' => 'required|email|max:255',
            'customer_phone' => 'required|string|max:20',
            'shipping_address' => 'required|string|max:500',
            'payment_method' => 'required|string|in:cod,bank_transfer,online,sepay,stripe,vnpay',
            'notes' => 'nullable|string|max:500',
            'items' => 'required|array|min:1',
            'items.*.product_id' => 'required|exists:products,id',
            'items.*.variant_id' => 'nullable|exists:product_variants,id',
            'items.*.product_variant_id' => 'nullable|exists:product_variants,id',
            'items.*.quantity' => 'required|integer|min:1',
            'items.*.custom_text' => 'nullable|string|max:500',
            'items.*.custom_image_name' => 'nullable|string|max:255',
            'items.*.custom_image_url' => 'nullable|string|max:2048',
            'voucher_code' => 'nullable|string',
            'shipping_fee' => 'nullable|numeric|min:0',
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Dữ liệu không hợp lệ.', 422, $validator->errors()->toArray());
        }

        $paymentMethod = $request->input('payment_method');
        if (in_array($paymentMethod, ['vnpay', 'sepay', 'stripe'])) {
            if (!\App\Models\Addon::isPurchased($paymentMethod)) {
                return ApiResponse::error("Tính năng thanh toán qua " . strtoupper($paymentMethod) . " chưa được mở khóa. Vui lòng mua Addon để sử dụng.", 403);
            }
        }

        $pricing = $this->buildCartPricing(
            $request->input('items'),
            $request->input('voucher_code'),
            (float) $request->input('shipping_fee', 0)
        );

        if (! empty($pricing['error'])) {
            return ApiResponse::error($pricing['error'], 422);
        }

        $subtotal = $pricing['subtotal'];
        $discount = $pricing['discount'];
        $shippingFee = $pricing['shipping_fee'];
        $grandTotal = $pricing['grand_total'];
        $voucher = $pricing['voucher'];
        $orderItemsData = $pricing['items'];

        // Try using Sanctum guard for checking logged-in user id
        $customerId = $request->user('sanctum')?->id;

        DB::beginTransaction();
        try {
            $order = Order::query()->create([
                'order_number' => 'ORD-' . strtoupper(Str::random(10)),
                'customer_name' => $request->customer_name,
                'customer_email' => $request->customer_email,
                'customer_phone' => $request->customer_phone,
                'shipping_address' => $request->shipping_address,
                'payment_method' => $request->payment_method,
                'payment_status' => 'pending',
                'status' => 'pending',
                'subtotal' => $subtotal,
                'discount' => $discount,
                'shipping_fee' => $shippingFee,
                'grand_total' => $grandTotal,
                'notes' => $request->notes,
                'user_id' => $customerId ?: null,
            ]);

            foreach ($orderItemsData as $itemData) {
                $order->items()->create($itemData);

                // Update inventory
                $product = Product::find($itemData['product_id']);
                if ($product && $product->manage_stock) {
                    $product->decrement('stock_quantity', $itemData['quantity']);
                }

                if (!empty($itemData['product_variant_id'])) {
                    ProductVariant::query()
                        ->where('id', $itemData['product_variant_id'])
                        ->decrement('stock_quantity', $itemData['quantity']);
                }
            }

            if ($voucher) {
                $voucher->increment('used_count');
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error("Checkout transaction failed: " . $e->getMessage());
            return ApiResponse::error('Có lỗi xảy ra khi tạo đơn hàng. Vui lòng thử lại.', 500);
        }

        $paymentUrl = null;
        if ($order->payment_method === 'vnpay') {
            $vnpayService = app(\App\Services\VNPAYService::class);
            $paymentUrl = $vnpayService->createPayment($order, $request->input('redirect_url'));

            if (!$paymentUrl) {
                DB::beginTransaction();
                try {
                    $order->update(['status' => 'cancelled', 'notes' => $order->notes . ' (Khởi tạo thanh toán VNPAY thất bại)']);
                    // Restore stock quantity
                    foreach ($order->items as $item) {
                        $product = Product::find($item->product_id);
                        if ($product && $product->manage_stock) {
                            $product->increment('stock_quantity', $item->quantity);
                        }
                    }
                    DB::commit();
                } catch (\Exception $ex) {
                    DB::rollBack();
                }
                return ApiResponse::error('Không thể khởi tạo giao dịch thanh toán VNPAY. Vui lòng cấu hình các trường TMN Code, Hash Secret hoặc thử lại sau.', 422);
            }
        }

        // Notification providers are isolated and never roll back a successful checkout.
        $order->load('items');
        \App\Support\NotificationHelper::sendNewOrderNotification($order);

        $responseData = $order->toArray();
        if ($paymentUrl) {
            $responseData['payment_url'] = $paymentUrl;
        }

        return ApiResponse::success($responseData, 'Đặt hàng thành công.');
    }

    /**
     * Track order status without login.
     */
    public function trackOrder(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'order_number' => 'required|string',
            'contact' => 'required|string',
        ]);

        if ($validator->fails()) {
            return ApiResponse::error('Dữ liệu không hợp lệ.', 422, $validator->errors()->toArray());
        }

        $orderNumber = $request->input('order_number');
        $contact = $request->input('contact');

        $order = Order::query()
            ->where('order_number', $orderNumber)
            ->where(function ($q) use ($contact) {
                $q->where('customer_email', $contact)
                    ->orWhere('customer_phone', $contact);
            })
            ->with(['items.product', 'items.variant'])
            ->first();

        if (!$order) {
            return ApiResponse::error('Đơn hàng không tồn tại hoặc thông tin xác thực không đúng.', 404);
        }

        return ApiResponse::success($order);
    }

    /**
     * Get logged-in customer's order history.
     */
    public function orderHistory(Request $request)
    {
        $user = $request->user();
        $orders = Order::query()
            ->where('user_id', $user->id)
            ->latest()
            ->paginate(10);

        return ApiResponse::success($orders->items(), 'Lấy lịch sử đơn hàng thành công.', [
            'current_page' => $orders->currentPage(),
            'last_page' => $orders->lastPage(),
            'per_page' => $orders->perPage(),
            'total' => $orders->total(),
        ]);
    }

    /**
     * Get detail of logged-in customer's specific order.
     */
    public function orderDetail($orderNumber, Request $request)
    {
        $user = $request->user();
        $order = Order::query()
            ->where('order_number', $orderNumber)
            ->where('user_id', $user->id)
            ->with(['items.product', 'items.variant'])
            ->first();

        if (!$order) {
            return ApiResponse::error('Không tìm thấy đơn hàng.', 404);
        }

        return ApiResponse::success($order);
    }

    /**
     * Cancel logged-in customer's specific order.
     */
    public function cancelOrder($id, Request $request)
    {
        $user = $request->user();
        $order = Order::query()
            ->where('user_id', $user->id)
            ->where(function ($q) use ($id) {
                $q->where('id', $id)->orWhere('order_number', $id);
            })
            ->first();

        if (!$order) {
            return ApiResponse::error('Không tìm thấy đơn hàng.', 404);
        }

        if ($order->status !== 'pending') {
            return ApiResponse::error('Chỉ có thể hủy đơn hàng ở trạng thái Chờ xác nhận.', 400);
        }

        $order->update([
            'status' => 'cancelled'
        ]);

        return ApiResponse::success($order, 'Hủy đơn hàng thành công.');
    }

    /**
     * Submit a review for a specific product.
     */
    public function storeReview(Request $request, $idOrSlug)
    {
        $product = Product::query()
            ->where('is_active', true)
            ->where(function ($q) use ($idOrSlug) {
                $q->where('id', $idOrSlug)->orWhere('slug', $idOrSlug);
            })
            ->first();

        if (!$product) {
            return ApiResponse::error('Sản phẩm không tồn tại.', 404);
        }

        $user = $request->user('sanctum');

        $rules = [
            'rating' => 'required|integer|min:1|max:5',
            'comment' => 'nullable|string|max:1000',
        ];

        if (!$user) {
            $rules['customer_name'] = 'required|string|max:255';
            $rules['customer_email'] = 'required|email|max:255';
        } else {
            $rules['customer_name'] = 'nullable|string|max:255';
            $rules['customer_email'] = 'nullable|email|max:255';
        }

        $validator = Validator::make($request->all(), $rules);

        if ($validator->fails()) {
            return ApiResponse::error('Dữ liệu không hợp lệ.', 422, $validator->errors()->toArray());
        }

        $customerName = $request->input('customer_name') ?: ($user ? $user->name : null);
        $customerEmail = $request->input('customer_email') ?: ($user ? $user->email : null);

        // Verify that the customer has purchased the product
        $hasPurchased = false;
        if ($user) {
            $hasPurchased = Order::query()
                ->where(function ($q) use ($user) {
                    $q->where('user_id', $user->id)
                      ->orWhere('customer_email', $user->email);
                })
                ->where('status', '!=', 'cancelled')
                ->whereHas('items', function ($q) use ($product) {
                    $q->where('product_id', $product->id);
                })
                ->exists();
        } else {
            $hasPurchased = Order::query()
                ->where('customer_email', $customerEmail)
                ->where('status', '!=', 'cancelled')
                ->whereHas('items', function ($q) use ($product) {
                    $q->where('product_id', $product->id);
                })
                ->exists();
        }

        if (!$hasPurchased) {
            return ApiResponse::error('Bạn chỉ có thể đánh giá sản phẩm sau khi đã mua hàng.', 403);
        }

        $review = Review::query()->create([
            'product_id' => $product->id,
            'user_id' => $user ? $user->id : null,
            'customer_name' => $customerName,
            'customer_email' => $customerEmail,
            'rating' => (int) $request->input('rating'),
            'comment' => $request->input('comment'),
            'is_visible' => true,
        ]);


        return ApiResponse::success($review, 'Gửi đánh giá thành công.');
    }

    /**
     * Handle Instant Payment Notification (IPN) from VNPAY.
     */
    public function vnpayIpn(Request $request)
    {
        $params = $request->all();
        Log::info('Received VNPAY IPN: ', $params);

        $vnpayService = app(\App\Services\VNPAYService::class);
        if (!$vnpayService->verifyIpnSignature($params)) {
            Log::warning('VNPAY IPN signature verification failed.');
            return response()->json([
                'RspCode' => '97',
                'Message' => 'Invalid signature'
            ]);
        }

        $orderNumber = $params['vnp_TxnRef'] ?? null;
        $responseCode = $params['vnp_ResponseCode'] ?? null;
        $transactionStatus = $params['vnp_TransactionStatus'] ?? null;

        if (!$orderNumber) {
            return response()->json([
                'RspCode' => '01',
                'Message' => 'Order not found'
            ]);
        }

        $order = Order::where('order_number', $orderNumber)->first();
        if (!$order) {
            Log::warning("VNPAY IPN order not found: {$orderNumber}");
            return response()->json([
                'RspCode' => '01',
                'Message' => 'Order not found'
            ]);
        }

        // Check if the order amount matches the VNPAY transaction amount (vnp_Amount is multiplied by 100)
        $vnpAmount = (int) ($params['vnp_Amount'] ?? 0);
        $orderAmount = (int) round($order->grand_total) * 100;
        if ($vnpAmount !== $orderAmount) {
            Log::warning("VNPAY IPN amount mismatch for order {$orderNumber}. VNPAY: {$vnpAmount}, Order: {$orderAmount}");
            return response()->json([
                'RspCode' => '04',
                'Message' => 'Invalid amount'
            ]);
        }

        // Check if order payment is already processed
        if ($order->payment_status === 'paid') {
            return response()->json([
                'RspCode' => '02',
                'Message' => 'Order already confirmed'
            ]);
        }

        if ($responseCode === '00' && $transactionStatus === '00') {
            // Payment success
            DB::beginTransaction();
            try {
                $order->update([
                    'payment_status' => 'paid',
                    'status' => 'processing',
                ]);
                DB::commit();
                Log::info("VNPAY IPN payment success for order: {$orderNumber}");
            } catch (\Exception $e) {
                DB::rollBack();
                Log::error("Failed to update order status via VNPAY IPN: " . $e->getMessage());
                return response()->json([
                    'RspCode' => '99',
                    'Message' => 'Database error'
                ]);
            }
        } else {
            // Payment failed or cancelled
            DB::beginTransaction();
            try {
                $order->update([
                    'payment_status' => 'failed',
                ]);
                DB::commit();
                Log::info("VNPAY IPN payment failed for order: {$orderNumber} (ResponseCode: {$responseCode})");
            } catch (\Exception $e) {
                DB::rollBack();
            }
        }

        return response()->json([
            'RspCode' => '00',
            'Message' => 'Confirm success'
        ]);
    }

    /**
     * Show mock VNPAY payment page.
     */
    public function vnpayMockPayment(Request $request)
    {
        $orderId = $request->query('order_id');
        $amount = $request->query('amount');
        $redirectUrl = $request->query('redirect_url');

        $order = Order::where('order_number', $orderId)->firstOrFail();

        return view('vnpay_mock', [
            'order' => $order,
            'amount' => $amount,
            'redirectUrl' => $redirectUrl
        ]);
    }

    /**
     * Submit mock VNPAY payment simulation.
     */
    public function vnpayMockSubmit(Request $request)
    {
        $orderId = $request->input('order_id');
        $amount = $request->input('amount');
        $status = $request->input('status'); // 'success' or 'cancel'
        $redirectUrl = $request->input('redirect_url');

        $paymentMethod = \App\Models\PaymentMethod::where('method_code', 'vnpay')->firstOrFail();
        $settings = $paymentMethod->settings;
        $tmnCode = $settings['tmn_code'] ?? 'mock';
        $hashSecret = $settings['hash_secret'] ?? 'mock';

        $ipnParams = [
            'vnp_TmnCode' => $tmnCode,
            'vnp_Amount' => $amount * 100,
            'vnp_Command' => 'pay',
            'vnp_CreateDate' => date('YmdHis'),
            'vnp_CurrCode' => 'VND',
            'vnp_IpAddr' => '127.0.0.1',
            'vnp_Locale' => 'vn',
            'vnp_OrderInfo' => 'Thanh toan don hang ' . $orderId,
            'vnp_OrderType' => 'other',
            'vnp_ReturnUrl' => $redirectUrl,
            'vnp_TxnRef' => $orderId,
            'vnp_Version' => '2.1.0',
            'vnp_ResponseCode' => $status === 'success' ? '00' : '24', // '24' is user cancelled
            'vnp_TransactionStatus' => $status === 'success' ? '00' : '02',
            'vnp_TransactionNo' => 'MOCK_VNP_TRANS_' . time(),
        ];

        // Generate signature using sorted parameters and configured hashSecret
        ksort($ipnParams);
        $hashData = "";
        $i = 0;
        foreach ($ipnParams as $key => $value) {
            if ($i == 1) {
                $hashData .= '&' . urlencode($key) . "=" . urlencode($value);
            } else {
                $hashData .= urlencode($key) . "=" . urlencode($value);
                $i = 1;
            }
        }

        $ipnParams['vnp_SecureHash'] = hash_hmac('sha512', $hashData, $hashSecret);

        // Call vnpayIpn internally via request object simulation
        $ipnRequest = Request::create(route('api.payment.vnpay.ipn'), 'GET', $ipnParams);
        $this->vnpayIpn($ipnRequest);

        // Build redirect URL matching VNPAY return params format
        $parsedUrl = parse_url($redirectUrl);
        $queryParams = [];
        if (isset($parsedUrl['query'])) {
            parse_str($parsedUrl['query'], $queryParams);
        }
        $queryParams = array_merge($queryParams, $ipnParams);
        $newQuery = http_build_query($queryParams);

        $scheme = isset($parsedUrl['scheme']) ? $parsedUrl['scheme'] . '://' : '';
        $host = isset($parsedUrl['host']) ? $parsedUrl['host'] : '';
        $port = isset($parsedUrl['port']) ? ':' . $parsedUrl['port'] : '';
        $path = isset($parsedUrl['path']) ? $parsedUrl['path'] : '';

        $finalRedirectUrl = $scheme . $host . $port . $path . '?' . $newQuery;

        return redirect($finalRedirectUrl);
    }

    /**
     * Get descendant category IDs recursively.
     */
    private function getDescendantCategoryIds(Category $category): array
    {
        $ids = [$category->id];

        $children = Category::query()
            ->where('parent_id', $category->id)
            ->get();

        foreach ($children as $child) {
            $ids = array_merge($ids, $this->getDescendantCategoryIds($child));
        }

        return array_unique($ids);
    }
}
