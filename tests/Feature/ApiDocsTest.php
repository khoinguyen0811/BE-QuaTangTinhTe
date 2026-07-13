<?php

namespace Tests\Feature;

use Tests\TestCase;

class ApiDocsTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config(['app.key' => 'base64:MTIzNDU2Nzg5MDEyMzQ1Njc4OTAxMjM0NTY3ODkwMTI=']);
    }

    public function test_can_access_api_documentation_page(): void
    {
        $response = $this->get('/api/docs');

        $response->assertStatus(200);
        $response->assertSee('/api/docs/openapi.json');
    }

    public function test_can_fetch_openapi_specification_from_docs_endpoint(): void
    {
        $response = $this->get('/api/docs/openapi.json');

        $response->assertStatus(200);
        $response->assertHeader('content-type', 'application/json; charset=utf-8');
        $response->assertJsonPath('openapi', '3.0.0');
    }

    public function test_legacy_openapi_specification_path_still_works(): void
    {
        $response = $this->get('/docs/openapi.json');

        $response->assertStatus(200);
        $response->assertJsonPath('openapi', '3.0.0');
    }

    public function test_openapi_specification_file_exists(): void
    {
        $filePath = public_path('docs/openapi.json');

        $this->assertFileExists($filePath);
        $this->assertJson(file_get_contents($filePath));
    }
}
