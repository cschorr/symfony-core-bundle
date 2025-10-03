<?php

declare(strict_types=1);

namespace C3net\CoreBundle\Api\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use C3net\CoreBundle\Entity\Offer;
use C3net\CoreBundle\Entity\User;
use C3net\CoreBundle\Enum\TransactionStatus;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * @implements ProcessorInterface<Offer, Offer>
 */
final readonly class OfferWriteProcessor implements ProcessorInterface
{
    /**
     * @param ProcessorInterface<Offer, Offer> $persistProcessor
     */
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private ProcessorInterface $persistProcessor,
        private Security $security,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if (!$data instanceof Offer) {
            return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
        }

        // Auto-generate offer number if not set
        if (null === $data->getOfferNumber()) {
            $data->setOfferNumber($this->generateOfferNumber($data));
        }

        // Calculate totals from items
        $data->calculateTotals();

        // If offer was just accepted, update transaction status
        if ($data->isAccepted() && null === $data->getAcceptedAt()) {
            $data->setAcceptedAt(new \DateTimeImmutable());

            $transaction = $data->getTransaction();
            if ($transaction && $transaction->isDraft()) {
                $transaction->setStatus(TransactionStatus::ORDERED);
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

    private function generateOfferNumber(Offer $offer): string
    {
        $year = date('Y');
        $random = str_pad((string) random_int(1, 9999), 4, '0', STR_PAD_LEFT);
        $version = $offer->getVersion();

        return sprintf('OFF-%s-%s-V%d', $year, $random, $version);
    }
}
