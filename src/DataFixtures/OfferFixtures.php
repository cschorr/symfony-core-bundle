<?php

declare(strict_types=1);

namespace C3net\CoreBundle\DataFixtures;

use C3net\CoreBundle\Entity\Offer;
use C3net\CoreBundle\Entity\OfferItem;
use C3net\CoreBundle\Entity\Transaction;
use C3net\CoreBundle\Enum\DomainEntityType;
use C3net\CoreBundle\Enum\OfferStatus;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class OfferFixtures extends AbstractCategorizableFixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $offersData = [
            // TXN-2025-0001: PAID - Should have accepted offer
            [
                'transaction' => 'TXN-2025-0001',
                'offerNumber' => 'OFF-2025-0001-V1',
                'status' => OfferStatus::ACCEPTED,
                'validUntil' => new \DateTimeImmutable('+30 days'),
                'items' => [
                    ['description' => 'Frontend Development (React/Next.js)', 'quantity' => '120', 'unitPrice' => '150.00', 'unit' => 'hours'],
                    ['description' => 'Backend API Development (Symfony)', 'quantity' => '100', 'unitPrice' => '150.00', 'unit' => 'hours'],
                    ['description' => 'Payment Gateway Integration', 'quantity' => '40', 'unitPrice' => '175.00', 'unit' => 'hours'],
                    ['description' => 'Testing & Quality Assurance', 'quantity' => '30', 'unitPrice' => '125.00', 'unit' => 'hours'],
                ],
            ],
            // TXN-2025-0002: PAID - Should have accepted offer
            [
                'transaction' => 'TXN-2025-0002',
                'offerNumber' => 'OFF-2025-0002-V1',
                'status' => OfferStatus::ACCEPTED,
                'validUntil' => new \DateTimeImmutable('+30 days'),
                'items' => [
                    ['description' => 'Mobile App Development (iOS)', 'quantity' => '80', 'unitPrice' => '165.00', 'unit' => 'hours'],
                    ['description' => 'Mobile App Development (Android)', 'quantity' => '80', 'unitPrice' => '165.00', 'unit' => 'hours'],
                    ['description' => 'Biometric Authentication Integration', 'quantity' => '30', 'unitPrice' => '180.00', 'unit' => 'hours'],
                ],
            ],
            // TXN-2025-0003: IN_PRODUCTION - Accepted offer
            [
                'transaction' => 'TXN-2025-0003',
                'offerNumber' => 'OFF-2025-0003-V1',
                'status' => OfferStatus::ACCEPTED,
                'validUntil' => new \DateTimeImmutable('+45 days'),
                'items' => [
                    ['description' => 'Security Assessment', 'quantity' => '40', 'unitPrice' => '200.00', 'unit' => 'hours'],
                    ['description' => 'System Implementation', 'quantity' => '120', 'unitPrice' => '175.00', 'unit' => 'hours'],
                ],
            ],
            // TXN-2025-0004: DELIVERED - Accepted offer
            [
                'transaction' => 'TXN-2025-0004',
                'offerNumber' => 'OFF-2025-0004-V1',
                'status' => OfferStatus::ACCEPTED,
                'validUntil' => new \DateTimeImmutable('+30 days'),
                'items' => [
                    ['description' => 'Dashboard Design & Development', 'quantity' => '100', 'unitPrice' => '160.00', 'unit' => 'hours'],
                    ['description' => 'Data Integration', 'quantity' => '50', 'unitPrice' => '170.00', 'unit' => 'hours'],
                ],
            ],
            // TXN-2025-0005: INVOICED - Accepted offer
            [
                'transaction' => 'TXN-2025-0005',
                'offerNumber' => 'OFF-2025-0005-V1',
                'status' => OfferStatus::ACCEPTED,
                'validUntil' => new \DateTimeImmutable('+30 days'),
                'items' => [
                    ['description' => 'Campaign Strategy & Planning', 'quantity' => '60', 'unitPrice' => '155.00', 'unit' => 'hours'],
                    ['description' => 'Content Creation', 'quantity' => '80', 'unitPrice' => '145.00', 'unit' => 'hours'],
                ],
            ],
            // TXN-2025-0009: PAID - Accepted offer with deposit/final invoices
            [
                'transaction' => 'TXN-2025-0009',
                'offerNumber' => 'OFF-2025-0009-V1',
                'status' => OfferStatus::ACCEPTED,
                'validUntil' => new \DateTimeImmutable('+30 days'),
                'items' => [
                    ['description' => 'CRM System Development', 'quantity' => '150', 'unitPrice' => '165.00', 'unit' => 'hours'],
                    ['description' => 'Pharmaceutical Module Customization', 'quantity' => '70', 'unitPrice' => '175.00', 'unit' => 'hours'],
                ],
            ],
        ];

        foreach ($offersData as $index => $offerData) {
            $transaction = $manager->getRepository(Transaction::class)->findOneBy(['transactionNumber' => $offerData['transaction']]);

            if (!$transaction) {
                throw new \RuntimeException(sprintf('Transaction "%s" not found for offer "%s"', $offerData['transaction'], $offerData['offerNumber']));
            }

            // Assign categories based on offer status
            $categoryNames = match ($offerData['status']) {
                OfferStatus::ACCEPTED => ['Marketing & Sales', 'Business Services'],
                OfferStatus::REJECTED => ['Marketing & Sales', 'Strategy Consulting'],
                default => ['Marketing & Sales', 'Digital Marketing'],
            };
            $categories = $this->findCategoriesByNames($manager, $categoryNames);

            $offer = (new Offer())
                ->setOfferNumber($offerData['offerNumber'])
                ->setStatus($offerData['status'])
                ->setValidUntil($offerData['validUntil'])
                ->setTransaction($transaction);

            // Calculate totals
            $subtotal = '0.00';
            $position = 1;
            foreach ($offerData['items'] as $itemData) {
                $itemTotal = \bcmul($itemData['quantity'], $itemData['unitPrice'], 2);
                $subtotal = \bcadd($subtotal, $itemTotal, 2);

                $offerItem = (new OfferItem())
                    ->setDescription($itemData['description'])
                    ->setQuantity($itemData['quantity'])
                    ->setUnitPrice($itemData['unitPrice'])
                    ->setUnit($itemData['unit'])
                    ->setTotalPrice($itemTotal)
                    ->setPosition($position++);

                $offer->addItem($offerItem);
            }

            $offer->setSubtotal($subtotal);
            $offer->setTaxRate('19.00'); // Standard VAT
            $taxAmount = \bcmul($subtotal, '0.19', 2);
            $offer->setTaxAmount($taxAmount);
            $offer->setTotalAmount(\bcadd($subtotal, $taxAmount, 2));

            // Persist and flush to get ID
            $this->persistAndFlush($manager, $offer);

            // Assign multiple categories
            $this->assignCategories($manager, $offer, $categories, DomainEntityType::Offer);
        }

        $this->flushSafely($manager);
    }

    public function getDependencies(): array
    {
        return [
            CategoryFixtures::class,
            TransactionFixtures::class,
        ];
    }
}
