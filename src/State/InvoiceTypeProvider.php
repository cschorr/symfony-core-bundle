<?php

declare(strict_types=1);

namespace C3net\CoreBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use C3net\CoreBundle\ApiResource\InvoiceTypeCollection;
use C3net\CoreBundle\Enum\InvoiceType;

/**
 * @implements ProviderInterface<InvoiceTypeCollection>
 */
class InvoiceTypeProvider implements ProviderInterface
{
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $items = [];

        foreach (InvoiceType::cases() as $type) {
            $items[] = [
                'name' => $type->name,
                'value' => $type->value,
                'label' => $type->getLabel(),
            ];
        }

        return new InvoiceTypeCollection($items);
    }
}
