<?php

namespace Tests\Feature;

use App\Models\Role;
use App\Models\User;
use App\Services\CloudinaryService;
use App\Services\HomeLayoutService;
use App\Support\JwtService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Mockery\MockInterface;
use Tests\TestCase;

class HomeBuilderTest extends TestCase
{
    use RefreshDatabase;

    public function test_public_home_layout_returns_semantic_section_document(): void
    {
        $response = $this->getJson('/api/pages/home');

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.page_key', 'home')
            ->assertJsonCount(8, 'data.layout.sections')
            ->assertJsonPath('data.layout.sections.0.id', 'hero');
    }

    public function test_draft_is_private_until_it_is_published(): void
    {
        $headers = $this->adminHeaders();
        $draft = $this->withHeaders($headers)
            ->getJson('/api/admin/pages/home/draft')
            ->assertOk()
            ->json('data');

        $layout = $draft['draft'];
        $layout['sections'][0]['props']['title'] = 'Tiêu đề bản nháp';

        $saved = $this->withHeaders($headers)
            ->putJson('/api/admin/pages/home/draft', [
                'layout' => $layout,
                'revision' => $draft['draft_revision'],
            ])
            ->assertOk()
            ->assertJsonPath('data.has_unpublished_changes', true)
            ->json('data');

        $this->getJson('/api/pages/home')
            ->assertOk()
            ->assertJsonMissing(['title' => 'Tiêu đề bản nháp']);

        $this->withHeaders($headers)
            ->postJson('/api/admin/pages/home/publish', [
                'revision' => $saved['draft_revision'],
            ])
            ->assertOk()
            ->assertJsonPath('data.has_unpublished_changes', false);

        $this->getJson('/api/pages/home')
            ->assertOk()
            ->assertJsonPath('data.layout.sections.0.props.title', 'Tiêu đề bản nháp');
    }

    public function test_stale_revision_cannot_overwrite_a_newer_draft(): void
    {
        $headers = $this->adminHeaders();
        $draft = $this->withHeaders($headers)
            ->getJson('/api/admin/pages/home/draft')
            ->json('data');

        $this->withHeaders($headers)
            ->putJson('/api/admin/pages/home/draft', [
                'layout' => $draft['draft'],
                'revision' => $draft['draft_revision'],
            ])
            ->assertOk();

        $this->withHeaders($headers)
            ->putJson('/api/admin/pages/home/draft', [
                'layout' => $draft['draft'],
                'revision' => $draft['draft_revision'],
            ])
            ->assertStatus(409);
    }

    public function test_layout_normalizer_rejects_unknown_sections_and_fields(): void
    {
        $service = app(HomeLayoutService::class);
        $layout = $service->defaultLayout();
        $layout['sections'][0]['unsafe_html'] = '<script>alert(1)</script>';
        $layout['sections'][] = [
            'id' => 'arbitrary-html',
            'type' => 'html',
            'enabled' => true,
            'props' => ['html' => '<script>alert(1)</script>'],
        ];

        $normalized = $service->normalize($layout);

        $this->assertCount(8, $normalized['sections']);
        $this->assertArrayNotHasKey('unsafe_html', $normalized['sections'][0]);
        $this->assertNotContains('arbitrary-html', array_column($normalized['sections'], 'id'));
    }

    public function test_layout_normalizer_keeps_each_testimonial_item_even_when_content_is_duplicated(): void
    {
        $service = app(HomeLayoutService::class);
        $layout = $service->defaultLayout();
        $sectionIndex = array_search('testimonials', array_column($layout['sections'], 'id'), true);
        $firstReview = $layout['sections'][$sectionIndex]['props']['items'][0];
        $layout['sections'][$sectionIndex]['props']['items'][] = $firstReview;

        $normalized = $service->normalize($layout);
        $items = $normalized['sections'][$sectionIndex]['props']['items'];

        $this->assertCount(4, $items);
        $this->assertSame($firstReview, $items[3]);
    }

    public function test_storefront_editor_context_is_hidden_for_guests(): void
    {
        $this->getJson('/storefront/editor-context')
            ->assertOk()
            ->assertJsonPath('authenticated', false)
            ->assertJsonPath('can_edit_home', false)
            ->assertJsonPath('builder_url', null);
    }

