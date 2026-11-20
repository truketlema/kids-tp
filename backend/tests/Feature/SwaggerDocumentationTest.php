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
                    '/categories' => ['get', 'post'],
                    '/categories/{categoryId}' => ['delete'],
                    '/categories/{categoryId}/activities' => ['post'],
                    '/activities/{activityId}' => ['patch', 'delete'],
                    '/family' => ['patch'],
                    '/family/invitations' => ['post'],
                    '/invitations/{token}' => ['get'],
                    '/invitations/accept' => ['post'],
                    '/children' => ['get', 'post'],
                    '/children/{childId}' => ['patch', 'delete'],
                    '/children/{childId}/allocation' => ['patch'],
                ],
                'components' => [
                    'securitySchemes' => ['bearerAuth'],
                ],
            ]);
    }
}
