<?php

declare(strict_types=1);

namespace C3net\CoreBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use C3net\CoreBundle\ApiResource\EnvironmentCollection;
use C3net\CoreBundle\Enum\Environment;

/**
 * @implements ProviderInterface<EnvironmentCollection>
 */
class EnvironmentProvider implements ProviderInterface
{
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $items = [];

        foreach (Environment::cases() as $env) {
            $items[] = [
                'name' => $env->name,
                'value' => $env->value,
                'label' => $env->getLabel(),
            ];
        }

        return new EnvironmentCollection($items);
    }
}
