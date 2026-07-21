<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CategoryDeletionTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Category $defaultCategory;

    protected function setUp(): void
    {
        parent::setUp();
        $this->admin = User::factory()->create();
        $this->defaultCategory = Category::query()->firstOrCreate(
            ['slug' => 'chua-phan-loai'],
            [
                'name' => ['vi' => 'Chưa phân loại', 'en' => 'Uncategorized'],
                'is_active' => true,
                'is_system' => true,
            ]
        );
    }

    public function test_cannot_delete_system_category(): void
    {
        $this->actingAs($this->admin);

        $response = $this->delete("/vi/admin/categories/{$this->defaultCategory->id}");
        $response->assertSessionHas('error', 'Không thể xóa danh mục hệ thống.');
        $this->assertTrue(Category::query()->where('id', $this->defaultCategory->id)->exists());
    }

    public function test_deleting_category_reassigns_orphaned_products_to_default(): void
    {
        $category = Category::query()->create([
            'name' => 'Tech',
            'slug' => 'tech',
            'is_active' => true,
        ]);

        $product = Product::query()->create([
            'category_id' => $category->id,
            'name' => 'iPhone',
            'slug' => 'iphone',
            'price' => 1000,
            'is_active' => true,
        ]);

        $this->actingAs($this->admin);
        
        $response = $this->delete("/vi/admin/categories/{$category->id}");
        $response->assertRedirect();
        
        $product->refresh();
        $this->assertEquals($this->defaultCategory->id, $product->category_id);
        $this->assertTrue($product->categories()->where('categories.id', $this->defaultCategory->id)->exists());
    }

    public function test_deleting_secondary_category_reassigns_to_remaining_primary(): void
    {
        $cat1 = Category::query()->create(['name' => 'Phones', 'slug' => 'phones', 'is_active' => true]);
        $cat2 = Category::query()->create(['name' => 'Accessories', 'slug' => 'accessories', 'is_active' => true]);

        $product = Product::query()->create([
            'category_id' => $cat1->id,
            'name' => 'Case',
            'slug' => 'case',
            'price' => 10,
            'is_active' => true,
        ]);
        $product->categories()->attach($cat2->id);

        $this->actingAs($this->admin);

        $response = $this->delete("/vi/admin/categories/{$cat2->id}");
        $response->assertRedirect();

        $product->refresh();
        $this->assertEquals($cat1->id, $product->category_id);
        $this->assertFalse($product->categories()->where('categories.id', $cat2->id)->exists());
        $this->assertTrue($product->categories()->where('categories.id', $cat1->id)->exists());
    }
}
