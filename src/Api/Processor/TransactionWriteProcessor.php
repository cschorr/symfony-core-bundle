<?php

declare(strict_types=1);

namespace C3net\CoreBundle\Api\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use C3net\CoreBundle\Entity\Transaction;
use C3net\CoreBundle\Entity\User;
use C3net\CoreBundle\Service\TransactionNumberGenerator;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * @implements ProcessorInterface<Transaction, Transaction>
 */
final readonly class TransactionWriteProcessor implements ProcessorInterface
{
    /**
     * @param ProcessorInterface<Transaction, Transaction> $persistProcessor
     */
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private ProcessorInterface $persistProcessor,
        private Security $security,
        private TransactionNumberGenerator $numberGenerator,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        // @phpstan-ignore-next-line instanceof.alwaysTrue (Runtime safety check needed despite generic type hint)
        if (!$data instanceof Transaction) {
            return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
        }

        // Auto-generate transaction number if not set using the robust generator service
        if (null === $data->getTransactionNumber()) {
            $data->setTransactionNumber($this->numberGenerator->generate());
        }

        // Set assigned user if not set
        $user = $this->security->getUser();
        if ($user instanceof User && null === $data->getAssignedTo()) {
            $data->setAssignedTo($user);
        }

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
}
