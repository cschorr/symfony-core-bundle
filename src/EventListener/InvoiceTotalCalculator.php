<?php

declare(strict_types=1);

namespace C3net\CoreBundle\EventListener;

use C3net\CoreBundle\Entity\Invoice;
use C3net\CoreBundle\Entity\InvoiceItem;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Events;
use Doctrine\Persistence\Event\LifecycleEventArgs;

/**
 * Automatically recalculate invoice totals when invoice items change.
 *
 * This listener ensures totals are always up-to-date without manual
 * calculation calls, improving performance by updating only when needed.
 */
#[AsEntityListener(event: Events::postPersist, method: 'recalculate', entity: InvoiceItem::class)]
#[AsEntityListener(event: Events::postUpdate, method: 'recalculate', entity: InvoiceItem::class)]
#[AsEntityListener(event: Events::postRemove, method: 'recalculate', entity: InvoiceItem::class)]
class InvoiceTotalCalculator
{
    /**
     * Recalculate invoice totals when an item changes.
     *
     * @param LifecycleEventArgs<EntityManagerInterface> $event
     */
    public function recalculate(InvoiceItem $item, LifecycleEventArgs $event): void
    {
        $invoice = $item->getInvoice();
        if (null === $invoice) {
            return;
        }

        // Recalculate totals
        $invoice->calculateTotals();

        // Mark for update
        $entityManager = $event->getObjectManager();
        $entityManager->persist($invoice);
    }
}
