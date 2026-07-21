<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SeoDiscoveryTest extends TestCase
{
    use RefreshDatabase;

    public function test_sitemap_lists_published_articles_with_last_modified_dates(): void
    {
        $response = $this->get('/sitemap.xml');

        $response->assertOk()
            ->assertHeader('Content-Type', 'application/xml; charset=UTF-8')
            ->assertSee('/posts/cach-chon-dang-pha-le-3d-phu-hop-tung-dip-tang/', false)
            ->assertSee('<lastmod>', false)
            ->assertDontSee('/admin', false);
    }

    public function test_llms_index_lists_current_published_articles(): void
    {
        $this->get('/llms.txt')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/plain; charset=UTF-8')
            ->assertSee('# Quà Tặng Tinh Tế', false)
            ->assertSee('/posts/cach-chon-dang-pha-le-3d-phu-hop-tung-dip-tang/', false);
    }

    public function test_llms_full_includes_semantic_article_content(): void
    {
        $this->get('/llms-full.txt')
            ->assertOk()
            ->assertSee('## Cách chọn dáng pha lê 3D phù hợp từng dịp tặng', false)
            ->assertSee('Chuyên mục:', false);
    }
}
