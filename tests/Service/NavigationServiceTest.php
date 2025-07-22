<?php

namespace App\Tests\Service;

use App\Entity\SystemEntity;
use App\Entity\User;
use App\Entity\UserSystemEntityPermission;
use App\Repository\SystemEntityRepository;
use App\Service\NavigationService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class NavigationServiceTest extends TestCase
{
    private NavigationService $navigationService;
    private SystemEntityRepository $systemEntityRepository;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->systemEntityRepository = $this->createMock(SystemEntityRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        
        $this->navigationService = new NavigationService(
            $this->entityManager,
            $this->systemEntityRepository
        );
    }

    public function testIsUserAdmin(): void
    {
        $adminUser = new User();
        $adminUser->setRoles(['ROLE_ADMIN']);
        
        $regularUser = new User();
        $regularUser->setRoles(['ROLE_USER']);
        
        $this->assertTrue($this->navigationService->isUserAdmin($adminUser));
        $this->assertFalse($this->navigationService->isUserAdmin($regularUser));
    }

    public function testGetSystemEntityEntityMapping(): void
    {
        $mapping = $this->navigationService->getSystemEntityEntityMapping();
        
        $this->assertIsArray($mapping);
        $this->assertArrayHasKey('SystemEntity', $mapping);
        $this->assertArrayHasKey('User', $mapping);
        $this->assertArrayHasKey('Company', $mapping);
        $this->assertArrayHasKey('CompanyGroup', $mapping);
        $this->assertEquals(\App\Entity\SystemEntity::class, $mapping['SystemEntity']);
        $this->assertEquals(\App\Entity\User::class, $mapping['User']);
    }

    public function testGetSystemEntityIconMapping(): void
    {
        $mapping = $this->navigationService->getSystemEntityIconMapping();
        
        $this->assertIsArray($mapping);
        $this->assertArrayHasKey('SystemEntity', $mapping);
        $this->assertArrayHasKey('User', $mapping);
        $this->assertArrayHasKey('Company', $mapping);
        $this->assertArrayHasKey('CompanyGroup', $mapping);
        $this->assertStringContainsString('fa', $mapping['SystemEntity']);
    }
}
