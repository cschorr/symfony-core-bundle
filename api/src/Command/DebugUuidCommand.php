<?php

declare(strict_types=1);

namespace App\Command;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Uid\Uuid;

#[AsCommand(
    name: 'debug:uuid',
    description: 'Debug UUID generation and storage',
)]
class DebugUuidCommand
{
    public function __construct(private readonly EntityManagerInterface $entityManager, private readonly UserPasswordHasherInterface $hasher)
    {
    }

    public function __invoke(OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $io->title('UUID and Doctrine QueryBuilder Debug');

        // Get the demo user and a DomainEntityPermission
        $userRepo = $this->entityManager->getRepository(User::class);
        $systemEntityRepo = $this->entityManager->getRepository(\App\Entity\DomainEntityPermission::class);
        $permissionRepo = $this->entityManager->getRepository(\App\Entity\UserSystemEntityPermission::class);

        $demoUser = $userRepo->findOneBy(['email' => 'demo@example.com']);
        $companyEntity = $systemEntityRepo->findOneBy(['code' => 'Company']);

        if (!$demoUser || !$companyEntity) {
            $io->error('Demo user or Company entity not found');

            return Command::FAILURE;
        }

        $io->writeln('Demo User ID: ' . $demoUser->getId());
        $io->writeln('Company Entity ID: ' . $companyEntity->getId());

        // Test 1: Direct SQL approach (what's working)
        $conn = $this->entityManager->getConnection();
        $sql = "SELECT COUNT(*) as count FROM user_system_entity_permission WHERE user_id = UNHEX(REPLACE(?, '-', '')) AND system_entity_id = UNHEX(REPLACE(?, '-', ''))";
        $result = $conn->executeQuery($sql, [$demoUser->getId(), $companyEntity->getId()]);
        $directSqlCount = $result->fetchOne();
        $io->writeln("\n1. Direct SQL with UNHEX approach: Found {$directSqlCount} records");

        // Test 2: QueryBuilder with entity parameters (what was failing)
        $qb = $permissionRepo->createQueryBuilder('usep')
            ->select('COUNT(usep.id)')
            ->andWhere('usep.user = :user')
            ->andWhere('usep.domainEntityPermission = :systemEntity')
            ->setParameter('user', $demoUser)
            ->setParameter('systemEntity', $companyEntity);

        $doctrineCount = $qb->getQuery()->getSingleScalarResult();
        $io->writeln(sprintf('2. Doctrine QueryBuilder with entities: Found %s records', $doctrineCount));

        // Test 3: QueryBuilder with UUID parameters using 'uuid' type (our clean solution)
        $qb2 = $permissionRepo->createQueryBuilder('usep')
            ->select('COUNT(usep.id)')
            ->andWhere('usep.user = :userId')
            ->andWhere('usep.domainEntityPermission = :systemEntityId')
            ->setParameter('userId', $demoUser->getId(), 'uuid')
            ->setParameter('systemEntityId', $companyEntity->getId(), 'uuid');

        $doctrineStringCount = $qb2->getQuery()->getSingleScalarResult();
        $io->writeln(sprintf("3. Doctrine QueryBuilder with 'uuid' parameter type (CLEAN SOLUTION): Found %s records", $doctrineStringCount));

        // Test 4: Let's see the actual SQL generated
        $sql = $qb->getQuery()->getSQL();
        $io->writeln("\n4. Generated SQL from QueryBuilder:");
        $io->writeln($sql);

        // Test 5: Check entity state
        $io->writeln("\n5. Entity state check:");
        $io->writeln('User is managed: ' . ($this->entityManager->contains($demoUser) ? 'YES' : 'NO'));
        $io->writeln('DomainEntityPermission is managed: ' . ($this->entityManager->contains($companyEntity) ? 'YES' : 'NO'));

        // Test 6: Try with explicit binary parameter binding
        $qb3 = $permissionRepo->createQueryBuilder('usep')
            ->select('COUNT(usep.id)')
            ->andWhere("usep.user = UNHEX(REPLACE(:userId, '-', ''))")
            ->andWhere("usep.domainEntityPermission = UNHEX(REPLACE(:systemEntityId, '-', ''))")
            ->setParameter('userId', $demoUser->getId()->__toString())
            ->setParameter('systemEntityId', $companyEntity->getId()->__toString());

        try {
            $doctrineBinaryCount = $qb3->getQuery()->getSingleScalarResult();
            $io->writeln(sprintf('6. Doctrine QueryBuilder with UNHEX in DQL: Found %s records', $doctrineBinaryCount));
        } catch (\Exception $exception) {
            $io->writeln('6. Doctrine QueryBuilder with UNHEX in DQL: ERROR - ' . $exception->getMessage());
        }

        // Test 7: Check the actual parameter values being bound
        $io->writeln("\n7. Parameter binding debug:");
        $io->writeln('User ID as string: ' . $demoUser->getId()->__toString());
        $io->writeln('User ID __toString(): ' . $demoUser->getId());
        $io->writeln('DomainEntityPermission ID as string: ' . $companyEntity->getId()->__toString());

        // Test 8: Test our repository methods that use the clean UUID solution
        $io->writeln("\n8. Testing our fixed repository methods:");

        // Test UserSystemEntityPermissionRepository
        $userPermissionRepo = $this->entityManager->getRepository(\App\Entity\UserSystemEntityPermission::class);
        $readPermissions = $userPermissionRepo->findBy(['user' => $demoUser, 'canRead' => true]);
        $writePermissions = $userPermissionRepo->findBy(['user' => $demoUser, 'canWrite' => true]);
        $io->writeln('UserSystemEntityPermissionRepository READ permissions: ' . count($readPermissions));
        $io->writeln('UserSystemEntityPermissionRepository WRITE permissions: ' . count($writePermissions));

        // Test DomainEntityRepository
        $systemEntityRepo = $this->entityManager->getRepository(\App\Entity\DomainEntityPermission::class);
        $readableEntities = $systemEntityRepo->findReadableByUser($demoUser);
        $io->writeln('DomainEntityRepository->findReadableByUser: Found ' . count($readableEntities) . ' entities');

        $writableEntities = $systemEntityRepo->findWritableByUser($demoUser);
        $io->writeln('DomainEntityRepository->findWritableByUser: Found ' . count($writableEntities) . ' entities');

        return Command::SUCCESS;
    }
}
