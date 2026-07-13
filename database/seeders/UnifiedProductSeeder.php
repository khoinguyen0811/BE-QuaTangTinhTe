<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

class UnifiedProductSeeder extends Seeder
{
    public function run(): void
    {
        $jsonPath = base_path('../scratch/products_unified.json');
        if (!file_exists($jsonPath)) {
            $this->command->warn("JSON data file not found at: {$jsonPath}");
            return;
        }

        $json = file_get_contents($jsonPath);
        $productsData = json_decode($json, true);
        if (!$productsData) {
            $this->command->error("Failed to decode products JSON.");
            return;
        }

        $this->command->info("Loaded " . count($productsData) . " products from JSON.");

        // Categories definitions
        $categories = [
            ['name' => ['vi' => 'Tất cả sản phẩm', 'en' => 'All Products'], 'slug' => 'all', 'sort_order' => 0, 'description' => ['vi' => 'Tất cả sản phẩm của cửa hàng', 'en' => 'All store products']],
            ['name' => ['vi' => 'Khối chữ nhật', 'en' => 'Rectangular Block'], 'slug' => 'khoi-chu-nhat', 'sort_order' => 1, 'description' => ['vi' => 'Pha lê hình khối chữ nhật', 'en' => 'Rectangular block crystals']],
            ['name' => ['vi' => 'Hoa hướng dương', 'en' => 'Sunflower'], 'slug' => 'hoa-huong-duong', 'sort_order' => 2, 'description' => ['vi' => 'Pha lê hình hoa hướng dương', 'en' => 'Sunflower crystals']],
            ['name' => ['vi' => 'Trái tim', 'en' => 'Heart'], 'slug' => 'trai-tim', 'sort_order' => 3, 'description' => ['vi' => 'Pha lê hình trái tim', 'en' => 'Heart-shaped crystals']],
            ['name' => ['vi' => 'Đa giác', 'en' => 'Polygon'], 'slug' => 'da-giac', 'sort_order' => 4, 'description' => ['vi' => 'Pha lê hình đa giác', 'en' => 'Polygon crystals']],
            ['name' => ['vi' => 'Giọt nước', 'en' => 'Water Drop'], 'slug' => 'giot-nuoc', 'sort_order' => 5, 'description' => ['vi' => 'Pha lê hình giọt nước', 'en' => 'Water drop crystals']],
        ];

        $categoriesMap = [];
        foreach ($categories as $cat) {
            $category = Category::query()->updateOrCreate(
                ['slug' => $cat['slug']],
                [
                    'name' => $cat['name'],
                    'description' => $cat['description'],
                    'sort_order' => $cat['sort_order'],
                    'is_active' => true,
                ]
            );
            $categoriesMap[$cat['name']['vi']] = $category->id;
        }

        foreach ($productsData as $pIdx => $p) {
            $titleLower = mb_strtolower($p['title']);
            $catName = 'Khối chữ nhật';
            if (str_contains($titleLower, 'trái tim')) {
                $catName = 'Trái tim';
            } elseif (str_contains($titleLower, 'hướng dương')) {
                $catName = 'Hoa hướng dương';
            } elseif (str_contains($titleLower, 'đa giác')) {
                $catName = 'Đa giác';
            } elseif (str_contains($titleLower, 'giọt nước')) {
                $catName = 'Giọt nước';
            }

            $catId = $categoriesMap[$catName] ?? null;

            // Generate slug from title
            $slug = Str::slug($p['title']);

            // Insert / update product
            $product = Product::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'category_id' => $catId,
                    'name' => [
                        'vi' => $p['title'],
                        'en' => $p['title'],
                    ],
                    'price' => $p['base_price'],
                    'compare_at_price' => $p['base_price'] * 1.25,
                    'image_url' => $p['image_url'],
                    'material' => 'Pha lê cao cấp K9 nhập khẩu',
                    'print_detail' => 'Khắc Laser 3D chân dung tinh tế',
                    'style' => 'Khối pha lê nghệ thuật',
                    'care_instructions' => 'Tránh va đập mạnh, lau chùi nhẹ nhàng bằng khăn mềm sạch.',
                    'is_featured' => ($pIdx < 4),
                    'is_active' => true,
                    'manage_stock' => true,
                    'stock_quantity' => 100,
                ]
            );

            // Seed variants
            foreach ($p['variants'] as $vIdx => $v) {
                // Deduplicate SKUs
                $finalSku = $v['sku'];
                while (ProductVariant::query()->where('sku', $finalSku)->where('product_id', '!=', $product->id)->exists()) {
                    $finalSku .= '-S';
                }
                
                $optionValues = [
                    'size' => $v['size'],
                    'material' => $v['material']
                ];

                ProductVariant::query()->updateOrCreate(
                    [
                        'product_id' => $product->id,
                        'sku' => $finalSku
                    ],
                    [
                        'name' => [
                            'vi' => "Kích thước: {$v['size']} | Chất liệu: {$v['material']}",
                            'en' => "Size: {$v['size']} | Material: {$v['material']}",
                        ],
                        'price' => $v['price'],
                        'stock_quantity' => rand(20, 100),
                        'option_values' => $optionValues,
                        'is_active' => true,
                        'sort_order' => $vIdx,
                    ]
                );
            }
        }

        $this->command->info("Finished seeding products and variants successfully.");
    }
}
