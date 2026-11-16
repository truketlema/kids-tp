<?php

namespace Tests\Feature;

use Tests\TestCase;

class SwaggerDocumentationTest extends TestCase
{
    public function test_swagger_ui_and_openapi_document_are_available(): void
    {
        $this->get('/api/documentation')
            ->assertOk()
            ->assertSee('swagger-ui');

        $this->get('/docs')
            ->assertOk()
            ->assertJsonStructure([
                'openapi',
                'info' => ['title', 'version'],
                'paths' => [
                    '/health' => ['get'],
                    '/register' => ['post'],
                    '/login' => ['post'],
                    '/user' => ['get'],
                    '/children' => ['get', 'post'],
                ],
                'components' => [
                    'securitySchemes' => ['bearerAuth'],
                ],
            ]);
    }
}
