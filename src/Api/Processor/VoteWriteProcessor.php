<?php

declare(strict_types=1);

namespace App\Api\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\User;
use App\Entity\Vote;
use App\Repository\VoteRepository;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\RateLimiter\RateLimiterFactory;

/**
 * @implements ProcessorInterface<Vote, Vote>
 */
final readonly class VoteWriteProcessor implements ProcessorInterface
{
    /**
     * @param ProcessorInterface<Vote, Vote> $persistProcessor
     */
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private ProcessorInterface $persistProcessor,
        private Security $security,
        #[Autowire(service: 'limiter.votes_per_10m')]
        private RateLimiterFactory $votesLimiter,
        private VoteRepository $votes,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        // Remove the redundant instanceof check - $data is already typed as Vote via generics
        // If $data is not a Vote, we pass it through to the persist processor
        if (!$data instanceof Vote) {
            return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
        }

        $user = $this->security->getUser();
        if (!$user instanceof User) {
            throw new \LogicException('User must be authenticated to vote');
        }

        $limit = $this->votesLimiter->create($user->getUserIdentifier())->consume(1);
        if (!$limit->isAccepted()) {
            $retryAfter = $limit->getRetryAfter();
            $retry = $retryAfter->getTimestamp();
            throw new TooManyRequestsHttpException($retry ? max(1, $retry - time()) : null, 'Vote rate limit exceeded');
        }

        $data->setVoter($user);

        if (!\in_array($data->getValue(), [-1, 1], true)) {
            throw new BadRequestHttpException('value must be -1 or 1');
        }

        // „POST wenn schon vorhanden" → als Update behandeln
        $existing = $this->votes->findOneBy(['comment' => $data->getComment(), 'voter' => $user]);
        if ($existing instanceof Vote && $existing !== $data) {
            $existing->setValue($data->getValue());

            return $this->persistProcessor->process($existing, $operation, $uriVariables, $context);
        }

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
}
