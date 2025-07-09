<?php

namespace App\Tests\Controller\Admin;

use App\Controller\Admin\CompanyCrudController;
use App\Entity\Company;
use App\Entity\User;
use App\Entity\Project;
use App\Service\PermissionService;
use App\Service\DuplicateService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Contracts\Translation\TranslatorInterface;
use ReflectionMethod;

class CompanyCrudControllerTest extends TestCase
{
    private CompanyCrudController $controller;

    protected function setUp(): void
    {
        $entityManager = $this->createMock(EntityManagerInterface::class);
        $translator = $this->createMock(TranslatorInterface::class);
        $permissionService = $this->createMock(PermissionService::class);
        $duplicateService = $this->createMock(DuplicateService::class);
        $requestStack = $this->createMock(RequestStack::class);

        $this->controller = new CompanyCrudController(
            $entityManager,
            $translator,
            $permissionService,
            $duplicateService,
            $requestStack
        );
    }

    private function callProtectedMethod($methodName, $entity)
    {
        $reflection = new ReflectionMethod($this->controller, $methodName);
        $reflection->setAccessible(true);
        return $reflection->invoke($this->controller, $entity);
    }

    public function testCanDeleteEntityWithNoRelatedRecords(): void
    {
        $company = new Company();
        
        $result = $this->callProtectedMethod('canDeleteEntity', $company);
        
        $this->assertTrue($result);
    }

    public function testCanDeleteEntityWithEmployees(): void
    {
        $company = new Company();
        $user = new User();
        $company->addEmployee($user);
        
        $result = $this->callProtectedMethod('canDeleteEntity', $company);
        
        $this->assertFalse($result);
    }

    public function testCanDeleteEntityWithProjects(): void
    {
        $company = new Company();
        $project = new Project();
        $company->addProject($project);
        
        $result = $this->callProtectedMethod('canDeleteEntity', $company);
        
        $this->assertFalse($result);
    }

    public function testCanDeleteEntityWithBothEmployeesAndProjects(): void
    {
        $company = new Company();
        $user = new User();
        $project = new Project();
        $company->addEmployee($user);
        $company->addProject($project);
        
        $result = $this->callProtectedMethod('canDeleteEntity', $company);
        
        $this->assertFalse($result);
    }

    public function testCanDeleteEntityWithNonCompanyEntity(): void
    {
        $user = new User();
        
        $result = $this->callProtectedMethod('canDeleteEntity', $user);
        
        $this->assertTrue($result);
    }
}
