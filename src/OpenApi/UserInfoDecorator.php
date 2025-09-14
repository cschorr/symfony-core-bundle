<?php

declare(strict_types=1);

namespace App\OpenApi;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\Model;
use ApiPlatform\OpenApi\OpenApi;

final readonly class UserInfoDecorator implements OpenApiFactoryInterface
{
    public function __construct(
        private OpenApiFactoryInterface $decorated,
    ) {
    }

    public function __invoke(array $context = []): OpenApi
    {
        $openApi = $this->decorated->__invoke($context);
        $pathItem = new Model\PathItem(
            post: new Model\Operation(
                operationId: 'postUserInfo',
                tags: ['Login Check'],
                responses: [
                    '200' => new Model\Response(
                        description: 'User information retrieved successfully',
                        content: new \ArrayObject([
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'id' => [
                                            'type' => 'string',
                                            'format' => 'uuid',
                                            'example' => '550e8400-e29b-41d4-a716-446655440000',
                                        ],
                                        'username' => [
                                            'type' => 'string',
                                            'example' => 'john.doe',
                                        ],
                                        'roles' => [
                                            'type' => 'array',
                                            'items' => ['type' => 'string'],
                                            'example' => ['ROLE_USER', 'ROLE_ADMIN'],
                                        ],
                                        'firstName' => [
                                            'type' => 'string',
                                            'nullable' => true,
                                            'example' => 'John',
                                        ],
                                        'lastName' => [
                                            'type' => 'string',
                                            'nullable' => true,
                                            'example' => 'Doe',
                                        ],
                                        'isActive' => [
                                            'type' => 'boolean',
                                            'example' => true,
                                        ],
                                        'isLocked' => [
                                            'type' => 'boolean',
                                            'example' => false,
                                        ],
                                    ],
                                ],
                            ],
                        ])
                    ),
                    '400' => new Model\Response(
                        description: 'Bad request - Missing or invalid Authorization header',
                        content: new \ArrayObject([
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'error' => [
                                            'type' => 'string',
                                            'example' => 'Authorization header with Bearer token required',
                                        ],
                                    ],
                                ],
                            ],
                        ])
                    ),
                    '401' => new Model\Response(
                        description: 'Unauthorized - Invalid token',
                        content: new \ArrayObject([
                            'application/json' => [
                                'schema' => [
                                    'type' => 'object',
                                    'properties' => [
                                        'error' => [
                                            'type' => 'string',
                                            'example' => 'Invalid token',
                                        ],
                                    ],
                                ],
                            ],
                        ])
                    ),
                ],
                summary: 'Get user information from JWT token',
                description: 'Retrieve user information using a JWT token from Authorization header',
                parameters: [
                    new Model\Parameter(
                        name: 'Authorization',
                        in: 'header',
                        description: 'Bearer JWT token',
                        required: true,
                        schema: [
                            'type' => 'string',
                            'example' => 'Bearer eyJ0eXAiOiJKV1QiLCJhbGciOiJSUzI1NiJ9...',
                        ]
                    ),
                ]
            )
        );

        $openApi->getPaths()->addPath('/api/userinfo', $pathItem);

        return $openApi;
    }
}
