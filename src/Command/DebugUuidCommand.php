<?php

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Uid\Uuid;

#[AsCommand(
    name: 'debug:uuid',
    description: 'Debug UUID generation and storage',
)]
class DebugUuidCommand extends Command
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $hasher
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('UUID and Doctrine QueryBuilder Debug');

        // Get the demo user and a SystemEntity
        $userRepo = $this->entityManager->getRepository(User::class);
        $systemEntityRepo = $this->entityManager->getRepository(\App\Entity\SystemEntity::class);
        $permissionRepo = $this->entityManager->getRepository(\App\Entity\UserSystemEntityPermission::class);

        $demoUser = $userRepo->findOneBy(['email' => 'demo@example.com']);
        $companyEntity = $systemEntityRepo->findOneBy(['code' => 'Company']);

        if (!$demoUser || !$companyEntity) {
            $io->error('Demo user or Company entity not found');
            return Command::FAILURE;
        }

        $io->writeln("Demo User ID: " . $demoUser->getId());
        $io->writeln("Company Entity ID: " . $companyEntity->getId());

        // Test 1: Direct SQL approach (what's working)
        $conn = $this->entityManager->getConnection();
        $sql = "SELECT COUNT(*) as count FROM user_system_entity_permission WHERE user_id = UNHEX(REPLACE(?, '-', '')) AND system_entity_id = UNHEX(REPLACE(?, '-', ''))";
        $result = $conn->executeQuery($sql, [$demoUser->getId(), $companyEntity->getId()]);
        $directSqlCount = $result->fetchOne();
        $io->writeln("\n1. Direct SQL with UNHEX approach: Found $directSqlCount records");

        // Test 2: QueryBuilder with entity parameters (what was failing)
        $qb = $permissionRepo->createQueryBuilder('usep')
            ->select('COUNT(usep.id)')
            ->andWhere('usep.user = :user')
            ->andWhere('usep.systemEntity = :systemEntity')
            ->setParameter('user', $demoUser)
            ->setParameter('systemEntity', $companyEntity);
        
        $doctrineCount = $qb->getQuery()->getSingleScalarResult();
        $io->writeln("2. Doctrine QueryBuilder with entities: Found $doctrineCount records");

        // Test 3: QueryBuilder with UUID string parameters
        $qb2 = $permissionRepo->createQueryBuilder('usep')
            ->select('COUNT(usep.id)')
            ->andWhere('usep.user = :userId')
            ->andWhere('usep.systemEntity = :systemEntityId')
            ->setParameter('userId', $demoUser->getId())
            ->setParameter('systemEntityId', $companyEntity->getId());
        
        $doctrineStringCount = $qb2->getQuery()->getSingleScalarResult();
        $io->writeln("3. Doctrine QueryBuilder with UUID strings: Found $doctrineStringCount records");

        // Test 4: Let's see the actual SQL generated
        $sql = $qb->getQuery()->getSQL();
        $io->writeln("\n4. Generated SQL from QueryBuilder:");
        $io->writeln($sql);

        // Test 5: Check entity state
        $io->writeln("\n5. Entity state check:");
        $io->writeln("User is managed: " . ($this->entityManager->contains($demoUser) ? 'YES' : 'NO'));
        $io->writeln("SystemEntity is managed: " . ($this->entityManager->contains($companyEntity) ? 'YES' : 'NO'));

        // Test 6: Try with explicit binary parameter binding
        $qb3 = $permissionRepo->createQueryBuilder('usep')
            ->select('COUNT(usep.id)')
            ->andWhere('usep.user = UNHEX(REPLACE(:userId, \'-\', \'\'))')
            ->andWhere('usep.systemEntity = UNHEX(REPLACE(:systemEntityId, \'-\', \'\'))')
            ->setParameter('userId', $demoUser->getId()->__toString())
            ->setParameter('systemEntityId', $companyEntity->getId()->__toString());
        
        try {
            $doctrineBinaryCount = $qb3->getQuery()->getSingleScalarResult();
            $io->writeln("6. Doctrine QueryBuilder with UNHEX in DQL: Found $doctrineBinaryCount records");
        } catch (\Exception $e) {
            $io->writeln("6. Doctrine QueryBuilder with UNHEX in DQL: ERROR - " . $e->getMessage());
        }

        // Test 7: Check the actual parameter values being bound
        $io->writeln("\n7. Parameter binding debug:");
        $io->writeln("User ID as string: " . $demoUser->getId()->__toString());
        $io->writeln("User ID __toString(): " . $demoUser->getId());
        $io->writeln("SystemEntity ID as string: " . $companyEntity->getId()->__toString());

        return Command::SUCCESS;
    }
}
