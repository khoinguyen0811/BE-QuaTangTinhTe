<?php

namespace Tests\Feature;

use App\Models\CustomPage;
use App\Models\Role;
use App\Models\User;
use HansSchouten\LaravelPageBuilder\Models\PageBuilderPage;
use HansSchouten\LaravelPageBuilder\Models\PageBuilderPageTranslation;
use HansSchouten\LaravelPageBuilder\Models\PageBuilderPageRevision;
use App\Services\PageBuilderRenderService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;
use Tests\TestCase;

class PageBuilderModuleTest extends TestCase
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

        // Default enable the visual page builder feature for testing
        config(['features.visual_page_builder_enabled' => true]);
    }

    /*
    ==========================================================================
    1. Isolation Tests
    ==========================================================================
    */

    public function test_guest_blocked_from_pagebuilder_dashboard(): void
    {
        $response = $this->get('/vi/admin/page-builder-lab');
        $response->assertRedirect();
    }

    public function test_non_admin_blocked_from_pagebuilder_dashboard(): void
    {
        $customer = User::factory()->create(['role_id' => null]);
        $this->actingAs($customer);

        $response = $this->get('/vi/admin/page-builder-lab');
        $response->assertStatus(403);
    }

    public function test_admin_can_access_pagebuilder_dashboard(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->get('/vi/admin/page-builder-lab');
        $response->assertStatus(200);
        $response->assertSee('Page Builder Lab Dashboard');
    }

    public function test_feature_flag_blocks_dashboard_when_disabled(): void
    {
        config(['features.visual_page_builder_enabled' => false]);
        $this->actingAs($this->adminUser);

        $response = $this->get('/vi/admin/page-builder-lab');
        $response->assertStatus(403);
        $response->assertSee('Lab Mode');
    }

    public function test_feature_flag_allows_dashboard_when_enabled(): void
    {
        config(['features.visual_page_builder_enabled' => true]);
        $this->actingAs($this->adminUser);

        $response = $this->get('/vi/admin/page-builder-lab');
        $response->assertStatus(200);
    }

    /*
    ==========================================================================
    2. RESTful CRUD & Policy Authorization Tests
    ==========================================================================
    */

    public function test_admin_can_create_visual_page(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->post('/vi/admin/page-builder-lab/pages', [
            'title' => 'Trang Liên Hệ Mới',
            'slug' => 'lien-he-moi',
            'seo_title' => 'SEO Liên Hệ',
            'seo_description' => 'Mô tả SEO',
            'is_active' => '1',
        ]);

        $response->assertRedirect('/vi/admin/page-builder-lab/pages');

        // Check CustomPage DB
        $this->assertDatabaseHas('custom_pages', [
            'slug' => 'lien-he-moi',
            'builder_driver' => 'laravel-pagebuilder',
            'is_active' => true,
        ]);

        $page = CustomPage::where('slug', 'lien-he-moi')->firstOrFail();
        $this->assertNotNull($page->builder_page_id);

        // Check PageBuilderPage DB
        $this->assertDatabaseHas('pagebuilder_pages', [
            'id' => $page->builder_page_id,
            'name' => 'Trang Liên Hệ Mới',
        ]);

        // Check PageBuilderPageTranslation DB
        $this->assertDatabaseHas('pagebuilder_page_translations', [
            'page_id' => $page->builder_page_id,
            'locale' => 'vi',
            'title' => 'Trang Liên Hệ Mới',
            'route' => 'lien-he-moi',
        ]);
    }

    public function test_duplicate_slug_validation_on_store(): void
    {
        $this->actingAs($this->adminUser);

        // First page
        $this->post('/vi/admin/page-builder-lab/pages', [
            'title' => 'Trang A',
            'slug' => 'trang-a',
        ]);

        // Second page with duplicate slug
        $response = $this->post('/vi/admin/page-builder-lab/pages', [
            'title' => 'Trang B',
            'slug' => 'trang-a',
        ]);

        $response->assertSessionHasErrors(['slug']);
    }

    public function test_reserved_slugs_fail_validation(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->post('/vi/admin/page-builder-lab/pages', [
            'title' => 'Admin Page',
            'slug' => 'admin',
        ]);

        $response->assertSessionHasErrors(['slug']);
    }

    public function test_admin_can_soft_delete_page(): void
    {
        $this->actingAs($this->adminUser);

        $builderPage = PageBuilderPage::create([
            'name' => 'Trang Test Xóa',
            'layout' => 'full-width',
            'data' => '{}',
        ]);

        $page = CustomPage::create([
            'title' => 'Trang Test Xóa',
            'slug' => 'test-xoa',
            'builder_driver' => 'laravel-pagebuilder',
            'builder_page_id' => $builderPage->id,
            'created_by' => $this->adminUser->id,
            'updated_by' => $this->adminUser->id,
        ]);

        $response = $this->delete("/vi/admin/page-builder-lab/pages/{$page->id}");
        $response->assertRedirect('/vi/admin/page-builder-lab/pages');

        $page->refresh();
        $this->assertNotNull($page->deleted_at);
        $this->assertStringContainsString('__deleted__', $page->slug);
    }

    public function test_admin_can_restore_soft_deleted_page(): void
    {
        $this->actingAs($this->adminUser);

        $builderPage = PageBuilderPage::create([
            'name' => 'Trang Phục Hồi',
            'layout' => 'full-width',
            'data' => '{}',
        ]);

        $page = CustomPage::create([
            'title' => 'Trang Phục Hồi',
            'slug' => 'phuc-hoi__deleted__12345',
            'builder_driver' => 'laravel-pagebuilder',
            'builder_page_id' => $builderPage->id,
            'created_by' => $this->adminUser->id,
            'updated_by' => $this->adminUser->id,
        ]);
        $page->delete();

        $response = $this->post("/vi/admin/page-builder-lab/pages/{$page->id}/restore");
        $response->assertRedirect('/vi/admin/page-builder-lab/pages');

        $page->refresh();
        $this->assertNull($page->deleted_at);
        $this->assertEquals('phuc-hoi', $page->slug);
    }

    /*
    ==========================================================================
    3. Draft & Published Snapshot Logic Tests
    ==========================================================================
    */

    public function test_editor_autosave_updates_draft_data(): void
    {
        $this->actingAs($this->adminUser);

        $builderPage = PageBuilderPage::create([
            'name' => 'Draft Page',
            'layout' => 'full-width',
            'data' => '{}',
        ]);

        $page = CustomPage::create([
            'title' => 'Draft Page',
            'slug' => 'draft-page',
            'builder_driver' => 'laravel-pagebuilder',
            'builder_page_id' => $builderPage->id,
            'lock_version' => 1,
            'created_by' => $this->adminUser->id,
            'updated_by' => $this->adminUser->id,
        ]);

        $payload = [
            'html' => '<div class="test">Hello World</div>',
            'css' => '.test { color: red; }',
            'components' => [],
            'styles' => [],
            'blocks' => [],
            'style' => [],
        ];

        $response = $this->post("/vi/admin/page-builder-lab/editor?action=store&page={$builderPage->id}&lock_version=1", [
            'data' => json_encode($payload)
        ]);

        $response->assertStatus(200);
        $response->assertJson([
            'success' => true,
            'new_lock_version' => 2
        ]);

        $builderPage->refresh();
        $this->assertEquals('<div class="test">Hello World</div>', $builderPage->draft_html);
        $this->assertEquals('.test { color: red; }', $builderPage->draft_css);
    }

    public function test_concurrency_lock_version_mismatch_on_autosave(): void
    {
        $this->actingAs($this->adminUser);

        $builderPage = PageBuilderPage::create([
            'name' => 'Lock Page',
            'layout' => 'full-width',
            'data' => '{}',
        ]);

        $page = new CustomPage([
            'title' => 'Lock Page',
            'slug' => 'lock-page',
            'builder_driver' => 'laravel-pagebuilder',
            'builder_page_id' => $builderPage->id,
            'created_by' => $this->adminUser->id,
            'updated_by' => $this->adminUser->id,
        ]);
        $page->lock_version = 2; // Bypasses fillable
        $page->save();

        // Client sends version 1 (mismatch)
        $response = $this->post("/vi/admin/page-builder-lab/editor?action=store&page={$builderPage->id}&lock_version=1", [
            'data' => json_encode(['html' => 'x', 'css' => 'y'])
        ]);

        $response->assertStatus(409);
        $response->assertJsonStructure(['message']);
    }

    public function test_publish_creates_immutable_revision_snapshot(): void
    {
        $this->actingAs($this->adminUser);

        $builderPage = PageBuilderPage::create([
            'name' => 'Publish Page',
            'layout' => 'full-width',
            'data' => '{"some":"grapesjs-json"}',
            'draft_html' => '<div class="published">Final HTML</div>',
            'draft_css' => '.published { font-size: 14px; }',
            'current_revision' => 0,
        ]);

        $page = CustomPage::create([
            'title' => 'Publish Page',
            'slug' => 'publish-page',
            'builder_driver' => 'laravel-pagebuilder',
            'builder_page_id' => $builderPage->id,
            'created_by' => $this->adminUser->id,
            'updated_by' => $this->adminUser->id,
        ]);

        $response = $this->post("/vi/admin/page-builder-lab/pages/{$page->id}/publish");
        $response->assertStatus(200);

        // Check revision DB
        $this->assertDatabaseHas('pagebuilder_page_revisions', [
            'page_id' => $builderPage->id,
            'revision' => 1,
            'project_json' => '{"some":"grapesjs-json"}',
            'html' => '<div class="published">Final HTML</div>',
            'css' => '.published { font-size: 14px; }',
        ]);

        // Check CustomPage updated references
        $page->refresh();
        $this->assertNotNull($page->published_at);
        
        $publishedData = json_decode($page->layout_published, true);
        $this->assertEquals(1, $publishedData['revision']);
        $this->assertEquals('<div class="published">Final HTML</div>', $publishedData['html']);
    }

    public function test_unpublish_removes_published_layout(): void
    {
        $this->actingAs($this->adminUser);

        $builderPage = PageBuilderPage::create([
            'name' => 'Unpublish Page',
            'layout' => 'full-width',
            'data' => '{}',
        ]);

        $page = CustomPage::create([
            'title' => 'Unpublish Page',
            'slug' => 'unpublish-page',
            'builder_driver' => 'laravel-pagebuilder',
            'builder_page_id' => $builderPage->id,
            'layout_published' => json_encode(['html' => 'abc', 'css' => '']),
            'published_at' => now(),
            'created_by' => $this->adminUser->id,
            'updated_by' => $this->adminUser->id,
        ]);

        $response = $this->post("/vi/admin/page-builder-lab/pages/{$page->id}/unpublish");
        $response->assertStatus(200);

        $page->refresh();
        $this->assertNull($page->layout_published);
    }

    /*
    ==========================================================================
    4. Storefront Rendering & Security Tests
    ==========================================================================
    */

    public function test_storefront_renders_published_grapesjs_layout(): void
    {
        $builderPage = PageBuilderPage::create([
            'name' => 'Public Page',
            'layout' => 'full-width',
            'data' => '{}',
        ]);

        $page = CustomPage::create([
            'title' => 'Public Page',
            'slug' => 'public-page',
            'builder_driver' => 'laravel-pagebuilder',
            'builder_page_id' => $builderPage->id,
            'layout_published' => json_encode([
                'html' => '<div class="grapes-storefront">Storefront OK</div>',
                'css' => '.grapes-storefront { color: blue; }',
            ]),
            'published_at' => now(),
            'is_active' => true,
        ]);

        $response = $this->get('/pages/public-page');
        $response->assertStatus(200);
        $response->assertSee('Storefront OK');
        $response->assertSee('.grapes-storefront { color: blue; }');
    }

    public function test_storefront_pagebuilder_renderer_sanitizes_xss(): void
    {
        $dirtyHtml = '<div class="content" onclick="alert(1)">Hello <script>alert("xss")</script><a href="javascript:alert(2)">click me</a></div>';
        
        $builderPage = PageBuilderPage::create([
            'name' => 'Dirty Page',
            'layout' => 'full-width',
            'data' => '{}',
        ]);

        $page = CustomPage::create([
            'title' => 'Dirty Page',
            'slug' => 'dirty-page',
            'builder_driver' => 'laravel-pagebuilder',
            'builder_page_id' => $builderPage->id,
            'layout_published' => json_encode([
                'html' => $dirtyHtml,
                'css' => '',
            ]),
            'published_at' => now(),
            'is_active' => true,
        ]);

        $response = $this->get('/pages/dirty-page');
        $response->assertStatus(200);
        
        // Assert script tag and inline/javascript attributes are stripped
        $response->assertDontSee('<script>');
        $response->assertDontSee('onclick="alert(1)"');
        $response->assertDontSee('href="javascript:alert(2)"');
        
        // Structure should remain
        $response->assertSee('<div class="content">Hello <a>click me</a></div>', false);
    }

    public function test_preview_signed_url_security_and_no_store_headers(): void
    {
        $this->actingAs($this->adminUser);

        $builderPage = PageBuilderPage::create([
            'name' => 'Preview Page',
            'layout' => 'full-width',
            'data' => '{}',
            'draft_html' => '<div class="preview-mode">Draft Preview</div>',
            'draft_css' => '',
        ]);

        $page = CustomPage::create([
            'title' => 'Preview Page',
            'slug' => 'preview-page',
            'builder_driver' => 'laravel-pagebuilder',
            'builder_page_id' => $builderPage->id,
            'created_by' => $this->adminUser->id,
            'updated_by' => $this->adminUser->id,
        ]);

        // 1. Direct access without valid signature must fail (403)
        $unsignedResponse = $this->get("/vi/admin/page-builder-lab/pages/{$page->id}/preview");
        $unsignedResponse->assertStatus(403);

        // 2. Access with valid Signed URL must succeed
        $signedUrl = URL::signedRoute('pagebuilder.pages.preview', [
            'locale' => 'vi',
            'page' => $page->id
        ]);

        $signedResponse = $this->get($signedUrl);
        $signedResponse->assertStatus(200);
        $signedResponse->assertSee('Draft Preview');
        
        // 3. Verify Cache-Control headers contain no-store and no-cache
        $cacheControl = $signedResponse->headers->get('Cache-Control');
        $this->assertStringContainsString('no-store', $cacheControl);
        $this->assertStringContainsString('no-cache', $cacheControl);
    }

    public function test_default_package_routes_are_not_registered(): void
    {
        // 1. Default website manager dashboard URL should not exist (404)
        $this->get('/admin/pagebuilder')->assertStatus(404);
        $this->get('/vi/admin/page-builder')->assertStatus(404);
        $this->get('/pagebuilder')->assertStatus(404);
    }

    public function test_granular_permissions_authorization(): void
    {
        // Create users with granular roles
        $viewRole = Role::create(['name' => 'Viewer', 'permissions' => ['custom_pages.view']]);
        $viewUser = User::factory()->create(['role_id' => $viewRole->id]);

        $updateRole = Role::create(['name' => 'Editor', 'permissions' => ['custom_pages.update']]);
        $updateUser = User::factory()->create(['role_id' => $updateRole->id]);

        $publishRole = Role::create(['name' => 'Publisher', 'permissions' => ['custom_pages.publish']]);
        $publishUser = User::factory()->create(['role_id' => $publishRole->id]);

        $builderPage = PageBuilderPage::create(['name' => 'Granular Test', 'layout' => 'full-width', 'data' => '{}']);
        $page = CustomPage::create([
            'title' => 'Granular Test',
            'slug' => 'granular-test',
            'builder_driver' => 'laravel-pagebuilder',
            'builder_page_id' => $builderPage->id,
            'created_by' => $this->adminUser->id,
            'updated_by' => $this->adminUser->id,
        ]);

        // A. View user can access list but not create/edit/delete/publish
        $this->actingAs($viewUser);
        $this->get('/vi/admin/page-builder-lab/pages')->assertStatus(200);
        $this->get('/vi/admin/page-builder-lab/pages/create')->assertStatus(403);
        $this->post('/vi/admin/page-builder-lab/pages', ['title' => 'X', 'slug' => 'x'])->assertStatus(403);
        $this->get("/vi/admin/page-builder-lab/pages/{$page->id}/edit")->assertStatus(403);
        $this->get("/vi/admin/page-builder-lab/pages/{$page->id}/builder")->assertStatus(403);
        $this->post("/vi/admin/page-builder-lab/pages/{$page->id}/publish")->assertStatus(403);
        $this->delete("/vi/admin/page-builder-lab/pages/{$page->id}")->assertStatus(403);

        // B. Update user can view settings & open editor, but cannot publish or delete
        $this->actingAs($updateUser);
        $this->get("/vi/admin/page-builder-lab/pages/{$page->id}/edit")->assertStatus(200);
        $this->post("/vi/admin/page-builder-lab/pages/{$page->id}/publish")->assertStatus(403);
        $this->delete("/vi/admin/page-builder-lab/pages/{$page->id}")->assertStatus(403);

        // C. Publish user can publish/unpublish but cannot update page settings or edit draft
        $this->actingAs($publishUser);
        $this->post("/vi/admin/page-builder-lab/pages/{$page->id}/publish")->assertStatus(200);
        $this->post("/vi/admin/page-builder-lab/pages/{$page->id}/unpublish")->assertStatus(200);
        $this->get("/vi/admin/page-builder-lab/pages/{$page->id}/edit")->assertStatus(403);
    }

    public function test_publish_and_unpublish_concurrency_conflict(): void
    {
        $this->actingAs($this->adminUser);

        $builderPage = PageBuilderPage::create([
            'name' => 'Publish Conflict',
            'layout' => 'full-width',
            'data' => '{}',
            'draft_html' => '<p>X</p>',
            'draft_css' => ''
        ]);

        $page = new CustomPage([
            'title' => 'Publish Conflict',
            'slug' => 'publish-conflict',
            'builder_driver' => 'laravel-pagebuilder',
            'builder_page_id' => $builderPage->id,
            'created_by' => $this->adminUser->id,
            'updated_by' => $this->adminUser->id,
        ]);
        $page->lock_version = 5;
        $page->save();

        // 1. Publish with stale version must fail (409)
        $response = $this->post("/vi/admin/page-builder-lab/pages/{$page->id}/publish?lock_version=4");
        $response->assertStatus(409);

        // 2. Publish with correct version must succeed and increment lock_version
        $response2 = $this->post("/vi/admin/page-builder-lab/pages/{$page->id}/publish?lock_version=5");
        $response2->assertStatus(200);
        $page->refresh();
        $this->assertEquals(6, $page->lock_version);

        // 3. Unpublish with stale version must fail (409)
        $response3 = $this->post("/vi/admin/page-builder-lab/pages/{$page->id}/unpublish?lock_version=5");
        $response3->assertStatus(409);

        // 4. Unpublish with correct version must succeed and increment
        $response4 = $this->post("/vi/admin/page-builder-lab/pages/{$page->id}/unpublish?lock_version=6");
        $response4->assertStatus(200);
        $page->refresh();
        $this->assertEquals(7, $page->lock_version);
    }

    public function test_published_revision_remains_immutable(): void
    {
        $this->actingAs($this->adminUser);

        $builderPage = PageBuilderPage::create([
            'name' => 'Immutable Test',
            'layout' => 'full-width',
            'data' => '{"version":"A"}',
            'draft_html' => '<div class="content">Revision A</div>',
            'draft_css' => '',
            'current_revision' => 0,
        ]);

        $page = new CustomPage([
            'title' => 'Immutable Test',
            'slug' => 'immutable-test',
            'builder_driver' => 'laravel-pagebuilder',
            'builder_page_id' => $builderPage->id,
            'created_by' => $this->adminUser->id,
            'updated_by' => $this->adminUser->id,
            'is_active' => true,
        ]);
        $page->lock_version = 1;
        $page->save();

        // 1. Publish Revision A
        $this->post("/vi/admin/page-builder-lab/pages/{$page->id}/publish?lock_version=1")->assertStatus(200);
        $page->refresh();
        $this->assertNotNull($page->layout_published);
        
        // Storefront must show Revision A
        $response = $this->get('/pages/immutable-test');
        $response->assertStatus(200)->assertSee('Revision A');

        // 2. Edit draft (autosave) new content B
        $payload = [
            'html' => '<div class="content">Revision B</div>',
            'css' => '',
            'components' => [],
            'styles' => [],
            'blocks' => [],
            'style' => [],
        ];
        $this->post("/vi/admin/page-builder-lab/editor?action=store&page={$builderPage->id}&lock_version=2", [
            'data' => json_encode($payload)
        ])->assertStatus(200);

        // Storefront must STILL display Revision A because draft hasn't been published!
        $response2 = $this->get('/pages/immutable-test');
        $response2->assertStatus(200)
            ->assertSee('Revision A')
            ->assertDontSee('Revision B');

        // 3. Publish Revision B
        $this->post("/vi/admin/page-builder-lab/pages/{$page->id}/publish?lock_version=3")->assertStatus(200);

        // Storefront must now display Revision B
        $response3 = $this->get('/pages/immutable-test');
        $response3->assertStatus(200)
            ->assertSee('Revision B')
            ->assertDontSee('Revision A');

        // 4. Verify Revision 1 (Revision A) is still stored in revisions table
        $this->assertDatabaseHas('pagebuilder_page_revisions', [
            'page_id' => $builderPage->id,
            'revision' => 1,
            'html' => '<div class="content">Revision A</div>'
        ]);
    }

    public function test_linked_builder_page_deletion_is_blocked(): void
    {
        $builderPage = PageBuilderPage::create([
            'name' => 'Linked Delete Test',
            'layout' => 'full-width',
            'data' => '{}',
        ]);

        $page = CustomPage::create([
            'title' => 'Linked Delete Test',
            'slug' => 'linked-delete-test',
            'builder_driver' => 'laravel-pagebuilder',
            'builder_page_id' => $builderPage->id,
            'created_by' => $this->adminUser->id,
            'updated_by' => $this->adminUser->id,
        ]);

        $this->expectException(\Exception::class);
        $this->expectExceptionMessage('Cannot delete builder page while linked to a custom page.');

        $builderPage->delete();
    }

    public function test_missing_builder_page_auto_provisions_and_succeeds(): void
    {
        $this->actingAs($this->adminUser);

        $builderPage = PageBuilderPage::create([
            'name' => 'Missing Builder',
            'layout' => 'full-width',
            'data' => '{}',
        ]);

        $page = CustomPage::create([
            'title' => 'Missing Builder',
            'slug' => 'missing-builder',
            'builder_driver' => 'laravel-pagebuilder',
            'builder_page_id' => $builderPage->id,
            'created_by' => $this->adminUser->id,
            'updated_by' => $this->adminUser->id,
        ]);

        // Delete the builder page record directly from DB to bypass model constraints and create dangling pointer
        DB::table('pagebuilder_pages')->where('id', $builderPage->id)->delete();

        $response = $this->get("/vi/admin/page-builder-lab/pages/{$page->id}/builder");
        $response->assertStatus(200);

        $page->refresh();
        $this->assertNotNull($page->builder_page_id);
        $this->assertDatabaseHas('pagebuilder_pages', ['id' => $page->builder_page_id]);
    }
}
