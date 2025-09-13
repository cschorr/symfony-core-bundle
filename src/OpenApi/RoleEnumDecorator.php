<?php

declare(strict_types=1);

// src/OpenApi/RoleEnumDecorator.php

namespace App\OpenApi;

use ApiPlatform\OpenApi\Factory\OpenApiFactoryInterface;
use ApiPlatform\OpenApi\OpenApi;
use App\Enum\UserRole;

final readonly class RoleEnumDecorator implements OpenApiFactoryInterface
{
    public function __construct(private OpenApiFactoryInterface $decorated)
    {
    }

    public function __invoke(array $context = []): OpenApi
    {
        $openApi = ($this->decorated)($context);

        $enumValues = UserRole::values(); // ['ROLE_USER', ...]
        $components = $openApi->getComponents();
        if (!$components) {
            return $openApi;
        }

        // Schemas is: array<string, \ArrayObject>
        $schemas = $components->getSchemas() ?? [];
        foreach ($schemas as $schema) {
            if (!$schema instanceof \ArrayObject) {
                continue;
            }

            $schemaArr = $schema->getArrayCopy();

            // Only if the schema has a "roles" property
            if (!isset($schemaArr['properties']['roles'])) {
                continue;
            }

            // Ensure "roles" is an array of enum strings
            $rolesProp = $schemaArr['properties']['roles'] ?? [];
            $rolesProp['type'] = 'array';
            $rolesProp['items'] = [
                'type' => 'string',
                'enum' => $enumValues,
            ];

            $schemaArr['properties']['roles'] = $rolesProp;

            // write back
            $schema->exchangeArray($schemaArr);
        }

        return $openApi;
    }
}
