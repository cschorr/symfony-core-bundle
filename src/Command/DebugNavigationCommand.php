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

        // Get accessible modules
        if ($isAdmin) {
            $modules = $this->navigationService->getAllActiveModules();
            $io->info('Getting ALL active modules (admin mode)');
        } else {
            $modules = $this->navigationService->getAccessibleModulesForUser($user);
            $io->info('Getting accessible modules for user');
        }

        $io->info(sprintf('Found %d modules', count($modules)));

        // Debug: Check user ID and permissions
        $io->section('User Details');
        $io->writeln(sprintf('User ID: %s', $user->getId()));
        $io->writeln(sprintf('User Email: %s', $user->getEmail()));
        $io->writeln(sprintf('Permissions Count: %d', $user->getModulePermissions()->count()));

        // Show user permissions
        $io->section('User Permissions');
        foreach ($user->getModulePermissions() as $permission) {
            $io->writeln(sprintf(
                'Module: %s (%s), Read: %s, Write: %s, Active: %s',
                $permission->getModule()->getName(),
                $permission->getModule()->getCode(),
                $permission->canRead() ? 'Yes' : 'No',
                $permission->canWrite() ? 'Yes' : 'No',
                $permission->getModule()->isActive() ? 'Yes' : 'No'
            ));
        }

        // Test repository method directly
        $io->section('Repository Test');
        $moduleRepo = $this->entityManager->getRepository(\App\Entity\Module::class);
        
        // Test the original method first
        $allUserModules = $moduleRepo->findModulesForUser($user);
        $io->writeln(sprintf('findModulesForUser returned %d modules', count($allUserModules)));
        
        $testModules = $moduleRepo->findActiveModulesForUser($user);
        $io->writeln(sprintf('findActiveModulesForUser returned %d modules', count($testModules)));

        // Debug SQL queries
        $io->section('SQL Debug');
        
        // Test the findActiveModulesForUser query
        $queryBuilder = $moduleRepo->createQueryBuilder('m')
            ->join('m.userPermissions', 'ump')
            ->andWhere('ump.user = :user')
            ->andWhere('(ump.canRead = true OR ump.canWrite = true)')
            ->andWhere('m.active = true')
            ->setParameter('user', $user)
            ->orderBy('m.name', 'ASC');
            
        $query = $queryBuilder->getQuery();
        $io->writeln('Generated SQL:');
        $io->writeln($query->getSQL());
        
        $io->writeln('Parameters:');
        foreach ($query->getParameters() as $param) {
            $io->writeln(sprintf('  %s => %s', $param->getName(), $param->getValue()));
        }

        // Show details of all user modules
        if (!empty($allUserModules)) {
            $io->writeln('All user modules:');
            foreach ($allUserModules as $module) {
                $io->writeln(sprintf('  - %s (%s) - Active: %s', 
                    $module->getName(), 
                    $module->getCode(), 
                    $module->isActive() ? 'Yes' : 'No'
                ));
            }
        }

        // Show modules
        $rows = [];
        foreach ($modules as $module) {
            $rows[] = [
                $module->getCode(),
                $module->getName(),
                $module->isActive() ? 'Yes' : 'No'
            ];
        }

        $io->table(['Code', 'Name', 'Active'], $rows);

        // Check entity mapping (dynamic generation)
        $io->section('Entity Mapping (Dynamic)');
        foreach ($modules as $module) {
            $entityClass = $this->navigationService->getEntityClassFromModule($module);
            $io->writeln(sprintf('%s => %s', $module->getCode(), $entityClass));
        }

        // Check which modules would appear in navigation
        $io->section('Navigation Items');
        $navigationCount = 0;
        foreach ($modules as $module) {
            $navigationCount++;
            $io->writeln(sprintf('✓ %s (%s)', $module->getName(), $module->getCode()));
        }

        $io->success(sprintf('Would show %d navigation items', $navigationCount));

        return Command::SUCCESS;
    }
}
