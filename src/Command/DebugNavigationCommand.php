<?php

namespace App\Command;

use App\Entity\User;
use App\Service\NavigationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:debug:navigation',
    description: 'Debug navigation for demo user',
)]
class DebugNavigationCommand extends Command
{
    public function __construct(
        private NavigationService $navigationService,
        private EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        // Get demo user
        $user = $this->entityManager->getRepository(User::class)
            ->findOneBy(['email' => 'demo@example.com']);

        if (!$user) {
            $io->error('Demo user not found');
            return Command::FAILURE;
        }

        $io->title('Navigation Debug for demo@example.com');

        // Check if user is admin
        $isAdmin = $this->navigationService->isUserAdmin($user);
        $io->info(sprintf('Is Admin: %s', $isAdmin ? 'Yes' : 'No'));

        // Get accessible system entities
        if ($isAdmin) {
            $systemEntities = $this->navigationService->getAllActiveSystemEntities();
            $io->info('Getting ALL active system entities (admin mode)');
        } else {
            $systemEntities = $this->navigationService->getAccessibleSystemEntitiesForUser($user);
            $io->info('Getting accessible system entities for user');
        }

        $io->info(sprintf('Found %d system entities', count($systemEntities)));

        // Debug: Check user ID and permissions
        $io->section('User Details');
        $io->writeln(sprintf('User ID: %s', $user->getId()));
        $io->writeln(sprintf('User Email: %s', $user->getEmail()));
        $io->writeln(sprintf('Permissions Count: %d', $user->getSystemEntityPermissions()->count()));

        // Show user permissions
        $io->section('User Permissions');
        foreach ($user->getSystemEntityPermissions() as $permission) {
            $io->writeln(sprintf(
                'SystemEntity: %s (%s), Read: %s, Write: %s, Active: %s',
                $permission->getSystemEntity()->getName(),
                $permission->getSystemEntity()->getCode(),
                $permission->canRead() ? 'Yes' : 'No',
                $permission->canWrite() ? 'Yes' : 'No',
                $permission->getSystemEntity()->isActive() ? 'Yes' : 'No'
            ));
        }

        // Test repository method directly
        $io->section('Repository Test');
        $systemEntityRepo = $this->entityManager->getRepository(\App\Entity\SystemEntity::class);
        
        // Test the original method first
        $allUserSystemEntities = $systemEntityRepo->findSystemEntitiesForUser($user);
        $io->writeln(sprintf('findSystemEntitiesForUser returned %d system entities', count($allUserSystemEntities)));
        
        $testSystemEntities = $systemEntityRepo->findActiveSystemEntitiesForUser($user);
        $io->writeln(sprintf('findActiveSystemEntitiesForUser returned %d system entities', count($testSystemEntities)));

        // Debug SQL queries
        $io->section('SQL Debug');
        
        // Test the findActiveSystemEntitiesForUser query
        $queryBuilder = $systemEntityRepo->createQueryBuilder('se')
            ->leftJoin('se.userPermissions', 'up')
            ->where('se.active = :active')
            ->andWhere('(up.user = :user AND (up.canRead = :canRead OR up.canWrite = :canWrite))')
            ->setParameter('active', true)
            ->setParameter('user', $user)
            ->setParameter('canRead', true)
            ->setParameter('canWrite', true);
            
        $sql = $queryBuilder->getQuery()->getSQL();
        $io->writeln('Generated SQL:');
        $io->writeln($sql);
        
        $results = $queryBuilder->getQuery()->getResult();
        $io->writeln(sprintf('Query returned %d system entities', count($results)));        
        $io->writeln('Parameters:');
        foreach ($queryBuilder->getQuery()->getParameters() as $param) {
            $io->writeln(sprintf('  %s => %s', $param->getName(), $param->getValue()));
        }

        // Show details of all user system entities
        if (!empty($allUserSystemEntities)) {
            $io->writeln('All user system entities:');
            foreach ($allUserSystemEntities as $systemEntity) {
                $io->writeln(sprintf('  - %s (%s) - Active: %s', 
                    $systemEntity->getName(), 
                    $systemEntity->getCode(), 
                    $systemEntity->isActive() ? 'Yes' : 'No'
                ));
            }
        }

        // Show system entities
        $rows = [];
        foreach ($systemEntities as $systemEntity) {
            $rows[] = [
                $systemEntity->getCode(),
                $systemEntity->getName(),
                $systemEntity->isActive() ? 'Yes' : 'No'
            ];
        }

        $io->table(['Code', 'Name', 'Active'], $rows);

        // Check entity mapping
        $entityMapping = $this->navigationService->getSystemEntityEntityMapping();
        $io->section('Entity Mapping');
        foreach ($entityMapping as $code => $class) {
            $io->writeln(sprintf('%s => %s', $code, $class));
        }

        // Check which system entities would appear in navigation
        $io->section('Navigation Items');
        $navigationCount = 0;
        foreach ($systemEntities as $systemEntity) {
            $systemEntityCode = $systemEntity->getCode();
            if (isset($entityMapping[$systemEntityCode])) {
                $navigationCount++;
                $io->writeln(sprintf('✓ %s (%s)', $systemEntity->getName(), $systemEntityCode));
            } else {
                $io->writeln(sprintf('✗ %s (%s) - No entity mapping', $systemEntity->getName(), $systemEntityCode));
            }
        }

        $io->success(sprintf('Would show %d navigation items', $navigationCount));

        return Command::SUCCESS;
    }
}