    public function test_storefront_editor_context_exposes_builder_to_authorized_admin(): void
    {
        $response = $this->actingAs($this->adminUser(['manage_settings'], 'Editor'))
            ->getJson('/storefront/editor-context')
            ->assertOk()
            ->assertJsonPath('authenticated', true)
            ->assertJsonPath('can_edit_home', true);

        $this->assertStringEndsWith('/vi/admin/home-builder', (string) $response->json('builder_url'));
        $this->assertStringContainsString('no-store', (string) $response->headers->get('Cache-Control'));
    }

    public function test_storefront_editor_context_does_not_expose_builder_without_permission(): void
    {
        $this->actingAs($this->adminUser([], 'Viewer'))
            ->getJson('/storefront/editor-context')
            ->assertOk()
            ->assertJsonPath('authenticated', true)
            ->assertJsonPath('can_edit_home', false)
            ->assertJsonPath('builder_url', null);
    }

    public function test_authorized_editor_can_browse_existing_local_media(): void
    {
        Storage::fake('public');
        Storage::disk('public')->put('home-builder/existing-image.png', 'image-content');
        Storage::disk('public')->put('home-builder/readme.txt', 'not-an-image');

        $this->actingAs($this->adminUser(['manage_settings'], 'Media editor'))
            ->getJson(route('admin.home-builder.media.index', ['locale' => 'vi']))
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.cloudinary_configured', false)
            ->assertJsonPath('data.preferred_storage', 'local')
            ->assertJsonCount(1, 'data.items')
            ->assertJsonPath('data.items.0.name', 'existing-image.png')
            ->assertJsonPath('data.items.0.storage', 'local');
    }

    public function test_authorized_editor_can_upload_media_to_local_fallback(): void
    {
        Storage::fake('public');

        $response = $this->actingAs($this->adminUser(['manage_settings'], 'Media uploader'))
            ->postJson(route('admin.home-builder.media', ['locale' => 'vi']), [
                'file' => UploadedFile::fake()->image('new-hero.jpg', 1200, 800)->size(512),
            ])
            ->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('data.name', 'new-hero.jpg')
            ->assertJsonPath('data.storage', 'local');

        $this->assertStringContainsString('/storage/home-builder/', (string) $response->json('data.url'));
        $this->assertCount(1, Storage::disk('public')->allFiles('home-builder'));
    }

    public function test_media_library_reports_cloudinary_as_preferred_when_configured(): void
    {
        $this->mock(CloudinaryService::class, function (MockInterface $mock) {
            $mock->shouldReceive('isConfigured')->once()->andReturnTrue();
            $mock->shouldReceive('listResources')->once()->with('all')->andReturn([
                [
                    'secure_url' => 'https://res.cloudinary.com/demo/image/upload/home-builder/cloud-image.webp',
                    'public_id' => 'home-builder/cloud-image',
                    'bytes' => 4096,
                    'created_at' => '2026-07-15T00:00:00Z',
                    'format' => 'webp',
                    'storage' => 'cloudinary',
                ],
            ]);
        });

        $this->actingAs($this->adminUser(['manage_settings'], 'Cloud media editor'))
            ->getJson(route('admin.home-builder.media.index', ['locale' => 'vi']))
            ->assertOk()
            ->assertJsonPath('data.cloudinary_configured', true)
            ->assertJsonPath('data.preferred_storage', 'cloudinary')
            ->assertJsonPath('data.items.0.name', 'cloud-image')
            ->assertJsonPath('data.items.0.storage', 'cloudinary');
    }

    private function adminHeaders(): array
    {
        $user = $this->adminUser();

        return [
            'Authorization' => 'Bearer '.JwtService::generateToken($user),
            'Accept' => 'application/json',
        ];
    }

    private function adminUser(array $permissions = ['*'], string $roleName = 'Superadmin'): User
    {
        $role = Role::query()->create([
            'name' => $roleName,
            'permissions' => $permissions,
        ]);

        return User::factory()->create([
            'role_id' => $role->id,
            'is_active' => true,
        ]);
    }

