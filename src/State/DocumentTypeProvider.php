<?php

declare(strict_types=1);

namespace C3net\CoreBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use C3net\CoreBundle\ApiResource\DocumentTypeCollection;
use C3net\CoreBundle\Enum\DocumentType;

/**
 * @implements ProviderInterface<DocumentTypeCollection>
 */
class DocumentTypeProvider implements ProviderInterface
{
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $items = [];

        foreach (DocumentType::cases() as $type) {
            $items[] = [
                'name' => $type->name,
                'value' => $type->value,
                'label' => $type->getLabel(),
            ];
        }

        return new DocumentTypeCollection($items);
    }
}
