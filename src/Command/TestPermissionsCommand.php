<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;

#[AsCommand(
    name: 'test:permissions',
    description: 'Test permissions for demo user',
)]
class TestPermissionsCommand
{
    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly AuthorizationCheckerInterface $authorizationChecker,
        private readonly TokenStorageInterface $tokenStorage,
    ) {
    }

    public function __invoke(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $demoUser = $this->userRepository->findOneBy(['email' => 'demo@example.com']);

        if (null === $demoUser) {
            $io->error('Demo user not found');

            return Command::FAILURE;
        }

        // Create a token for the demo user
        $token = new UsernamePasswordToken($demoUser, 'main', $demoUser->getRoles());
        $this->tokenStorage->setToken($token);

        $io->title('Testing permissions for demo@example.com');

        // Test Company permissions
        $canReadCompany = $this->authorizationChecker->isGranted('read', 'Company');
        $canWriteCompany = $this->authorizationChecker->isGranted('write', 'Company');

        // Test User permissions
        $canReadUser = $this->authorizationChecker->isGranted('read', 'User');
        $canWriteUser = $this->authorizationChecker->isGranted('write', 'User');

        $io->table(
            ['Resource', 'Permission', 'Granted'],
            [
                ['Company', 'read', $canReadCompany ? 'YES' : 'NO'],
                ['Company', 'write', $canWriteCompany ? 'YES' : 'NO'],
                ['User', 'read', $canReadUser ? 'YES' : 'NO'],
                ['User', 'write', $canWriteUser ? 'YES' : 'NO'],
            ]
        );

        if ($canReadCompany && $canWriteCompany) {
            $io->success('Demo user has correct Company permissions');

            return Command::SUCCESS;
        }

        $io->error('Demo user is missing Company permissions');

        return Command::FAILURE;
    }
}
