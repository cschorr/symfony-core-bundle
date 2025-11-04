<?php

declare(strict_types=1);

namespace C3net\CoreBundle\Command;

use C3net\CoreBundle\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:test-password-change',
    description: 'Test password change notification by changing admin password',
)]
class TestPasswordChangeCommand
{
    public function __construct(private readonly EntityManagerInterface $entityManager, private readonly UserPasswordHasherInterface $passwordHasher)
    {
    }

    public function __invoke(\Symfony\Component\Console\Style\SymfonyStyle $io): int
    {
        $io->title('Testing Password Change Notification');
        // Find admin user
        $userRepository = $this->entityManager->getRepository(User::class);
        $user = $userRepository->findOneBy(['email' => 'admin@example.com']);
        if ($user === null) {
            $io->error('Admin user not found');

            return Command::FAILURE;
        }
        $io->info(sprintf('Found user: %s', $user->getEmail()));
        // Generate a unique password for testing
        $newPassword = 'TestPass' . time() . '!';
        $io->info(sprintf('Changing password to: %s', $newPassword));
        // Hash and set the new password
        $hashedPassword = $this->passwordHasher->hashPassword($user, $newPassword);
        $user->setPassword($hashedPassword);
        // Flush changes - this should trigger the postUpdate event and PasswordChangedEventListener
        $this->entityManager->flush();
        $io->success('Password changed successfully!');
        $io->info('Check Mailpit at http://localhost:8025 for the notification email');
        $io->info('Check logs for debug output from PasswordChangedEventListener');
        return Command::SUCCESS;
    }
}
