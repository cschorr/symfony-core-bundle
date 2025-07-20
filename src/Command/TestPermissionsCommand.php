<?php

namespace App\Command;

use App\Entity\User;
use App\Repository\UserRepository;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

#[AsCommand(
    name: 'test:permissions',
    description: 'Test permissions for demo user',
)]
class TestPermissionsCommand extends Command
{
    public function __construct(
        private UserRepository $userRepository,
        private AuthorizationCheckerInterface $authorizationChecker,
        private TokenStorageInterface $tokenStorage
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $demoUser = $this->userRepository->findOneBy(['email' => 'demo@example.com']);
        
        if (!$demoUser) {
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

        $io->table(
            ['Resource', 'Permission', 'Granted'],
            [
                ['Company', 'read', $canReadCompany ? 'YES' : 'NO'],
                ['Company', 'write', $canWriteCompany ? 'YES' : 'NO'],
            ]
        );

        if ($canReadCompany && $canWriteCompany) {
            $io->success('Demo user has correct Company permissions');
            return Command::SUCCESS;
        } else {
            $io->error('Demo user is missing Company permissions');
            return Command::FAILURE;
        }
    }
}
