<?php

declare(strict_types=1);

namespace C3net\CoreBundle\Api\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use C3net\CoreBundle\Entity\Invoice;
use C3net\CoreBundle\Entity\User;
use C3net\CoreBundle\Enum\TransactionStatus;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * @implements ProcessorInterface<Invoice, Invoice>
 */
final readonly class InvoiceWriteProcessor implements ProcessorInterface
{
    /**
     * @param ProcessorInterface<Invoice, Invoice> $persistProcessor
     */
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private ProcessorInterface $persistProcessor,
        private Security $security,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if (!$data instanceof Invoice) {
            return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
        }

        // Auto-generate invoice number if not set
        if (null === $data->getInvoiceNumber()) {
            $data->setInvoiceNumber($this->generateInvoiceNumber());
        }

        // Calculate totals from items
        $data->calculateTotals();

        // Check if invoice is overdue
        $data->checkOverdue();

        // Update transaction status if invoice is paid
        if ($data->isPaid() && null === $data->getPaidAt()) {
            $data->setPaidAt(new \DateTimeImmutable());

            $transaction = $data->getTransaction();
            if (null !== $transaction) {
                // Check if all invoices for this transaction are paid
                $allPaid = true;
                foreach ($transaction->getInvoices() as $invoice) {
                    if (!$invoice->isPaid()) {
                        $allPaid = false;
                        break;
                    }
                }

                if ($allPaid) {
                    $transaction->setStatus(TransactionStatus::PAID);
                } elseif ($transaction->isDelivered()) {
                    $transaction->setStatus(TransactionStatus::INVOICED);
                }
            }
        }

        // Set sentBy user if status changed to sent
        $user = $this->security->getUser();
        if ($user instanceof User && $data->isSent() && null === $data->getSentAt()) {
            $data->setSentAt(new \DateTimeImmutable());
            $data->setSentBy($user);
        }

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }

    private function generateInvoiceNumber(): string
    {
        $year = date('Y');
        $random = str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);

        return sprintf('INV-%s-%s', $year, $random);
    }
}
