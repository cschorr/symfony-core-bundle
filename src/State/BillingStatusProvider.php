<?php

declare(strict_types=1);

namespace C3net\CoreBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProviderInterface;
use C3net\CoreBundle\ApiResource\BillingStatusCollection;
use C3net\CoreBundle\Enum\BillingStatus;

/**
 * @implements ProviderInterface<BillingStatusCollection>
 */
class BillingStatusProvider implements ProviderInterface
{
    public function provide(Operation $operation, array $uriVariables = [], array $context = []): object|array|null
    {
        $items = [];

        foreach (BillingStatus::cases() as $status) {
            $items[] = [
                'name' => $status->name,
                'value' => $status->value,
                'label' => $status->getLabel(),
                'badgeClass' => $status->getBadgeClass(),
            ];
        }

        return new BillingStatusCollection($items);
    }
}
