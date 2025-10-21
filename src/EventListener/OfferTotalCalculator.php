<?php

declare(strict_types=1);

namespace C3net\CoreBundle\EventListener;

use C3net\CoreBundle\Entity\Offer;
use C3net\CoreBundle\Entity\OfferItem;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;

/**
 * Automatically recalculate offer totals when offer items change.
 *
 * This listener ensures totals are always up-to-date without manual
 * calculation calls, improving performance by updating only when needed.
 */
#[AsEntityListener(event: Events::postPersist, method: 'recalculate', entity: OfferItem::class)]
#[AsEntityListener(event: Events::postUpdate, method: 'recalculate', entity: OfferItem::class)]
#[AsEntityListener(event: Events::postRemove, method: 'recalculate', entity: OfferItem::class)]
class OfferTotalCalculator
{
    /**
     * Recalculate offer totals when an item changes.
     *
     * @param LifecycleEventArgs<EntityManagerInterface> $event
     */
    public function recalculate(OfferItem $item, LifecycleEventArgs $event): void
    {
        $offer = $item->getOffer();
        if (null === $offer) {
            return;
        }

        // Recalculate totals
        $offer->calculateTotals();

        // Mark for update
        $entityManager = $event->getObjectManager();
        $entityManager->persist($offer);
    }
}
