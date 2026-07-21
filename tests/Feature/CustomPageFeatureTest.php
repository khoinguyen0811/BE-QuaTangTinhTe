<?php

namespace Tests\Feature;

use App\Models\CustomPage;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class CustomPageFeatureTest extends TestCase
{
    use RefreshDatabase;

    private Role $adminRole;
    private User $adminUser;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminRole = Role::query()->create([
            'name' => 'System admin',
            'permissions' => ['*'],
        ]);

        $this->adminUser = User::factory()->create([
            'role_id' => $this->adminRole->id,
        ]);
    }

    public function test_guests_and_non_admins_cannot_manage_custom_pages(): void
    {
        $response = $this->get('/vi/admin/custom-pages');
        $response->assertRedirect(); // Admin middleware redirects guests

        $customer = User::factory()->create(['role_id' => null]);
        $this->actingAs($customer);

        $response = $this->get('/vi/admin/custom-pages');
        $response->assertStatus(403);
    }

    public function test_admin_can_perform_custom_page_crud_lifecycle(): void
    {
        $this->actingAs($this->adminUser);

        // 1. Create Custom Page
        $storeResponse = $this->post('/vi/admin/custom-pages', [
            'title' => 'Về Chúng Tôi',
            'slug' => 've-chung-toi',
            'seo_title' => 'Mcrystal - Về Chúng Tôi',
            'seo_description' => 'Tìm hiểu câu chuyện thương hiệu của Mcrystal.',
            'seo_image' => 'https://res.cloudinary.com/demo.jpg',
            'is_active' => '1',
        ]);

        $storeResponse->assertRedirect('/vi/admin/page-builder-lab/pages');
        $this->assertDatabaseHas('custom_pages', [
            'slug' => 've-chung-toi',
            'title' => 'Về Chúng Tôi',
            'lock_version' => 1,
        ]);

        $page = CustomPage::where('slug', 've-chung-toi')->firstOrFail();

        // 2. Edit Custom Page Info
        $updateResponse = $this->put("/vi/admin/custom-pages/{$page->id}", [
            'title' => 'Về Chúng Tôi Mcrystal',
            'slug' => 've-chung-toi-mcrystal',
            'seo_title' => 'Về Chúng Tôi Mcrystal',
            'seo_description' => 'Tìm hiểu câu chuyện thương hiệu của Mcrystal.',
            'seo_image' => 'https://res.cloudinary.com/demo.jpg',
            'is_active' => '1',
        ]);

        $updateResponse->assertRedirect('/vi/admin/custom-pages');
        $this->assertDatabaseHas('custom_pages', [
            'id' => $page->id,
            'slug' => 've-chung-toi-mcrystal',
            'title' => 'Về Chúng Tôi Mcrystal',
        ]);

        // 3. Soft Delete and Slug Renaming
        $deleteResponse = $this->delete("/vi/admin/custom-pages/{$page->id}");
        $deleteResponse->assertRedirect('/vi/admin/custom-pages');

        $page->refresh();
        $this->assertNotNull($page->deleted_at);
        $this->assertStringContainsString('__deleted__', $page->slug);

        // Try creating again with same slug - should succeed since old slug was renamed
        $secondStoreResponse = $this->post('/vi/admin/custom-pages', [
            'title' => 'Về Chúng Tôi',
            'slug' => 've-chung-toi',
            'is_active' => '1',
        ]);
        $secondStoreResponse->assertRedirect('/vi/admin/page-builder-lab/pages');
    }

    public function test_layout_autosave_and_atomic_lock_concurrency(): void
    {
        $this->actingAs($this->adminUser);

        $page = CustomPage::query()->create([
            'title' => 'Chính Sách Bảo Mật',
            'slug' => 'chinh-sach-bao-mat',
            'is_active' => true,
            'lock_version' => 1,
        ]);

        // 1. Initial draft layout retrieval
        $this->get("/vi/admin/custom-pages/{$page->id}/draft")
            ->assertOk()
            ->assertJsonPath('data.lock_version', 1);

        // 2. Successful Autosave
        $layoutData = [
            'schema_version' => 1,
            'blocks' => [
                [
                    'id' => 'b1',
                    'type' => 'rich_text',
                    'version' => 1,
                    'enabled' => true,
                    'settings' => [
                        'title' => 'Chính sách bảo mật',
                        'content' => '<p>MCrystal cam kết bảo mật thông tin.</p>',
                    ],
                ]
            ],
        ];

        $saveResponse = $this->putJson("/vi/admin/custom-pages/{$page->id}/layout", [
            'layout' => $layoutData,
            'lock_version' => 1,
        ]);

        $saveResponse->assertOk()
            ->assertJsonPath('data.lock_version', 2);

        // 3. Stale Lock Version Conflict (Concurrency detection)
        $conflictResponse = $this->putJson("/vi/admin/custom-pages/{$page->id}/layout", [
            'layout' => $layoutData,
            'lock_version' => 1, // Sending stale lock_version
        ]);

        $conflictResponse->assertStatus(409); // Conflict HTTP status
    }

    public function test_publish_and_unpublish_cycles_with_cache_clearing(): void
    {
        $this->actingAs($this->adminUser);

        $page = CustomPage::query()->create([
            'title' => 'Chính Sách Thanh Toán',
            'slug' => 'chinh-sach-thanh-toan',
            'is_active' => true,
            'lock_version' => 1,
            'layout_draft' => ['schema_version' => 1, 'blocks' => []],
        ]);

        // Set mock data in cache
        Cache::put("custom_page:data:{$page->slug}", 'cached_data');

        // Publish
        $this->postJson("/vi/admin/custom-pages/{$page->id}/publish", [
            'lock_version' => 1,
        ])->assertOk();

        $page->refresh();
        $this->assertNotNull($page->layout_published);
        $this->assertNotNull($page->published_at);

        // Cache should be forgotten post-commit
        $this->assertFalse(Cache::has("custom_page:data:{$page->slug}"));

        // Unpublish
        Cache::put("custom_page:data:{$page->slug}", 'cached_data');
        $this->postJson("/vi/admin/custom-pages/{$page->id}/unpublish", [
            'lock_version' => $page->lock_version,
        ])->assertOk();

        $page->refresh();
        $this->assertNull($page->layout_published);
        $this->assertNull($page->published_at);
        $this->assertFalse(Cache::has("custom_page:data:{$page->slug}"));
    }

    public function test_signed_preview_url_authentication_and_headers(): void
    {
        $page = CustomPage::query()->create([
            'title' => 'Trang Preview',
            'slug' => 'trang-preview',
            'is_active' => true,
            'lock_version' => 1,
            'layout_draft' => [
                'schema_version' => 1,
                'blocks' => [
                    ['id' => '1', 'type' => 'rich_text', 'enabled' => true, 'settings' => ['title' => 'Bản nháp']]
                ]
            ],
        ]);

        $previewUrl = URL::signedRoute('admin.custom-pages.preview', ['locale' => 'vi', 'customPage' => $page]);

        // 1. Guest request with valid signature
        $response = $this->get($previewUrl);
        $response->assertOk()
            ->assertHeader('X-Robots-Tag', 'noindex, nofollow')
            ->assertSee('Bản nháp')
            ->assertSee('noindex, nofollow');

        $this->assertStringContainsString('no-store', $response->headers->get('Cache-Control'));

        // 2. Guest request with invalid signature
        $invalidUrl = $previewUrl . 'modified';
        $this->get($invalidUrl)->assertStatus(403);
    }

    public function test_publish_concurrency_conflict(): void
    {
        $this->actingAs($this->adminUser);

        $page = CustomPage::query()->create([
            'title' => 'Concurrency Page',
            'slug' => 'concurrency-page',
            'is_active' => true,
            'lock_version' => 1,
            'layout_draft' => ['schema_version' => 1, 'blocks' => []],
        ]);

        // Attempt publish with stale version
        $response = $this->postJson("/vi/admin/custom-pages/{$page->id}/publish", [
            'lock_version' => 0, // Stale
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('lock_version');
    }

    public function test_soft_delete_and_restore_slug_resolution(): void
    {
        $this->actingAs($this->adminUser);

        $page1 = CustomPage::query()->create([
            'title' => 'Original Page',
            'slug' => 'original-slug',
            'is_active' => true,
            'lock_version' => 1,
        ]);

        // 1. Delete page -> slug is freed (renamed to oldSlug__deleted__timestamp)
        $this->deleteJson("/vi/admin/custom-pages/{$page1->id}")->assertRedirect();
        
        $page1->refresh();
        $this->assertSoftDeleted($page1);
        $this->assertStringContainsString('original-slug__deleted__', $page1->slug);

        // 2. Create another page with the same slug 'original-slug' (should succeed because the first one is deleted)
        $page2 = CustomPage::query()->create([
            'title' => 'New Page with same slug',
            'slug' => 'original-slug',
            'is_active' => true,
            'lock_version' => 1,
        ]);

        // 3. Restore the first page when its slug is already occupied (should append timestamp)
        $this->postJson("/vi/admin/custom-pages/{$page1->id}/restore")->assertRedirect();

        $page1->refresh();
        $this->assertFalse($page1->trashed());
        $this->assertStringContainsString('original-slug-', $page1->slug);
    }

    public function test_storefront_dynamic_routing(): void
    {
        // 1. Access unpublished page - should return 404
        $page = CustomPage::query()->create([
            'title' => 'Trang Chưa Xuất Bản',
            'slug' => 'chua-xuat-ban',
            'is_active' => true,
            'lock_version' => 1,
            'layout_draft' => [
                'schema_version' => 1,
                'blocks' => [
                    ['id' => 'd1', 'type' => 'rich_text', 'enabled' => true, 'settings' => ['title' => 'Bản Nháp Nhạy Cảm', 'content' => '<p>Không được lộ diện.</p>']]
                ]
            ],
        ]);

        $this->get('/pages/chua-xuat-ban')->assertStatus(404);

        // 2. Access published active page - should return 200 with layout elements
        $page->update([
            'layout_published' => [
                'schema_version' => 1,
                'blocks' => [
                    ['id' => 'b1', 'type' => 'rich_text', 'enabled' => true, 'settings' => ['title' => 'Chính sách mua hàng', 'content' => '<p>Thỏa thuận giao dịch.</p>']],
                    ['id' => 'b2', 'type' => 'rich_text', 'enabled' => false, 'settings' => ['title' => 'Khối bị ẩn', 'content' => '<p>Không được hiển thị.</p>']],
                    ['id' => 'b3', 'type' => 'hero', 'enabled' => true, 'settings' => ['title' => 'Khối không được hỗ trợ']], // Should be ignored safely
                    ['id' => 'b4', 'type' => 'rich_text', 'enabled' => true, 'settings' => 'invalid-settings-string'] // Throws rendering error, should be caught
                ]
            ],
            'published_at' => now(),
        ]);

        $response = $this->get('/pages/chua-xuat-ban');
        $response->assertOk()
            ->assertSee('Chính sách mua hàng')
            ->assertSee('Thỏa thuận giao dịch.')
            ->assertSee('site-header')
            ->assertSee('site-footer');

        // Disabled block must not be displayed
        $response->assertDontSee('Khối bị ẩn');
        $response->assertDontSee('Không được hiển thị.');
        // Unsupported block must be ignored without causing 500
        $response->assertDontSee('Khối không được hỗ trợ');

        // 3. Storefront must absolutely not display draft content
        $response->assertDontSee('Bản Nháp Nhạy Cảm');
        $response->assertDontSee('Không được lộ diện.');

        // 4. Access inactive published page - should return 404
        $page->update(['is_active' => false]);
        Cache::forget("custom_page:data:{$page->slug}");
        $this->get('/pages/chua-xuat-ban')->assertStatus(404);

        // 5. Access soft-deleted published page - should return 404
        $page->update(['is_active' => true]);
        Cache::forget("custom_page:data:{$page->slug}");
        $page->delete();
        Cache::forget("custom_page:data:{$page->slug}");
        $this->get('/pages/chua-xuat-ban')->assertStatus(404);

        // 6. Access non-existent page slug - should return 404
        $this->get('/pages/does-not-exist-slug')->assertStatus(404);

        // 7. Verify slug cache clearing and routing on update
        $page->restore();
        $page->update(['is_active' => true]);
        Cache::forget("custom_page:data:{$page->slug}");

        $this->actingAs($this->adminUser);
        
        // Cache the slug
        $this->get('/pages/chua-xuat-ban')->assertOk();
        $this->assertTrue(Cache::has("custom_page:data:chua-xuat-ban"));

        // Update slug
        $updateResponse = $this->put("/vi/admin/custom-pages/{$page->id}", [
            'title' => 'Chính sách mua hàng mới',
            'slug' => 'chinh-sach-moi',
            'is_active' => '1',
        ]);
        $updateResponse->assertRedirect('/vi/admin/custom-pages');

        // Old cache is cleared, old route returns 404
        $this->assertFalse(Cache::has("custom_page:data:chua-xuat-ban"));
        $this->get('/pages/chua-xuat-ban')->assertStatus(404);

        // New route renders correctly
        $this->get('/pages/chinh-sach-moi')->assertOk();
    }

    public function test_custom_page_policy_permissions(): void
    {
        // Setup Roles and Users
        $editorRole = Role::query()->create([
            'name' => 'Editor Role',
            'permissions' => ['manage_custom_pages'], // Can CRUD, but no publish
        ]);
        $editor = User::factory()->create(['role_id' => $editorRole->id]);

        $publisherRole = Role::query()->create([
            'name' => 'Publisher Role',
            'permissions' => ['publish_custom_pages'], // Can publish, but no CRUD
        ]);
        $publisher = User::factory()->create(['role_id' => $publisherRole->id]);

        $superAdminRole = Role::query()->create([
            'name' => 'Superadmin',
            'permissions' => [], // Implicit '*' bypass
        ]);
        $superadmin = User::factory()->create(['role_id' => $superAdminRole->id]);

        $page = CustomPage::query()->create([
            'title' => 'Policy Test',
            'slug' => 'policy-test',
            'is_active' => true,
        ]);

        // Editor permissions
        $this->assertTrue($editor->can('viewAny', CustomPage::class));
        $this->assertTrue($editor->can('create', CustomPage::class));
        $this->assertTrue($editor->can('update', $page));
        $this->assertTrue($editor->can('delete', $page));
        $this->assertTrue($editor->can('restore', $page));
        $this->assertFalse($editor->can('publish', $page));

        // Publisher permissions
        $this->assertFalse($publisher->can('viewAny', CustomPage::class));
        $this->assertFalse($publisher->can('create', CustomPage::class));
        $this->assertFalse($publisher->can('update', $page));
        $this->assertFalse($publisher->can('delete', $page));
        $this->assertFalse($publisher->can('restore', $page));
        $this->assertTrue($publisher->can('publish', $page));

        // Superadmin permissions
        $this->assertTrue($superadmin->can('viewAny', CustomPage::class));
        $this->assertTrue($superadmin->can('create', CustomPage::class));
        $this->assertTrue($superadmin->can('update', $page));
        $this->assertTrue($superadmin->can('delete', $page));
        $this->assertTrue($superadmin->can('restore', $page));
        $this->assertTrue($superadmin->can('publish', $page));
    }

    public function test_unpublish_concurrency_conflict(): void
    {
        $this->actingAs($this->adminUser);

        $page = CustomPage::query()->create([
            'title' => 'Concurrency Unpublish Page',
            'slug' => 'concurrency-unpublish-page',
            'is_active' => true,
            'lock_version' => 1,
            'layout_draft' => ['schema_version' => 1, 'blocks' => []],
            'layout_published' => ['schema_version' => 1, 'blocks' => []],
            'published_at' => now(),
        ]);

        // Attempt unpublish with stale version
        $response = $this->postJson("/vi/admin/custom-pages/{$page->id}/unpublish", [
            'lock_version' => 0, // Stale
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('lock_version');
    }

    public function test_custom_page_audit_logs(): void
    {
        $this->actingAs($this->adminUser);

        \Illuminate\Support\Facades\Log::shouldReceive('error')
            ->byDefault()
            ->andReturnUsing(function ($msg, $ctx = []) {
                dump("LOG ERROR: " . $msg);
                if (isset($ctx['exception'])) {
                    dump($ctx['exception']->getMessage());
                }
            });

        \Illuminate\Support\Facades\Log::shouldReceive('info')
            ->once()
            ->with('custom_page.created', \Mockery::on(function ($context) {
                return $context['slug'] === 'audit-page' &&
                    $context['actor_id'] === $this->adminUser->id &&
                    isset($context['page_id'], $context['lock_version'], $context['timestamp']);
            }));

        \Illuminate\Support\Facades\Log::shouldReceive('info')
            ->once()
            ->with('custom_page.updated', \Mockery::on(function ($context) {
                return $context['old_slug'] === 'audit-page' &&
                    $context['new_slug'] === 'audit-page-modified' &&
                    $context['actor_id'] === $this->adminUser->id &&
                    isset($context['page_id'], $context['lock_version'], $context['timestamp']);
            }));

        \Illuminate\Support\Facades\Log::shouldReceive('info')
            ->once()
            ->with('custom_page.deleted', \Mockery::on(function ($context) {
                return str_contains($context['slug'], 'audit-page-modified') &&
                    $context['actor_id'] === $this->adminUser->id &&
                    isset($context['page_id'], $context['lock_version'], $context['timestamp']);
            }));

        \Illuminate\Support\Facades\Log::shouldReceive('info')
            ->once()
            ->with('custom_page.restored', \Mockery::on(function ($context) {
                return str_contains($context['original_slug'], 'audit-page-modified') &&
                    $context['actor_id'] === $this->adminUser->id &&
                    isset($context['page_id'], $context['restored_slug'], $context['lock_version'], $context['timestamp']);
            }));

        \Illuminate\Support\Facades\Log::shouldReceive('info')
            ->once()
            ->with('custom_page.published', \Mockery::on(function ($context) {
                return $context['actor_id'] === $this->adminUser->id &&
                    isset($context['page_id'], $context['slug'], $context['lock_version'], $context['timestamp']);
            }));

        \Illuminate\Support\Facades\Log::shouldReceive('info')
            ->once()
            ->with('custom_page.unpublished', \Mockery::on(function ($context) {
                return $context['actor_id'] === $this->adminUser->id &&
                    isset($context['page_id'], $context['slug'], $context['lock_version'], $context['timestamp']);
            }));

        // 1. Create (custom_page.created)
        $storeResponse = $this->post('/vi/admin/custom-pages', [
            'title' => 'Audit Page',
            'slug' => 'audit-page',
            'is_active' => '1',
        ]);
        $storeResponse->assertRedirect('/vi/admin/page-builder-lab/pages');
        $page = CustomPage::where('slug', 'audit-page')->firstOrFail();

        // 2. Update (custom_page.updated)
        $updateResponse = $this->put("/vi/admin/custom-pages/{$page->id}", [
            'title' => 'Audit Page Modified',
            'slug' => 'audit-page-modified',
            'is_active' => '1',
        ]);
        $updateResponse->assertRedirect('/vi/admin/custom-pages');

        // 3. Delete (custom_page.deleted)
        $deleteResponse = $this->delete("/vi/admin/custom-pages/{$page->id}");
        $deleteResponse->assertRedirect('/vi/admin/custom-pages');

        // 4. Restore (custom_page.restored)
        $restoreResponse = $this->post("/vi/admin/custom-pages/{$page->id}/restore");
        $restoreResponse->assertRedirect('/vi/admin/custom-pages');

        // 5. Publish (custom_page.published)
        $page->refresh();
        $publishResponse = $this->postJson("/vi/admin/custom-pages/{$page->id}/publish", [
            'lock_version' => $page->lock_version,
        ]);
        $publishResponse->assertOk();

        // 6. Unpublish (custom_page.unpublished)
        $page->refresh();
        $unpublishResponse = $this->postJson("/vi/admin/custom-pages/{$page->id}/unpublish", [
            'lock_version' => $page->lock_version,
        ]);
        $unpublishResponse->assertOk();
    }
}
