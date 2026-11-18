<?php

namespace App\OpenApi;

use OpenApi\Spec as OA;

#[OA\Info(
    title: 'Kids TP API',
    version: '1.0.0',
    description: 'API for parent accounts, family details, and children.',
)]
#[OA\Server(url: '/api', description: 'Kids TP API')]
#[OA\Security\Scheme(
    component: 'bearerAuth',
    type: 'http',
    scheme: 'bearer',
    bearerFormat: 'Sanctum token',
)]
class ApiSpec
{
    #[OA\Operation\Get(
        path: '/health',
        tags: ['System'],
        summary: 'Check API health',
        responses: [new OA\Response(response: 200, description: 'API is running')],
    )]
    public function health(): void {}

    #[OA\Operation\Post(
        path: '/register',
        tags: ['Authentication'],
        summary: 'Register a parent and create their family',
        requestBody: new OA\RequestBody(
            required: true,
            content: [new OA\MediaType\Json(schema: new OA\Schema(
                type: 'object',
                required: ['name', 'email', 'phone', 'password', 'password_confirmation', 'family_name'],
                properties: [
                    new OA\Property(property: 'name', schema: new OA\Schema(type: 'string', maxLength: 255)),
                    new OA\Property(property: 'email', schema: new OA\Schema(type: 'string', format: 'email')),
                    new OA\Property(property: 'phone', schema: new OA\Schema(type: 'string', maxLength: 20)),
                    new OA\Property(property: 'password', schema: new OA\Schema(type: 'string', minLength: 8)),
                    new OA\Property(property: 'password_confirmation', schema: new OA\Schema(type: 'string', minLength: 8)),
                    new OA\Property(property: 'family_name', schema: new OA\Schema(type: 'string', maxLength: 255)),
                ],
            ))],
        ),
        responses: [
            new OA\Response(response: 201, description: 'Account created; returns the user, family, and bearer token'),
            new OA\Response(response: 422, description: 'Validation failed'),
        ],
    )]
    public function register(): void {}

    #[OA\Operation\Post(
        path: '/login',
        tags: ['Authentication'],
        summary: 'Authenticate a parent',
        requestBody: new OA\RequestBody(
            required: true,
            content: [new OA\MediaType\Json(schema: new OA\Schema(
                type: 'object',
                required: ['name', 'email', 'password'],
                properties: [
                    new OA\Property(property: 'name', schema: new OA\Schema(type: 'string')),
                    new OA\Property(property: 'email', schema: new OA\Schema(type: 'string', format: 'email')),
                    new OA\Property(property: 'password', schema: new OA\Schema(type: 'string')),
                ],
            ))],
        ),
        responses: [
            new OA\Response(response: 200, description: 'Login succeeded; returns the user and bearer token'),
            new OA\Response(response: 401, description: 'Credentials did not match'),
            new OA\Response(response: 422, description: 'Validation failed'),
        ],
    )]
    public function login(): void {}

    #[OA\Operation\Get(
        path: '/user',
        tags: ['Account'],
        summary: 'Get the authenticated parent and family',
        security: [new OA\Security\Requirement(scheme: 'bearerAuth')],
        responses: [
            new OA\Response(response: 200, description: 'Authenticated user with family details'),
            new OA\Response(response: 401, description: 'Unauthenticated or invalid token'),
        ],
    )]
    public function user(): void {}

    #[OA\Operation\Get(
        path: '/children',
        tags: ['Children'],
        summary: 'List children in the authenticated parent’s family',
        description: 'Returns each child’s saved budget allocation, or null when the fixed default allocation applies.',
        security: [new OA\Security\Requirement(scheme: 'bearerAuth')],
        responses: [
            new OA\Response(response: 200, description: 'Family children, ordered by name'),
            new OA\Response(response: 401, description: 'Unauthenticated or invalid token'),
            new OA\Response(response: 409, description: 'Account is not associated with a family'),
        ],
    )]
    public function children(): void {}

    #[OA\Operation\Post(
        path: '/children',
        tags: ['Children'],
        summary: 'Add a child to the authenticated parent’s family',
        security: [new OA\Security\Requirement(scheme: 'bearerAuth')],
        requestBody: new OA\RequestBody(
            required: true,
            content: [new OA\MediaType\Json(schema: new OA\Schema(
                type: 'object',
                required: ['name', 'date_of_birth'],
                properties: [
                    new OA\Property(property: 'name', schema: new OA\Schema(type: 'string', maxLength: 255)),
                    new OA\Property(property: 'date_of_birth', schema: new OA\Schema(type: 'string', format: 'date')),
                    new OA\Property(
                        property: 'avatar',
                        schema: new OA\Schema(type: 'string', enum: ['bear', 'cat', 'dog', 'flower', 'rocket', 'star']),
                    ),
                ],
            ))],
        ),
        responses: [
            new OA\Response(response: 201, description: 'Child created'),
            new OA\Response(response: 401, description: 'Unauthenticated or invalid token'),
            new OA\Response(response: 409, description: 'Account is not associated with a family'),
            new OA\Response(response: 422, description: 'Validation failed'),
        ],
    )]
    public function addChild(): void {}

    #[OA\Operation\Patch(
        path: '/family',
        tags: ['Family'],
        summary: 'Update the authenticated parent’s family name and shared settings',
        security: [new OA\Security\Requirement(scheme: 'bearerAuth')],
        requestBody: new OA\RequestBody(
            required: true,
            content: [new OA\MediaType\Json(schema: new OA\Schema(
                type: 'object',
                properties: [
                    new OA\Property(property: 'family_name', schema: new OA\Schema(type: 'string', maxLength: 255)),
                    new OA\Property(property: 'conversion_rate', schema: new OA\Schema(type: 'number', exclusiveMinimum: 0)),
                    new OA\Property(property: 'daily_deduction_cap', schema: new OA\Schema(type: 'integer', minimum: 0, maximum: 1000000)),
                ],
            ))],
        ),
        responses: [
            new OA\Response(response: 200, description: 'Family settings updated'),
            new OA\Response(response: 401, description: 'Unauthenticated or invalid token'),
            new OA\Response(response: 409, description: 'Account is not associated with a family'),
            new OA\Response(response: 422, description: 'Validation failed'),
        ],
    )]
    public function updateFamily(): void {}

    #[OA\Operation\Post(
        path: '/family/invitations',
        tags: ['Family'],
        summary: 'Email an invitation to join the authenticated parent’s family',
        security: [new OA\Security\Requirement(scheme: 'bearerAuth')],
        requestBody: new OA\RequestBody(
            required: true,
            content: [new OA\MediaType\Json(schema: new OA\Schema(
                type: 'object',
                required: ['email'],
                properties: [
                    new OA\Property(property: 'email', schema: new OA\Schema(type: 'string', format: 'email')),
                ],
            ))],
        ),
        responses: [
            new OA\Response(response: 201, description: 'Invitation email sent; invitation expires after seven days'),
            new OA\Response(response: 401, description: 'Unauthenticated or invalid token'),
            new OA\Response(response: 409, description: 'Email already belongs to an account or no family is associated'),
            new OA\Response(response: 422, description: 'Invalid email'),
        ],
    )]
    public function inviteParent(): void {}

    #[OA\Operation\Get(
        path: '/invitations/{token}',
        tags: ['Family'],
        summary: 'View a family invitation before accepting it',
        parameters: [
            new OA\Parameter(name: 'token', in: 'path', required: true, schema: new OA\Schema(type: 'string')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Invitation email and family name'),
            new OA\Response(response: 410, description: 'Invitation is invalid, expired, or already used'),
        ],
    )]
    public function showFamilyInvitation(): void {}

    #[OA\Operation\Post(
        path: '/invitations/accept',
        tags: ['Family'],
        summary: 'Create a parent account and accept a family invitation',
        requestBody: new OA\RequestBody(
            required: true,
            content: [new OA\MediaType\Json(schema: new OA\Schema(
                type: 'object',
                required: ['token', 'name', 'phone', 'password', 'password_confirmation'],
                properties: [
                    new OA\Property(property: 'token', schema: new OA\Schema(type: 'string', minLength: 64, maxLength: 64)),
                    new OA\Property(property: 'name', schema: new OA\Schema(type: 'string', maxLength: 255)),
                    new OA\Property(property: 'phone', schema: new OA\Schema(type: 'string', maxLength: 20)),
                    new OA\Property(property: 'password', schema: new OA\Schema(type: 'string', minLength: 8)),
                    new OA\Property(property: 'password_confirmation', schema: new OA\Schema(type: 'string', minLength: 8)),
                ],
            ))],
        ),
        responses: [
            new OA\Response(response: 201, description: 'Parent account created and joined family; returns bearer token'),
            new OA\Response(response: 409, description: 'Email already belongs to an account'),
            new OA\Response(response: 410, description: 'Invitation is invalid, expired, or already used'),
            new OA\Response(response: 422, description: 'Validation failed'),
        ],
    )]
    public function acceptFamilyInvitation(): void {}

    #[OA\Operation\Patch(
        path: '/children/{childId}',
        tags: ['Children'],
        summary: 'Update a child in the authenticated parent’s family',
        security: [new OA\Security\Requirement(scheme: 'bearerAuth')],
        parameters: [
            new OA\Parameter(name: 'childId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: [new OA\MediaType\Json(schema: new OA\Schema(
                type: 'object',
                properties: [
                    new OA\Property(property: 'name', schema: new OA\Schema(type: 'string', maxLength: 255)),
                    new OA\Property(property: 'date_of_birth', schema: new OA\Schema(type: 'string', format: 'date')),
                    new OA\Property(property: 'avatar', schema: new OA\Schema(type: 'string', nullable: true, enum: ['bear', 'cat', 'dog', 'flower', 'rocket', 'star'])),
                ],
            ))],
        ),
        responses: [
            new OA\Response(response: 200, description: 'Child updated'),
            new OA\Response(response: 401, description: 'Unauthenticated or invalid token'),
            new OA\Response(response: 404, description: 'Child is not in the authenticated parent’s family'),
            new OA\Response(response: 422, description: 'Validation failed'),
        ],
    )]
    public function updateChild(): void {}

    #[OA\Operation\Delete(
        path: '/children/{childId}',
        tags: ['Children'],
        summary: 'Remove a child with no ledger history from the authenticated parent’s family',
        security: [new OA\Security\Requirement(scheme: 'bearerAuth')],
        parameters: [
            new OA\Parameter(name: 'childId', in: 'path', required: true, schema: new OA\Schema(type: 'integer')),
        ],
        responses: [
            new OA\Response(response: 200, description: 'Child removed'),
            new OA\Response(response: 401, description: 'Unauthenticated or invalid token'),
            new OA\Response(response: 404, description: 'Child is not in the authenticated parent’s family'),
            new OA\Response(response: 409, description: 'Child has ledger history and cannot be removed'),
        ],
    )]
    public function removeChild(): void {}

    #[OA\Operation\Patch(
        path: '/children/{childId}/allocation',
        tags: ['Children'],
        summary: 'Save or restore a child’s budget allocation',
        description: 'Allocation percentages must total 100. Send allocation: null to restore the fixed defaults.',
        security: [new OA\Security\Requirement(scheme: 'bearerAuth')],
        parameters: [
            new OA\Parameter(
                name: 'childId',
                in: 'path',
                required: true,
                schema: new OA\Schema(type: 'integer'),
            ),
        ],
        requestBody: new OA\RequestBody(
            required: true,
            content: [new OA\MediaType\Json(schema: new OA\Schema(
                type: 'object',
                required: ['allocation'],
                properties: [
                    new OA\Property(
                        property: 'allocation',
                        schema: new OA\Schema(
                            type: 'object',
                            nullable: true,
                            required: [
                                'long_term_savings',
                                'planned_purchase',
                                'discretionary_spending',
                                'reserve',
                            ],
                            properties: [
                                new OA\Property(property: 'long_term_savings', schema: new OA\Schema(type: 'integer', minimum: 0, maximum: 100)),
                                new OA\Property(property: 'planned_purchase', schema: new OA\Schema(type: 'integer', minimum: 0, maximum: 100)),
                                new OA\Property(property: 'discretionary_spending', schema: new OA\Schema(type: 'integer', minimum: 0, maximum: 100)),
                                new OA\Property(property: 'reserve', schema: new OA\Schema(type: 'integer', minimum: 0, maximum: 100)),
                            ],
                        ),
                    ),
                ],
            ))],
        ),
        responses: [
            new OA\Response(response: 200, description: 'Allocation saved or fixed defaults restored'),
            new OA\Response(response: 401, description: 'Unauthenticated or invalid token'),
            new OA\Response(response: 409, description: 'Account is not associated with a family'),
            new OA\Response(response: 404, description: 'Child is not in the authenticated parent’s family'),
            new OA\Response(response: 422, description: 'Validation failed or percentages do not total 100'),
        ],
    )]
    public function updateChildAllocation(): void {}
}
