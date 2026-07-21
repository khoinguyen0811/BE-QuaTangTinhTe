<?php

namespace Tests\Feature;

use App\Models\Brand;
use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductRequestTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Category $category;
    private Brand $brand;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create();
        $this->category = Category::query()->create([
            'name' => 'Dien thoai',
            'slug' => 'dien-thoai',
            'is_active' => true,
        ]);
        $this->brand = Brand::query()->create([
            'name' => 'Apple',
            'slug' => 'apple',
            'is_active' => true,
        ]);
    }

    public function test_cannot_have_duplicate_variant_skus_in_payload(): void
    {
        $this->actingAs($this->admin);

        $response = $this->postJson('/vi/admin/products', [
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'name' => 'Product Name',
            'price' => 10000,
            'is_active' => true,
            'variants' => [
                ['sku' => '  sku-1  ', 'price' => 10000],
                ['sku' => 'SKU-1', 'price' => 10000],
            ]
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['variants.0.sku', 'variants.1.sku']);
    }

    public function test_cannot_use_sku_already_existing_in_database(): void
    {
        $product1 = Product::query()->create([
            'category_id' => $this->category->id,
            'name' => 'Prod 1',
            'slug' => 'prod-1',
            'price' => 10000,
            'is_active' => true,
        ]);
        ProductVariant::query()->create([
            'product_id' => $product1->id,
            'sku' => 'SKU-X',
            'name' => 'Variant X',
            'price' => 10000,
        ]);

        $this->actingAs($this->admin);

        $response = $this->postJson('/vi/admin/products', [
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'name' => 'Prod 2',
            'price' => 20000,
            'is_active' => true,
            'variants' => [
                ['sku' => 'sku-x', 'price' => 20000],
            ]
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['variants.0.sku']);
    }

    public function test_allows_keeping_same_sku_for_current_variant_during_update(): void
    {
        $product = Product::query()->create([
            'category_id' => $this->category->id,
            'name' => 'Prod 1',
            'slug' => 'prod-1',
            'price' => 10000,
            'is_active' => true,
        ]);
        $variant = ProductVariant::query()->create([
            'product_id' => $product->id,
            'sku' => 'KEEP-SKU',
            'name' => 'Variant 1',
            'price' => 10000,
        ]);

        $this->actingAs($this->admin);

        $response = $this->putJson("/vi/admin/products/{$product->id}", [
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'name' => 'Prod 1 Updated',
            'price' => 12000,
            'is_active' => true,
            'variants' => [
                ['id' => $variant->id, 'sku' => 'KEEP-SKU', 'price' => 11000],
            ]
        ]);

        $response->assertRedirect();
    }

    public function test_prevents_variant_id_from_belonging_to_another_product(): void
    {
        $product1 = Product::query()->create([
            'category_id' => $this->category->id,
            'name' => 'Prod 1',
            'slug' => 'prod-1',
            'price' => 10000,
            'is_active' => true,
        ]);
        $variant1 = ProductVariant::query()->create([
            'product_id' => $product1->id,
            'sku' => 'SKU-A',
            'name' => 'Variant A',
            'price' => 10000,
        ]);

        $product2 = Product::query()->create([
            'category_id' => $this->category->id,
            'name' => 'Prod 2',
            'slug' => 'prod-2',
            'price' => 10000,
            'is_active' => true,
        ]);

        $this->actingAs($this->admin);

        $response = $this->putJson("/vi/admin/products/{$product2->id}", [
            'category_id' => $this->category->id,
            'brand_id' => $this->brand->id,
            'name' => 'Prod 2 Updated',
            'price' => 15000,
            'is_active' => true,
            'variants' => [
                ['id' => $variant1->id, 'sku' => 'SKU-B', 'price' => 15000],
            ]
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['variants.0.id']);
    }
}
