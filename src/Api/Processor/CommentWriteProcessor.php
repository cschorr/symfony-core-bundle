<?php

declare(strict_types=1);

namespace App\Api\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use App\Entity\Comment;
use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\RateLimiter\RateLimiterFactory;

/**
 * @implements ProcessorInterface<Comment, Comment>
 */
final readonly class CommentWriteProcessor implements ProcessorInterface
{
    /**
     * @param ProcessorInterface<Comment, Comment> $persistProcessor
     */
    public function __construct(
        #[Autowire(service: 'api_platform.doctrine.orm.state.persist_processor')]
        private ProcessorInterface $persistProcessor,
        private Security $security,
        #[Autowire(service: 'limiter.comments_per_10m')]
        private RateLimiterFactory $commentsLimiter,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if ($data instanceof Comment) {
            $user = $this->security->getUser();
            if (!$user instanceof User) {
                throw new \LogicException('User must be authenticated to create a comment');
            }

            $limit = $this->commentsLimiter->create($user->getUserIdentifier())->consume(1);
            if (!$limit->isAccepted()) {
                $retryAfter = $limit->getRetryAfter();
                $retry = null !== $retryAfter ? $retryAfter->getTimestamp() : null;
                throw new TooManyRequestsHttpException($retry ? max(1, $retry - time()) : null, 'Comment rate limit exceeded');
            }

            if (null === $data->getAuthor()) {
                $data->setAuthor($user);
            }
        }

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }
}
