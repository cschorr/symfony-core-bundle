<?php

namespace App\Tests\Service;

use App\Entity\Module;
use App\Entity\User;
use App\Entity\UserModulePermission;
use App\Repository\ModuleRepository;
use App\Service\NavigationService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class NavigationServiceTest extends TestCase
{
    private NavigationService $navigationService;
    private ModuleRepository $moduleRepository;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->moduleRepository = $this->createMock(ModuleRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        
        $this->navigationService = new NavigationService(
            $this->moduleRepository,
            $this->entityManager
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

    public function testGetModuleEntityMapping(): void
    {
        $mapping = $this->navigationService->getModuleEntityMapping();
        
        $this->assertIsArray($mapping);
        $this->assertArrayHasKey('Module', $mapping);
        $this->assertArrayHasKey('User', $mapping);
        $this->assertArrayHasKey('Company', $mapping);
        $this->assertArrayHasKey('CompanyGroup', $mapping);
        $this->assertEquals(\App\Entity\Module::class, $mapping['Module']);
        $this->assertEquals(\App\Entity\User::class, $mapping['User']);
    }

    public function testGetModuleIconMapping(): void
    {
        $mapping = $this->navigationService->getModuleIconMapping();
        
        $this->assertIsArray($mapping);
        $this->assertArrayHasKey('Module', $mapping);
        $this->assertArrayHasKey('User', $mapping);
        $this->assertArrayHasKey('Company', $mapping);
        $this->assertArrayHasKey('CompanyGroup', $mapping);
        $this->assertStringContainsString('fa', $mapping['Module']);
    }
}
