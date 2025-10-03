<?php

declare(strict_types=1);

namespace C3net\CoreBundle\DataFixtures;

use C3net\CoreBundle\Entity\Invoice;
use C3net\CoreBundle\Entity\InvoiceItem;
use C3net\CoreBundle\Entity\Offer;
use C3net\CoreBundle\Entity\Transaction;
use C3net\CoreBundle\Enum\InvoiceType;
use C3net\CoreBundle\Enum\PaymentStatus;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;

class InvoiceFixtures extends Fixture implements DependentFixtureInterface
{
    public function load(ObjectManager $manager): void
    {
        $invoicesData = [
            // TXN-2025-0001: PAID - Full invoice, paid
            [
                'transaction' => 'transaction_0',
                'invoiceNumber' => 'INV-2025-0001',
                'type' => InvoiceType::FULL,
                'paymentStatus' => PaymentStatus::PAID, // FIXED: PaymentStatus not InvoicePaymentStatus
                'dueDate' => new \DateTimeImmutable('-10 days'),
                'offer' => 'offer_0',
            ],
            // TXN-2025-0002: PAID - Full invoice, paid
            [
                'transaction' => 'transaction_1',
                'invoiceNumber' => 'INV-2025-0002',
                'type' => InvoiceType::FULL,
                'paymentStatus' => PaymentStatus::PAID, // FIXED: PaymentStatus not InvoicePaymentStatus
                'dueDate' => new \DateTimeImmutable('-5 days'),
                'offer' => 'offer_1',
            ],
            // TXN-2025-0005: INVOICED - Full invoice, unpaid
            [
                'transaction' => 'transaction_4',
                'invoiceNumber' => 'INV-2025-0003',
                'type' => InvoiceType::FULL,
                'paymentStatus' => PaymentStatus::UNPAID, // FIXED: PaymentStatus not InvoicePaymentStatus
                'dueDate' => new \DateTimeImmutable('+14 days'),
                'offer' => 'offer_4',
            ],
            // TXN-2025-0009: PAID - Deposit + Final invoices
            [
                'transaction' => 'transaction_8',
                'invoiceNumber' => 'INV-2025-0004',
                'type' => InvoiceType::DEPOSIT,
                'paymentStatus' => PaymentStatus::PAID, // FIXED: PaymentStatus not InvoicePaymentStatus
                'dueDate' => new \DateTimeImmutable('-30 days'),
                'offer' => 'offer_5',
                'depositPercentage' => 30,
            ],
            [
                'transaction' => 'transaction_8',
                'invoiceNumber' => 'INV-2025-0005',
                'type' => InvoiceType::FINAL,
                'paymentStatus' => PaymentStatus::PAID, // FIXED: PaymentStatus not InvoicePaymentStatus
                'dueDate' => new \DateTimeImmutable('-7 days'),
                'offer' => 'offer_5',
                'depositPercentage' => 70,
            ],
        ];

        foreach ($invoicesData as $index => $invoiceData) {
            $transaction = $this->getReference($invoiceData['transaction'], Transaction::class);
            $offer = $this->getReference($invoiceData['offer'], Offer::class);

            $invoice = (new Invoice())
                ->setInvoiceNumber($invoiceData['invoiceNumber'])
                // REMOVED: ->setTitle() - Invoice doesn't have this method
                ->setInvoiceType($invoiceData['type'])
                ->setPaymentStatus($invoiceData['paymentStatus'])
                ->setDueDate($invoiceData['dueDate'])
                ->setTransaction($transaction);

            // Calculate invoice amounts based on type
            $depositPercentage = $invoiceData['depositPercentage'] ?? 100;

            if ($invoiceData['type'] === InvoiceType::DEPOSIT) {
                $subtotal = \bcmul($offer->getSubtotal(), (string)($depositPercentage / 100), 2); // FIXED: Added backslash
            } elseif ($invoiceData['type'] === InvoiceType::FINAL) {
                $subtotal = \bcmul($offer->getSubtotal(), (string)($depositPercentage / 100), 2); // FIXED: Added backslash
            } else {
                $subtotal = $offer->getSubtotal();
            }

            $invoice->setSubtotal($subtotal);
            $invoice->setTaxRate($offer->getTaxRate());
            $taxAmount = \bcmul($subtotal, \bcdiv($offer->getTaxRate(), '100', 4), 2); // FIXED: Added backslashes
            $invoice->setTaxAmount($taxAmount);
            $invoice->setTotalAmount(\bcadd($subtotal, $taxAmount, 2)); // FIXED: setTotalAmount() not setTotal(), added backslash

            // Add invoice items matching the offer
            $itemCount = 0;
            $position = 1;
            foreach ($offer->getItems() as $offerItem) { // FIXED: getItems() not getOfferItems()
                $quantity = $offerItem->getQuantity();
                $unitPrice = $offerItem->getUnitPrice();

                // Adjust quantity for deposit/final invoices
                if ($invoiceData['type'] === InvoiceType::DEPOSIT || $invoiceData['type'] === InvoiceType::FINAL) {
                    $quantity = \bcmul($quantity, (string)($depositPercentage / 100), 2); // FIXED: Added backslash
                }

                $totalPrice = \bcmul($quantity, $unitPrice, 2); // FIXED: Added backslash

                $invoiceItem = (new InvoiceItem())
                    ->setDescription($offerItem->getDescription())
                    ->setQuantity($quantity)
                    ->setUnitPrice($unitPrice)
                    ->setUnit($offerItem->getUnit()) // ADDED: setUnit()
                    ->setTotalPrice($totalPrice) // ADDED: setTotalPrice()
                    ->setPosition($position++) // ADDED: setPosition()
                    ->setInvoice($invoice);

                $manager->persist($invoiceItem);
                $itemCount++;

                // Limit items to avoid too many in deposit invoices
                if ($itemCount >= 5) {
                    break;
                }
            }

            $manager->persist($invoice);
            $this->addReference('invoice_' . $index, $invoice);
        }

        $manager->flush();
    }

    public function getDependencies(): array
    {
        return [
            TransactionFixtures::class,
            OfferFixtures::class,
        ];
    }
}
