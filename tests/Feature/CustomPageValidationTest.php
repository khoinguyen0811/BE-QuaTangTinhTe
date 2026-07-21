<?php

namespace Tests\Feature;

use App\Models\CustomPage;
use App\Models\Role;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomPageValidationTest extends TestCase
{
    use RefreshDatabase;

    private Role $adminRole;
    private User $adminUser;
    private CustomPage $page;

    protected function setUp(): void
    {
        parent::setUp();

        $this->adminRole = Role::query()->create([
            'name' => 'System administrator',
            'permissions' => ['*'],
        ]);

        $this->adminUser = User::factory()->create([
            'role_id' => $this->adminRole->id,
        ]);

        $this->page = CustomPage::query()->create([
            'title' => 'Chính sách',
            'slug' => 'chinh-sach',
            'is_active' => true,
            'lock_version' => 1,
        ]);
    }

    public function test_layout_validates_maximum_block_count_threshold(): void
    {
        $this->actingAs($this->adminUser);

        // Generate 31 rich_text blocks (max allowed is 30)
        $blocks = [];
        for ($i = 0; $i < 31; $i++) {
            $blocks[] = [
                'id' => 'block_' . $i,
                'type' => 'rich_text',
                'version' => 1,
                'enabled' => true,
                'settings' => ['title' => 'Block ' . $i],
            ];
        }

        $response = $this->putJson("/vi/admin/custom-pages/{$this->page->id}/layout", [
            'layout' => [
                'schema_version' => 1,
                'blocks' => $blocks,
            ],
            'lock_version' => 1,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('layout');
    }

    public function test_layout_validates_maximum_faq_items_threshold(): void
    {
        $this->actingAs($this->adminUser);

        // Generate FAQ block with 16 accordion items (max allowed is 15)
        $faqItems = [];
        for ($i = 0; $i < 16; $i++) {
            $faqItems[] = [
                'question' => 'Câu hỏi ' . $i,
                'answer' => '<p>Trả lời ' . $i . '</p>',
            ];
        }

        $response = $this->putJson("/vi/admin/custom-pages/{$this->page->id}/layout", [
            'layout' => [
                'schema_version' => 1,
                'blocks' => [
                    [
                        'id' => 'faq1',
                        'type' => 'faq',
                        'version' => 1,
                        'enabled' => true,
                        'settings' => [
                            'title' => 'Hỏi đáp',
                            'items' => $faqItems,
                        ]
                    ]
                ],
            ],
            'lock_version' => 1,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('layout.blocks.0.settings.items');
    }

    public function test_layout_validates_maximum_feature_columns_threshold(): void
    {
        $this->actingAs($this->adminUser);

        // Generate Feature Columns block with 9 items (max allowed is 8)
        $featureItems = [];
        for ($i = 0; $i < 9; $i++) {
            $featureItems[] = [
                'title' => 'Tính năng ' . $i,
                'description' => 'Mô tả tính năng ' . $i,
            ];
        }

        $response = $this->putJson("/vi/admin/custom-pages/{$this->page->id}/layout", [
            'layout' => [
                'schema_version' => 1,
                'blocks' => [
                    [
                        'id' => 'feat1',
                        'type' => 'feature_columns',
                        'version' => 1,
                        'enabled' => true,
                        'settings' => [
                            'title' => 'Tính năng',
                            'items' => $featureItems,
                        ]
                    ]
                ],
            ],
            'lock_version' => 1,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('layout.blocks.0.settings.items');
    }

    public function test_layout_sanitizes_malicious_xss_scripts_and_events(): void
    {
        $this->actingAs($this->adminUser);

        $payload = [
            'schema_version' => 1,
            'blocks' => [
                [
                    'id' => 'b1',
                    'type' => 'rich_text',
                    'version' => 1,
                    'enabled' => true,
                    'settings' => [
                        'title' => 'Tiêu đề sạch',
                        // Malicious markup
                        'content' => '<div>Chào mừng <script>alert("XSS")</script> <img src="x" onload="alert(1)"> <a href="javascript:alert(2)">Nhấp vào đây</a></div>',
                    ],
                ]
            ],
        ];

        $response = $this->putJson("/vi/admin/custom-pages/{$this->page->id}/layout", [
            'layout' => $payload,
            'lock_version' => 1,
        ]);

        $response->assertOk();

        // Verify the database contains sanitized content
        $this->page->refresh();
        $savedContent = $this->page->layout_draft['blocks'][0]['settings']['content'];

        $this->assertStringNotContainsString('<script>', $savedContent);
        $this->assertStringNotContainsString('onload', $savedContent);
        $this->assertStringNotContainsString('javascript:', $savedContent);
        $this->assertStringContainsString('Chào mừng', html_entity_decode($savedContent, ENT_QUOTES, 'UTF-8'));
    }

    public function test_contact_form_recipient_group_id_validation(): void
    {
        $this->actingAs($this->adminUser);

        // Invalid recipient_group_id type (should be integer, boolean or other string is invalid)
        $response = $this->putJson("/vi/admin/custom-pages/{$this->page->id}/layout", [
            'layout' => [
                'schema_version' => 1,
                'blocks' => [
                    [
                        'id' => 'cf1',
                        'type' => 'contact_form',
                        'version' => 1,
                        'enabled' => true,
                        'settings' => [
                            'title' => 'Form',
                            'recipient_group_id' => 'invalid_group_string', // Invalid
                        ]
                    ]
                ],
            ],
            'lock_version' => 1,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('layout.blocks.0.settings.recipient_group_id');
    }

    public function test_admin_cannot_create_page_with_reserved_slug(): void
    {
        $this->actingAs($this->adminUser);

        // Try creating a custom page with slug 'admin'
        $response = $this->postJson('/vi/admin/custom-pages', [
            'title' => 'Admin Page',
            'slug' => 'admin',
            'is_active' => true,
        ]);

        $response->assertStatus(302)
            ->assertSessionHasErrors('slug');

        // Try creating a custom page with slug 'api'
        $response = $this->postJson('/vi/admin/custom-pages', [
            'title' => 'API Page',
            'slug' => 'api',
            'is_active' => true,
        ]);

        $response->assertStatus(302)
            ->assertSessionHasErrors('slug');
    }

    public function test_layout_validates_json_size_limit_1_mb(): void
    {
        $this->actingAs($this->adminUser);

        // Generate a huge payload (>1MB)
        $hugeString = str_repeat('A', 1.1 * 1024 * 1024);

        $response = $this->putJson("/vi/admin/custom-pages/{$this->page->id}/layout", [
            'layout' => [
                'schema_version' => 1,
                'blocks' => [
                    [
                        'id' => 'huge1',
                        'type' => 'rich_text',
                        'version' => 1,
                        'enabled' => true,
                        'settings' => [
                            'title' => 'Huge Block',
                            'content' => $hugeString,
                        ]
                    ]
                ],
            ],
            'lock_version' => 1,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('layout');
    }

    public function test_layout_validates_rich_text_length_limit_50_000_chars(): void
    {
        $this->actingAs($this->adminUser);

        // Generate content with 51,000 characters
        $largeString = str_repeat('A', 51000);

        $response = $this->putJson("/vi/admin/custom-pages/{$this->page->id}/layout", [
            'layout' => [
                'schema_version' => 1,
                'blocks' => [
                    [
                        'id' => 'rt1',
                        'type' => 'rich_text',
                        'version' => 1,
                        'enabled' => true,
                        'settings' => [
                            'title' => 'Large Rich Text',
                            'content' => $largeString,
                        ]
                    ]
                ],
            ],
            'lock_version' => 1,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('layout.blocks.0.settings.content');
    }

    public function test_layout_validates_duplicate_block_ids(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->putJson("/vi/admin/custom-pages/{$this->page->id}/layout", [
            'layout' => [
                'schema_version' => 1,
                'blocks' => [
                    [
                        'id' => 'dup-id',
                        'type' => 'rich_text',
                        'version' => 1,
                        'enabled' => true,
                        'settings' => ['title' => 'Block 1']
                    ],
                    [
                        'id' => 'dup-id',
                        'type' => 'rich_text',
                        'version' => 1,
                        'enabled' => true,
                        'settings' => ['title' => 'Block 2']
                    ]
                ],
            ],
            'lock_version' => 1,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('layout.blocks.1.id');
    }

    public function test_layout_validates_unsupported_block_type(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->putJson("/vi/admin/custom-pages/{$this->page->id}/layout", [
            'layout' => [
                'schema_version' => 1,
                'blocks' => [
                    [
                        'id' => 'unsupported1',
                        'type' => 'hero', // Home Page Builder block type, not supported in Custom Page Builder
                        'version' => 1,
                        'enabled' => true,
                        'settings' => ['title' => 'Hero']
                    ]
                ],
            ],
            'lock_version' => 1,
        ]);

        $response->assertStatus(422)
            ->assertJsonValidationErrors('layout.blocks.0.type');
    }

    public function test_layout_rejects_home_builder_block_types(): void
    {
        $this->actingAs($this->adminUser);

        $homeBlockTypes = ['hero', 'services', 'applications', 'rd_steps', 'interactive_accordion'];

        foreach ($homeBlockTypes as $type) {
            $response = $this->putJson("/vi/admin/custom-pages/{$this->page->id}/layout", [
                'layout' => [
                    'schema_version' => 1,
                    'blocks' => [
                        [
                            'id' => 'home_' . $type,
                            'type' => $type,
                            'version' => 1,
                            'enabled' => true,
                            'settings' => ['title' => 'Test ' . $type]
                        ]
                    ],
                ],
                'lock_version' => 1,
            ]);

            $response->assertStatus(422)
                ->assertJsonValidationErrors('layout.blocks.0.type');
        }
    }

    public function test_layout_allows_table_tags_with_colspan_and_rowspan(): void
    {
        $this->actingAs($this->adminUser);

        $response = $this->putJson("/vi/admin/custom-pages/{$this->page->id}/layout", [
            'layout' => [
                'schema_version' => 1,
                'blocks' => [
                    [
                        'id' => 'rt_table',
                        'type' => 'rich_text',
                        'version' => 1,
                        'enabled' => true,
                        'settings' => [
                            'title' => 'Table test',
                            'content' => '<table><thead><tr><th colspan="2">Header</th></tr></thead><tbody><tr><td rowspan="2">Cell 1</td><td>Cell 2</td></tr></tbody></table>'
                        ]
                    ]
                ],
            ],
            'lock_version' => 1,
        ]);

        $response->assertOk();
        $this->page->refresh();
        $savedContent = $this->page->layout_draft['blocks'][0]['settings']['content'];
        $this->assertStringContainsString('<table', $savedContent);
        $this->assertStringContainsString('colspan="2"', $savedContent);
        $this->assertStringContainsString('rowspan="2"', $savedContent);
    }

    public function test_layout_allows_and_validates_data_text_align(): void
    {
        $this->actingAs($this->adminUser);

        // Valid data-text-align center and invalid value 'center-large'
        $response = $this->putJson("/vi/admin/custom-pages/{$this->page->id}/layout", [
            'layout' => [
                'schema_version' => 1,
                'blocks' => [
                    [
                        'id' => 'rt_align',
                        'type' => 'rich_text',
                        'version' => 1,
                        'enabled' => true,
                        'settings' => [
                            'title' => 'Alignment test',
                            'content' => '<p data-text-align="center">Center</p><h2 data-text-align="invalid-align">Invalid</h2>'
                        ]
                    ]
                ],
            ],
            'lock_version' => 1,
        ]);

        $response->assertOk();
        $this->page->refresh();
        $savedContent = $this->page->layout_draft['blocks'][0]['settings']['content'];
        $this->assertStringContainsString('data-text-align="center"', $savedContent);
        // Invalid text-align should be stripped from h2
        $this->assertStringNotContainsString('data-text-align="invalid-align"', $savedContent);
    }

    public function test_layout_validates_and_syncs_media_library_images(): void
    {
        $this->actingAs($this->adminUser);

        // Mock Cloudinary listResources to return one valid image public_id
        $mock = $this->createMock(\App\Services\CloudinaryService::class);
        $mock->method('listResources')->willReturn([
            [
                'public_id' => 'general/valid-image.png',
                'secure_url' => 'https://res.cloudinary.com/valid-image.png',
                'storage' => 'cloudinary',
            ]
        ]);
        $this->app->instance(\App\Services\CloudinaryService::class, $mock);

        // 1. Image with valid data-media-id should be allowed and synced
        $response = $this->putJson("/vi/admin/custom-pages/{$this->page->id}/layout", [
            'layout' => [
                'schema_version' => 1,
                'blocks' => [
                    [
                        'id' => 'rt_image_valid',
                        'type' => 'rich_text',
                        'version' => 1,
                        'enabled' => true,
                        'settings' => [
                            'title' => 'Image Valid',
                            'content' => '<img src="https://attacker.com/malicious.png" data-media-id="general/valid-image.png" alt="Test image">'
                        ]
                    ]
                ],
            ],
            'lock_version' => 1,
        ]);

        $response->assertOk();
        $this->page->refresh();
        $savedContent = $this->page->layout_draft['blocks'][0]['settings']['content'];
        // URL must be replaced with official secure_url
        $this->assertStringContainsString('src="https://res.cloudinary.com/valid-image.png"', $savedContent);

        // 2. Image with invalid data-media-id should throw validation error
        $responseErr = $this->putJson("/vi/admin/custom-pages/{$this->page->id}/layout", [
            'layout' => [
                'schema_version' => 1,
                'blocks' => [
                    [
                        'id' => 'rt_image_invalid',
                        'type' => 'rich_text',
                        'version' => 1,
                        'enabled' => true,
                        'settings' => [
                            'title' => 'Image Invalid',
                            'content' => '<img src="https://attacker.com/malicious.png" data-media-id="general/invalid-image.png" alt="Test image">'
                        ]
                    ]
                ],
            ],
            'lock_version' => 2, // lock_version increased due to previous update
        ]);

        $responseErr->assertStatus(422)
            ->assertJsonValidationErrors('layout.blocks.0.settings.content');
    }
}
