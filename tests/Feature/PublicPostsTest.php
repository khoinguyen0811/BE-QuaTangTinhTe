<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PublicPostsTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_post_index_returns_seeded_crystal_content(): void
    {
        $this->getJson('/api/posts?limit=48&include_content=1')
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonCount(6, 'data')
            ->assertJsonPath('meta.total', 6)
            ->assertJsonPath('data.0.slug', 'cach-chon-dang-pha-le-3d-phu-hop-tung-dip-tang')
            ->assertJsonPath('data.0.in_language', 'vi-VN')
            ->assertJsonPath('data.0.word_count', fn ($count) => is_int($count) && $count > 0)
            ->assertJsonPath('data.0.content', fn ($content) => str_contains($content, '<h2>Chọn hình khối theo thông điệp</h2>'));
    }

    public function test_public_post_categories_include_post_counts(): void
    {
        $this->getJson('/api/post-categories')
            ->assertOk()
            ->assertJsonCount(6, 'data')
            ->assertJsonPath('data.0.slug', 'huong-dan-chon-qua')
            ->assertJsonPath('data.0.posts_count', 1);
    }

    public function test_public_post_detail_contains_semantic_article_content(): void
    {
        $this->getJson('/api/posts/huong-dan-chuan-bi-anh-de-khac-3d-ro-net-nhat')
            ->assertOk()
            ->assertJsonPath('data.category.slug', 'ca-nhan-hoa-3d')
            ->assertJsonPath('data.title', 'Hướng dẫn chuẩn bị ảnh để khắc 3D rõ nét nhất')
            ->assertJsonPath('data.content', fn ($content) => str_contains($content, '<h2>Tiêu chí của một ảnh phù hợp</h2>'));
    }
}
