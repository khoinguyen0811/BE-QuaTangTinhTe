<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VariantConcurrencyTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Product $product;
    private ProductVariant $variant;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create();
        
        $category = Category::query()->create([
            'name' => 'Dien thoai',
            'slug' => 'dien-thoai',
            'is_active' => true,
        ]);
        $this->product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'iPhone',
            'slug' => 'iphone',
            'price' => 1000,
            'is_active' => true,
        ]);
        $this->variant = ProductVariant::query()->create([
            'product_id' => $this->product->id,
            'sku' => 'IPHONE-VAR',
            'name' => 'Gold',
            'price' => 1100,
        ]);
    }

    public function test_matching_timestamp_allows_update(): void
    {
        $this->actingAs($this->admin);

        $response = $this->putJson("/vi/admin/products/{$this->product->id}/variants/{$this->variant->id}", [
            'name' => 'Silver',
            'sku' => 'IPHONE-VAR',
            'price' => 1150,
            'updated_at' => $this->variant->updated_at->format('Y-m-d\TH:i:s.uP'),
        ]);

        $response->assertRedirect();
        $this->variant->refresh();
        $this->assertEquals('Silver', $this->variant->name);
    }

    public function test_stale_timestamp_returns_409_conflict(): void
    {
        $this->actingAs($this->admin);

        $response = $this->putJson("/vi/admin/products/{$this->product->id}/variants/{$this->variant->id}", [
            'name' => 'Bronze',
            'sku' => 'IPHONE-VAR',
            'price' => 1150,
            'updated_at' => '2026-01-01T00:00:00.000000+00:00',
        ]);

        $response->assertStatus(409);
    }
}
