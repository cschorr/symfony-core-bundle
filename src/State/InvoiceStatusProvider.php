<?php

declare(strict_types=1);

namespace C3net\CoreBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use C3net\CoreBundle\ApiResource\InvoiceStatusCollection;
use C3net\CoreBundle\Enum\InvoiceStatus;

/**
 * @implements ProviderInterface<InvoiceStatusCollection>
 */
class InvoiceStatusProvider implements ProviderInterface
{
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $items = [];

        foreach (InvoiceStatus::cases() as $status) {
            $items[] = [
                'name' => $status->name,
                'value' => $status->value,
                'label' => $status->getLabel(),
                'badgeClass' => $status->getBadgeClass(),
            ];
        }

        return new InvoiceStatusCollection($items);
    }
}
