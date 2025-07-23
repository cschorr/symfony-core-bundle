<?php

declare(strict_types=1);

namespace App\Controller;

use App\Enum\Environment;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\Routing\Annotation\Route;

class SentryTestController extends AbstractController
{
    public function __construct(
        private readonly LoggerInterface $logger,
    ) {
    }

    #[Route(path: '/_sentry-test', name: 'sentry_test')]
    public function testLog()
    {
        // Restrict access to development and staging environments
        $environment = $this->getParameter('kernel.environment');
        if (Environment::DEV !== $environment && Environment::STAGE !== $environment) {
            throw $this->createAccessDeniedException('This endpoint is not accessible in the current environment.');
        }

        // the following code will test if monolog integration logs to sentry
        $this->logger->error('My custom logged error.');

        // the following code will test if an uncaught exception logs to sentry
        throw new \RuntimeException('Example exception.');
    }
}
