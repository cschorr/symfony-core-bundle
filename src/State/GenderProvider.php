<?php

declare(strict_types=1);

namespace C3net\CoreBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use C3net\CoreBundle\ApiResource\GenderCollection;
use C3net\CoreBundle\Enum\Gender;

/**
 * @implements ProviderInterface<GenderCollection>
 */
class GenderProvider implements ProviderInterface
{
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $items = [];

        foreach (Gender::cases() as $gender) {
            $items[] = [
                'name' => $gender->name,
                'value' => $gender->value,
                'label' => $gender->getLabel(),
                'pronoun' => $gender->getPronoun(),
                'salutation' => $gender->getSalutation(),
            ];
        }

        return new GenderCollection($items);
    }
}
