<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\FeatureSetting;
use App\Models\Product;
use App\Models\User;
use App\Services\Catalog\CategoryService;
use App\Services\Catalog\ProductService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CatalogCrudTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_services_can_create_update_and_delete_records(): void
    {
        $categoryService = app(CategoryService::class);
        $productService = app(ProductService::class);

        $category = $categoryService->create([
            'name' => 'Danh muc test',
            'description' => 'Mo ta',
            'is_active' => true,
        ]);

        $product = $productService->create([
            'category_id' => $category->id,
            'name' => 'San pham test',
            'sku' => 'TEST-SKU-1',
            'price' => 100000,
            'stock_quantity' => 5,
            'manage_stock' => true,
            'is_active' => true,
        ]);

        $variant = $productService->createVariant($product, [
            'name' => 'Do / M',
            'sku' => 'TEST-SKU-1-RED-M',
            'option_names' => ['Color', 'Size'],
            'option_values' => ['Red', 'M'],
            'price' => 110000,
            'stock_quantity' => 2,
            'is_active' => true,
        ]);

        $this->assertSame('Danh muc test', $category->getTranslation('name', 'vi'));
        $this->assertSame('San pham test', $product->getTranslation('name', 'vi'));
        $this->assertSame(['Color' => 'Red', 'Size' => 'M'], $variant->option_values);

        $productService->deleteVariant($variant);
        $productService->delete($product);
        $categoryService->delete($category);

        $this->assertDatabaseCount('product_variants', 0);
        $this->assertDatabaseCount('products', 0);
        $this->assertDatabaseCount('categories', 0);
    }

    public function test_admin_catalog_pages_render_for_authenticated_user(): void
    {
        FeatureSetting::query()->create([
            'feature_code' => 'catalog',
            'is_enabled' => true,
        ]);

        $category = Category::query()->create([
            'name' => ['vi' => 'Danh muc'],
            'slug' => 'danh-muc',
            'is_active' => true,
        ]);

        $brand = \App\Models\Brand::query()->create([
            'name' => ['vi' => 'Intel'],
            'slug' => 'intel',
            'is_active' => true,
        ]);

        Product::query()->create([
            'category_id' => $category->id,
            'brand_id' => $brand->id,
            'name' => ['vi' => 'San pham'],
            'slug' => 'san-pham',
            'sku' => 'SKU-1',
            'price' => 100000,
            'is_active' => true,
        ]);

        $this->actingAs(User::factory()->create());

        $this->get('/vi/admin/categories')->assertOk();
        $this->get('/vi/admin/products')->assertOk();
        $this->get('/vi/admin/products?category_id=' . $category->id)->assertOk();
        $this->get('/vi/admin/products?brand_id=' . $brand->id)->assertOk();
        $this->get('/vi/admin/products?status=1')->assertOk();
        $this->get('/vi/admin/products?status=0')->assertOk();
    }

    public function test_admin_can_upload_quick_update_and_sort_categories(): void
    {
        Storage::fake('public');

        FeatureSetting::query()->create([
            'feature_code' => 'catalog',
            'is_enabled' => true,
        ]);

        $first = Category::query()->create([
            'name' => ['vi' => 'First'],
            'slug' => 'first',
            'sort_order' => 0,
            'is_active' => true,
        ]);
        $second = Category::query()->create([
            'name' => ['vi' => 'Second'],
            'slug' => 'second',
            'sort_order' => 1,
            'is_active' => true,
        ]);

        $this->actingAs(User::factory()->create());

        $this->post('/vi/admin/categories', [
            'name' => 'Uploaded category',
            'slug' => 'uploaded-category',
            'image_file' => UploadedFile::fake()->image('category.jpg'),
            'is_active' => true,
        ])->assertRedirect('/vi/admin/categories');

        $uploaded = Category::query()->where('slug', 'uploaded-category')->firstOrFail();
        $this->assertNotNull($uploaded->image_url);

        $this->put("/vi/admin/categories/{$uploaded->id}/quick-update", [
            'name' => 'Draft category',
            'slug' => 'draft-category',
            'is_active' => false,
        ])->assertRedirect('/vi/admin/categories');

        $this->assertDatabaseHas('categories', [
            'id' => $uploaded->id,
            'slug' => 'draft-category',
            'is_active' => false,
        ]);

        $this->postJson('/vi/admin/categories/sort', [
            'ids' => [$second->id, $first->id],
            'start_order' => 0,
        ])->assertOk();

        $this->assertSame(0, $second->fresh()->sort_order);
        $this->assertSame(1, $first->fresh()->sort_order);
    }

    public function test_product_many_categories_and_system_category_protection(): void
    {
        FeatureSetting::query()->updateOrCreate(
            ['feature_code' => 'catalog'],
            ['is_enabled' => true]
        );

        $parent = Category::query()->create([
            'name' => ['vi' => 'Danh mục cha'],
            'slug' => 'danh-muc-cha',
            'is_active' => true,
        ]);

        $child1 = Category::query()->create([
            'parent_id' => $parent->id,
            'name' => ['vi' => 'Danh mục con 1'],
            'slug' => 'danh-muc-con-1',
            'is_active' => true,
        ]);

        $child2 = Category::query()->create([
            'parent_id' => $parent->id,
            'name' => ['vi' => 'Danh mục con 2'],
            'slug' => 'danh-muc-con-2',
            'is_active' => true,
        ]);

        $other = Category::query()->create([
            'name' => ['vi' => 'Danh mục khác'],
            'slug' => 'danh-muc-khac',
            'is_active' => true,
        ]);

        $service = app(ProductService::class);
        $product = $service->create([
            'name' => 'Sản phẩm đa danh mục',
            'slug' => 'san-pham-da-danh-muc',
            'price' => 50000,
            'category_ids' => [$child1->id, $child2->id, $other->id],
            'is_active' => true,
        ]);

        $this->assertCount(3, $product->categories);
        $this->assertEquals($child1->id, $product->category_id);

        $systemCategory = Category::query()->create([
            'name' => ['vi' => 'Danh mục hệ thống'],
            'slug' => 'sys-cat',
            'is_system' => true,
            'is_active' => true,
        ]);

        $this->actingAs(User::factory()->create());

        $response = $this->delete("/vi/admin/categories/{$systemCategory->id}");
        $response->assertSessionHas('error');
        $this->assertTrue(Category::query()->where('id', $systemCategory->id)->exists());

        $this->expectException(\RuntimeException::class);
        $systemCategory->delete();
    }

    public function test_public_category_recursive_filtering(): void
    {
        FeatureSetting::query()->updateOrCreate(
            ['feature_code' => 'catalog'],
            ['is_enabled' => true]
        );

        $parent = Category::query()->create([
            'name' => ['vi' => 'Danh mục cha'],
            'slug' => 'danh-muc-cha',
            'is_active' => true,
        ]);

        $child = Category::query()->create([
            'parent_id' => $parent->id,
            'name' => ['vi' => 'Danh mục con'],
            'slug' => 'danh-muc-con',
            'is_active' => true,
        ]);

        $service = app(ProductService::class);
        $product = $service->create([
            'name' => 'Sản phẩm trong danh mục con',
            'slug' => 'sp-con',
            'price' => 30000,
            'category_ids' => [$child->id],
            'is_active' => true,
        ]);

        $response = $this->getJson('/api/products?category_slug=danh-muc-cha');
        $response->assertOk();

        $data = $response->json('data');
        $this->assertNotEmpty($data);
        $this->assertEquals('sp-con', $data[0]['slug']);
    }

    public function test_product_update_checks_concurrency_conflict(): void
    {
        $category = Category::query()->create([
            'name' => 'Con Danh Muc',
            'slug' => 'con-danh-muc',
            'is_active' => true,
        ]);

        $service = app(ProductService::class);
        $product = $service->create([
            'name' => 'Sản phẩm thử nghiệm',
            'slug' => 'sp-thu-nghiem',
            'price' => 50000,
            'category_ids' => [$category->id],
            'is_active' => true,
        ]);

        $user = User::factory()->create();
        $this->actingAs($user);

        $response1 = $this->putJson("/vi/admin/products/{$product->id}", [
            'name' => 'Tên mới',
            'price' => 60000,
            'category_ids' => [$category->id],
            'updated_at' => '2026-01-01T00:00:00+00:00',
        ]);
        $response1->assertStatus(409);

        $response2 = $this->putJson("/vi/admin/products/{$product->id}", [
            'name' => 'Tên mới',
            'price' => 60000,
            'category_ids' => [$category->id],
            'updated_at' => $product->updated_at->toIso8601String(),
        ]);
        $response2->assertRedirect();
    }
}
