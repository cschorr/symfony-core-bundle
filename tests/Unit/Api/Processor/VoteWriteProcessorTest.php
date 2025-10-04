<?php

declare(strict_types=1);

namespace C3net\CoreBundle\Tests\Unit\Api\Processor;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use C3net\CoreBundle\Api\Processor\VoteWriteProcessor;
use C3net\CoreBundle\Entity\Comment;
use C3net\CoreBundle\Entity\User;
use C3net\CoreBundle\Entity\Vote;
use C3net\CoreBundle\Repository\VoteRepository;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpKernel\Exception\BadRequestHttpException;
use Symfony\Component\HttpKernel\Exception\TooManyRequestsHttpException;
use Symfony\Component\RateLimiter\Limit;
use Symfony\Component\RateLimiter\LimiterInterface;
use Symfony\Component\RateLimiter\RateLimiterFactory;

class VoteWriteProcessorTest extends TestCase
{
    private VoteWriteProcessor $processor;
    private ProcessorInterface&MockObject $persistProcessor;
    private Security&MockObject $security;
    private RateLimiterFactory&MockObject $rateLimiterFactory;
    private EntityManagerInterface&MockObject $entityManager;
    private VoteRepository&MockObject $voteRepository;
    private Operation&MockObject $operation;

    protected function setUp(): void
    {
        $this->persistProcessor = $this->createMock(ProcessorInterface::class);
        $this->security = $this->createMock(Security::class);
        $this->rateLimiterFactory = $this->createMock(RateLimiterFactory::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->voteRepository = $this->createMock(VoteRepository::class);
        $this->operation = $this->createMock(Operation::class);

        $this->entityManager
            ->method('getRepository')
            ->with(Vote::class)
            ->willReturn($this->voteRepository);

        $this->processor = new VoteWriteProcessor(
            $this->persistProcessor,
            $this->security,
            $this->rateLimiterFactory,
            $this->entityManager
        );
    }

    public function testConstructor(): void
    {
        $processor = new VoteWriteProcessor(
            $this->persistProcessor,
            $this->security,
            $this->rateLimiterFactory,
            $this->entityManager
        );

        $this->assertInstanceOf(VoteWriteProcessor::class, $processor);
        $this->assertInstanceOf(ProcessorInterface::class, $processor);
    }

    public function testProcessWithNonVoteData(): void
    {
        $nonVoteData = new \stdClass();
        $expectedResult = 'processed';

        $this->persistProcessor
            ->expects($this->once())
            ->method('process')
            ->with($nonVoteData, $this->operation, [], [])
            ->willReturn($expectedResult);

        $result = $this->processor->process($nonVoteData, $this->operation);

        $this->assertSame($expectedResult, $result);
    }

    public function testProcessThrowsExceptionWhenUserNotAuthenticated(): void
    {
        $vote = new Vote();

        $this->security
            ->method('getUser')
            ->willReturn(null);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('User must be authenticated to vote');

        $this->processor->process($vote, $this->operation);
    }

    public function testProcessThrowsExceptionWhenUserIsNotUserInstance(): void
    {
        $vote = new Vote();
        $nonUserObject = new \stdClass();

        $this->security
            ->method('getUser')
            ->willReturn($nonUserObject);

        $this->expectException(\LogicException::class);
        $this->expectExceptionMessage('User must be authenticated to vote');

        $this->processor->process($vote, $this->operation);
    }

    public function testProcessThrowsExceptionWhenRateLimitExceeded(): void
    {
        $vote = new Vote();
        $user = new User();
        $user->setEmail('test@example.com');

        $limiter = $this->createMock(LimiterInterface::class);
        $limit = $this->createMock(Limit::class);

        $this->security
            ->method('getUser')
            ->willReturn($user);

        $this->rateLimiterFactory
            ->method('create')
            ->with('test@example.com')
            ->willReturn($limiter);

        $limiter
            ->method('consume')
            ->with(1)
            ->willReturn($limit);

        $limit
            ->method('isAccepted')
            ->willReturn(false);

        $retryAfter = new \DateTimeImmutable('+60 seconds');
        $limit
            ->method('getRetryAfter')
            ->willReturn($retryAfter);

        $this->expectException(TooManyRequestsHttpException::class);
        $this->expectExceptionMessage('Vote rate limit exceeded');

        $this->processor->process($vote, $this->operation);
    }

    public function testProcessThrowsExceptionForInvalidVoteValue(): void
    {
        $vote = new Vote();
        $vote->setValue(0); // Invalid value
        $user = new User();
        $user->setEmail('test@example.com');

        $this->setupValidRateLimit($user);

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('value must be -1 or 1');

        $this->processor->process($vote, $this->operation);
    }

    public function testProcessThrowsExceptionForVoteValueTwo(): void
    {
        $vote = new Vote();
        $vote->setValue(2); // Invalid value
        $user = new User();
        $user->setEmail('test@example.com');

        $this->setupValidRateLimit($user);

        $this->expectException(BadRequestHttpException::class);
        $this->expectExceptionMessage('value must be -1 or 1');

        $this->processor->process($vote, $this->operation);
    }

    public function testProcessWithValidUpvote(): void
    {
        $vote = new Vote();
        $vote->setValue(1);
        $comment = new Comment();
        $vote->setComment($comment);

        $user = new User();
        $user->setEmail('test@example.com');

        $this->setupValidRateLimit($user);

        $this->voteRepository
            ->method('findOneBy')
            ->with(['comment' => $comment, 'voter' => $user])
            ->willReturn(null);

        $expectedResult = $vote;
        $this->persistProcessor
            ->expects($this->once())
            ->method('process')
            ->with($vote, $this->operation, [], [])
            ->willReturn($expectedResult);

        $result = $this->processor->process($vote, $this->operation);

        $this->assertSame($expectedResult, $result);
        $this->assertSame($user, $vote->getVoter());
    }

    public function testProcessWithValidDownvote(): void
    {
        $vote = new Vote();
        $vote->setValue(-1);
        $comment = new Comment();
        $vote->setComment($comment);

        $user = new User();
        $user->setEmail('test@example.com');

        $this->setupValidRateLimit($user);

        $this->voteRepository
            ->method('findOneBy')
            ->with(['comment' => $comment, 'voter' => $user])
            ->willReturn(null);

        $expectedResult = $vote;
        $this->persistProcessor
            ->expects($this->once())
            ->method('process')
            ->with($vote, $this->operation, [], [])
            ->willReturn($expectedResult);

        $result = $this->processor->process($vote, $this->operation);

        $this->assertSame($expectedResult, $result);
        $this->assertSame($user, $vote->getVoter());
        $this->assertSame(-1, $vote->getValue());
    }

    public function testProcessUpdatesExistingVote(): void
    {
        $newVote = new Vote();
        $newVote->setValue(1);
        $comment = new Comment();
        $newVote->setComment($comment);

        $user = new User();
        $user->setEmail('test@example.com');

        $existingVote = new Vote();
        $existingVote->setValue(-1);
        $existingVote->setComment($comment);
        $existingVote->setVoter($user);

        $this->setupValidRateLimit($user);

        $this->voteRepository
            ->method('findOneBy')
            ->with(['comment' => $comment, 'voter' => $user])
            ->willReturn($existingVote);

        $expectedResult = $existingVote;
        $this->persistProcessor
            ->expects($this->once())
            ->method('process')
            ->with($existingVote, $this->operation, [], [])
            ->willReturn($expectedResult);

        $result = $this->processor->process($newVote, $this->operation);

        $this->assertSame($expectedResult, $result);
        $this->assertSame(1, $existingVote->getValue()); // Updated value
        $this->assertSame($user, $newVote->getVoter()); // Voter set on new vote
    }

    public function testProcessDoesNotUpdateWhenExistingVoteIsSameInstance(): void
    {
        $vote = new Vote();
        $vote->setValue(1);
        $comment = new Comment();
        $vote->setComment($comment);

        $user = new User();
        $user->setEmail('test@example.com');

        $this->setupValidRateLimit($user);

        // Return the same instance as existing vote
        $this->voteRepository
            ->method('findOneBy')
            ->with(['comment' => $comment, 'voter' => $user])
            ->willReturn($vote);

        $expectedResult = $vote;
        $this->persistProcessor
            ->expects($this->once())
            ->method('process')
            ->with($vote, $this->operation, [], [])
            ->willReturn($expectedResult);

        $result = $this->processor->process($vote, $this->operation);

        $this->assertSame($expectedResult, $result);
    }

    public function testProcessHandlesUriVariablesAndContext(): void
    {
        $vote = new Vote();
        $vote->setValue(1);
        $comment = new Comment();
        $vote->setComment($comment);

        $user = new User();
        $user->setEmail('test@example.com');

        $uriVariables = ['id' => 123];
        $context = ['some' => 'context'];

        $this->setupValidRateLimit($user);

        $this->voteRepository
            ->method('findOneBy')
            ->willReturn(null);

        $this->persistProcessor
            ->expects($this->once())
            ->method('process')
            ->with($vote, $this->operation, $uriVariables, $context)
            ->willReturn($vote);

        $result = $this->processor->process($vote, $this->operation, $uriVariables, $context);

        $this->assertSame($vote, $result);
    }

    public function testProcessSetsVoterOnVote(): void
    {
        $vote = new Vote();
        $vote->setValue(1);
        $comment = new Comment();
        $vote->setComment($comment);

        $user = new User();
        $user->setEmail('voter@example.com');

        $this->setupValidRateLimit($user);

        $this->voteRepository
            ->method('findOneBy')
            ->willReturn(null);

        $this->persistProcessor
            ->method('process')
            ->willReturn($vote);

        $result = $this->processor->process($vote, $this->operation);

        $this->assertSame($user, $vote->getVoter());
    }

    public function testRateLimitWithRetryAfterNull(): void
    {
        $vote = new Vote();
        $user = new User();
        $user->setEmail('test@example.com');

        $limiter = $this->createMock(LimiterInterface::class);
        $limit = $this->createMock(Limit::class);

        $this->security
            ->method('getUser')
            ->willReturn($user);

        $this->rateLimiterFactory
            ->method('create')
            ->with('test@example.com')
            ->willReturn($limiter);

        $limiter
            ->method('consume')
            ->willReturn($limit);

        $limit
            ->method('isAccepted')
            ->willReturn(false);

        $limit
            ->method('getRetryAfter')
            ->willReturn(null);

        $this->expectException(TooManyRequestsHttpException::class);

        $this->processor->process($vote, $this->operation);
    }

    private function setupValidRateLimit(User $user): void
    {
        $limiter = $this->createMock(LimiterInterface::class);
        $limit = $this->createMock(Limit::class);

        $this->security
            ->method('getUser')
            ->willReturn($user);

        $this->rateLimiterFactory
            ->method('create')
            ->with($user->getUserIdentifier())
            ->willReturn($limiter);

        $limiter
            ->method('consume')
            ->with(1)
            ->willReturn($limit);

        $limit
            ->method('isAccepted')
            ->willReturn(true);
    }

    public function testCompleteVotingWorkflow(): void
    {
        // Test complete workflow: user votes, then changes vote
        $comment = new Comment();
        $user = new User();
        $user->setEmail('workflow@example.com');

        // First vote (upvote)
        $firstVote = new Vote();
        $firstVote->setValue(1);
        $firstVote->setComment($comment);

        $this->setupValidRateLimit($user);

        $this->voteRepository
            ->expects($this->exactly(2))
            ->method('findOneBy')
            ->with(['comment' => $comment, 'voter' => $user])
            ->willReturnOnConsecutiveCalls(null, $firstVote);

        $this->persistProcessor
            ->expects($this->exactly(2))
            ->method('process')
            ->willReturnOnConsecutiveCalls($firstVote, $firstVote);

        // Process first vote
        $result1 = $this->processor->process($firstVote, $this->operation);
        $this->assertSame($firstVote, $result1);
        $this->assertSame(1, $firstVote->getValue());

        // Second vote (change to downvote)
        $secondVote = new Vote();
        $secondVote->setValue(-1);
        $secondVote->setComment($comment);

        $result2 = $this->processor->process($secondVote, $this->operation);
        $this->assertSame($firstVote, $result2); // Returns existing vote
        $this->assertSame(-1, $firstVote->getValue()); // Value updated
    }
}