    public function test_layout_normalizer_allows_custom_blocks(): void
    {
        $service = app(HomeLayoutService::class);
        $layout = [
            'sections' => [
                [
                    'id' => 'custom_video_1',
                    'type' => 'custom_video',
                    'name' => 'Video giới thiệu sản phẩm',
                    'enabled' => true,
                    'props' => [
                        'title' => 'Xem cách chế tác của chúng tôi',
                        'description' => 'Video ghi lại toàn bộ quy trình mài và khắc pha lê 3D',
                        'video_url' => 'https://www.youtube.com/watch?v=dQw4w9WgXcQ',
                        'unsafe_prop' => 'dangerous val'
                    ]
                ],
                [
                    'id' => 'custom_text_2',
                    'type' => 'custom_text_block',
                    'name' => 'Mô tả triết lý',
                    'enabled' => true,
                    'props' => [
                        'title' => 'Triết lý kinh doanh',
                        'content' => 'Chúng tôi tin vào <script>alert(1)</script>sự <strong>chân thành</strong>.',
                        'align' => 'left'
                    ]
                ],
                [
                    'id' => 'custom_banner_3',
                    'type' => 'custom_image_banner',
                    'name' => 'Banner Giảm Giá',
                    'enabled' => false,
                    'props' => [
                        'image_url' => 'public/images/banner.jpg',
                        'link_url' => '/collection?promo=1',
                        'alt_text' => 'Khuyến mãi 2026',
                        'overlay_enabled' => true,
                        'height' => '500px'
                    ]
                ]
            ]
        ];

        $normalized = $service->normalize($layout);
        $sections = collect($normalized['sections'])->keyBy('id');

        $this->assertTrue($sections->has('custom_video_1'));
        $this->assertEquals('custom_video', $sections->get('custom_video_1')['type']);
        $this->assertEquals('https://www.youtube.com/embed/dQw4w9WgXcQ', $sections->get('custom_video_1')['props']['video_url']);
        $this->assertArrayNotHasKey('unsafe_prop', $sections->get('custom_video_1')['props']);

        $this->assertTrue($sections->has('custom_text_2'));
        $this->assertEquals('Chúng tôi tin vào sự chân thành.', $sections->get('custom_text_2')['props']['content']);

        $this->assertTrue($sections->has('custom_banner_3'));
        $this->assertFalse($sections->get('custom_banner_3')['enabled']);
        $this->assertTrue($sections->get('custom_banner_3')['props']['overlay_enabled']);
        $this->assertEquals('500px', $sections->get('custom_banner_3')['props']['height']);
    }

    public function test_safe_video_url_parser(): void
    {
        $service = app(HomeLayoutService::class);
        
        $reflector = new \ReflectionClass(HomeLayoutService::class);
        $method = $reflector->getMethod('parseSafeVideoUrl');
        $method->setAccessible(true);

        $this->assertEquals('https://www.youtube.com/embed/8x87TxOHXmo', $method->invoke($service, 'https://www.youtube.com/watch?v=8x87TxOHXmo'));
        $this->assertEquals('https://www.youtube.com/embed/8x87TxOHXmo', $method->invoke($service, 'https://youtu.be/8x87TxOHXmo'));
        $this->assertEquals('https://www.youtube.com/embed/8x87TxOHXmo', $method->invoke($service, 'https://www.youtube.com/embed/8x87TxOHXmo'));
        
        $this->assertEquals('https://player.vimeo.com/video/123456', $method->invoke($service, 'https://vimeo.com/123456'));
        $this->assertEquals('https://player.vimeo.com/video/123456', $method->invoke($service, 'https://player.vimeo.com/video/123456'));

        $this->assertEquals('', $method->invoke($service, 'https://unsafe-url.com/video.mp4'));
    }

    public function test_collections_section_props_normalization(): void
    {
        $service = app(HomeLayoutService::class);
        $layout = [
            'sections' => [
                [
                    'id' => 'collections',
                    'type' => 'product_collection',
                    'props' => [
                        'product_source' => 'manual',
                        'product_ids' => ['10', '15', '22'],
                        'product_limit' => 12,
                        'category_id' => '5',
                    ]
                ]
            ]
        ];

        $normalized = $service->normalize($layout);
        $collectionsSection = collect($normalized['sections'])->firstWhere('id', 'collections');

        $this->assertNotNull($collectionsSection);
        $this->assertEquals('manual', $collectionsSection['props']['product_source']);
        $this->assertEquals([10, 15, 22], $collectionsSection['props']['product_ids']);
        $this->assertEquals(12, $collectionsSection['props']['product_limit']);
        $this->assertEquals(5, $collectionsSection['props']['category_id']);
    }
}
