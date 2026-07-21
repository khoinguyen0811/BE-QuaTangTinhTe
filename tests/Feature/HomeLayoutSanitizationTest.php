<?php

namespace Tests\Feature;

use App\Services\HomeLayoutService;
use Tests\TestCase;

class HomeLayoutSanitizationTest extends TestCase
{
    public function test_custom_text_block_is_sanitized_to_plain_text(): void
    {
        $service = app(HomeLayoutService::class);

        $layout = [
            'sections' => [
                [
                    'id' => 'custom_text_1',
                    'type' => 'custom_text_block',
                    'name' => 'Custom text block',
                    'enabled' => true,
                    'props' => [
                        'title' => 'Title 1',
                        'align' => 'center',
                        'content' => 'Hello <script>alert(1)</script>World! <a href="javascript:alert(1)">Click</a> <strong>Important</strong>',
                    ]
                ]
            ]
        ];

        $normalized = $service->normalize($layout);

        $content = $normalized['sections'][0]['props']['content'];
        
        $this->assertStringNotContainsString('<script>', $content);
        $this->assertStringNotContainsString('<strong>', $content);
        $this->assertStringNotContainsString('href', $content);
        $this->assertEquals('Hello World! Click Important', $content);
    }
}
