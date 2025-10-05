<?php

declare(strict_types=1);

namespace C3net\CoreBundle\Tests\Unit\Entity;

use C3net\CoreBundle\Entity\Category;
use C3net\CoreBundle\Entity\Company;
use C3net\CoreBundle\Entity\CompanyGroup;
use C3net\CoreBundle\Entity\Project;
use C3net\CoreBundle\Entity\User;
use PHPUnit\Framework\TestCase;

class CompanyTest extends TestCase
{
    private Company $company;

    protected function setUp(): void
    {
        $this->company = new Company();
    }

    public function testConstructor(): void
    {
        $company = new Company();

        // Test that collections are initialized
        $this->assertCount(0, $company->getProjects());
        $this->assertCount(0, $company->getEmployees());

        // Test inherited AbstractEntity properties
        $this->assertNotNull($company->getCreatedAt());
        $this->assertNotNull($company->getUpdatedAt());
        $this->assertTrue($company->isActive());
    }

    public function testExtendsAbstractEntity(): void
    {
        $this->assertInstanceOf(\C3net\CoreBundle\Entity\AbstractEntity::class, $this->company);
        $this->assertInstanceOf(\Stringable::class, $this->company);
    }

    public function testNameTrait(): void
    {
        $name = 'Acme Corporation';

        $this->company->setName($name);

        $this->assertSame($name, $this->company->getName());
    }

    public function testNameExtensionTrait(): void
    {
        $nameExtension = 'LLC';

        $this->company->setNameExtension($nameExtension);

        $this->assertSame($nameExtension, $this->company->getNameExtension());
    }

    public function testCommunicationTrait(): void
    {
        $email = 'contact@acme.com';
        $phone = '+1-555-123-4567';
        $website = 'https://acme.com';

        $this->company->setEmail($email);
        $this->company->setPhone($phone);
        $this->company->setUrl($website);

        $this->assertSame($email, $this->company->getEmail());
        $this->assertSame($phone, $this->company->getPhone());
        $this->assertSame($website, $this->company->getUrl());
    }

    public function testAddressTrait(): void
    {
        $street = '123 Main Street';
        $city = 'New York';
        $zipCode = '10001';
        $country = 'US';

        $this->company->setStreet($street);
        $this->company->setCity($city);
        $this->company->setZip($zipCode);
        $this->company->setCountryCode($country);

        $this->assertSame($street, $this->company->getStreet());
        $this->assertSame($city, $this->company->getCity());
        $this->assertSame($zipCode, $this->company->getZip());
        $this->assertSame($country, $this->company->getCountryCode());
    }

    public function testCompanyGroupRelationship(): void
    {
        $this->assertNull($this->company->getCompanyGroup());

        $companyGroup = new CompanyGroup();
        $this->company->setCompanyGroup($companyGroup);

        $this->assertSame($companyGroup, $this->company->getCompanyGroup());
    }

    public function testCategoryRelationship(): void
    {
        $this->assertNull($this->company->getCategory());

        $category = new Category();
        $this->company->setCategory($category);

        $this->assertSame($category, $this->company->getCategory());
    }

    public function testImagePathProperty(): void
    {
        $this->assertNull($this->company->getImagePath());

        // Test with simple path
        $imagePath = 'uploads/company-logo.jpg';
        $this->company->setImagePath($imagePath);

        // Note: getImagePath() builds full URL, but we're testing the setter
        $this->assertStringContainsString($imagePath, $this->company->getImagePath());
    }

    public function testImagePathWithNull(): void
    {
        $this->company->setImagePath('test.jpg');
        $this->company->setImagePath(null);

        $this->assertNull($this->company->getImagePath());
    }

    public function testProjectsRelationship(): void
    {
        $project1 = new Project();
        $project2 = new Project();

        // Add projects
        $this->company->addProject($project1);
        $this->company->addProject($project2);

        $this->assertCount(2, $this->company->getProjects());
        $this->assertTrue($this->company->getProjects()->contains($project1));
        $this->assertTrue($this->company->getProjects()->contains($project2));
        $this->assertSame($this->company, $project1->getClient());
        $this->assertSame($this->company, $project2->getClient());

        // Remove project
        $this->company->removeProject($project1);

        $this->assertCount(1, $this->company->getProjects());
        $this->assertFalse($this->company->getProjects()->contains($project1));
        $this->assertNull($project1->getClient());
    }

    public function testProjectsNoDuplicates(): void
    {
        $project = new Project();

        $this->company->addProject($project);
        $this->company->addProject($project); // Add same project again

        $this->assertCount(1, $this->company->getProjects());
    }

    public function testEmployeesRelationship(): void
    {
        $employee1 = new User();
        $employee2 = new User();

        // Add employees
        $this->company->addEmployee($employee1);
        $this->company->addEmployee($employee2);

        $this->assertCount(2, $this->company->getEmployees());
        $this->assertTrue($this->company->getEmployees()->contains($employee1));
        $this->assertTrue($this->company->getEmployees()->contains($employee2));
        $this->assertSame($this->company, $employee1->getCompany());
        $this->assertSame($this->company, $employee2->getCompany());

        // Remove employee
        $this->company->removeEmployee($employee1);

        $this->assertCount(1, $this->company->getEmployees());
        $this->assertFalse($this->company->getEmployees()->contains($employee1));
        $this->assertNull($employee1->getCompany());
    }

    public function testEmployeesNoDuplicates(): void
    {
        $employee = new User();

        $this->company->addEmployee($employee);
        $this->company->addEmployee($employee); // Add same employee again

        $this->assertCount(1, $this->company->getEmployees());
    }

    public function testToStringWithName(): void
    {
        $companyName = 'Test Corporation';
        $this->company->setName($companyName);

        $this->assertSame($companyName, (string) $this->company);
    }

    public function testToStringWithoutName(): void
    {
        // When no name is set
        $this->assertSame('Unnamed Company', (string) $this->company);
    }

    public function testToStringWithEmptyName(): void
    {
        $this->company->setName('');

        $this->assertSame('Unnamed Company', (string) $this->company);
    }

    public function testCompleteCompanySetup(): void
    {
        $company = new Company();

        // Set basic info
        $company->setName('Complete Test Corp')
                ->setNameExtension('Inc.');

        // Set communication info
        $company->setEmail('test@company.com')
                ->setPhone('+1-555-TEST')
                ->setUrl('https://company.com');

        // Set address
        $company->setStreet('123 Business Ave')
                ->setCity('Business City')
                ->setZip('12345')
                ->setCountryCode('US');

        // Set relationships
        $companyGroup = new CompanyGroup();
        $category = new Category();
        $company->setCompanyGroup($companyGroup)
                ->setCategory($category);

        // Set image
        $company->setImagePath('logos/company.png');

        // Add employees and projects
        $employee = new User();
        $project = new Project();
        $company->addEmployee($employee)
                ->addProject($project);

        // Verify complete setup
        $this->assertSame('Complete Test Corp', $company->getName());
        $this->assertSame('Inc.', $company->getNameExtension());
        $this->assertSame('test@company.com', $company->getEmail());
        $this->assertSame('+1-555-TEST', $company->getPhone());
        $this->assertSame('https://company.com', $company->getUrl());
        $this->assertSame('123 Business Ave', $company->getStreet());
        $this->assertSame('Business City', $company->getCity());
        $this->assertSame('12345', $company->getZip());
        $this->assertSame('US', $company->getCountryCode());
        $this->assertSame($companyGroup, $company->getCompanyGroup());
        $this->assertSame($category, $company->getCategory());
        $this->assertStringContainsString('logos/company.png', $company->getImagePath());
        $this->assertCount(1, $company->getEmployees());
        $this->assertCount(1, $company->getProjects());
        $this->assertSame('Complete Test Corp', (string) $company);
    }

    public function testImagePathUrlGeneration(): void
    {
        // Mock server variables for URL generation
        $_SERVER['REQUEST_SCHEME'] = 'https';
        $_SERVER['HTTP_HOST'] = 'example.com';

        $imagePath = 'uploads/logo.jpg';
        $this->company->setImagePath($imagePath);

        $expectedUrl = 'https://example.com/uploads/logo.jpg';
        $this->assertSame($expectedUrl, $this->company->getImagePath());

        // Test with leading slash
        $this->company->setImagePath('/uploads/logo.jpg');
        $this->assertSame($expectedUrl, $this->company->getImagePath());

        // Clean up
        unset($_SERVER['REQUEST_SCHEME'], $_SERVER['HTTP_HOST']);
    }

    public function testBidirectionalRelationships(): void
    {
        $employee = new User();
        $project = new Project();

        // Test bidirectional employee relationship
        $this->company->addEmployee($employee);
        $this->assertSame($this->company, $employee->getCompany());

        $this->company->removeEmployee($employee);
        $this->assertNull($employee->getCompany());

        // Test bidirectional project relationship
        $this->company->addProject($project);
        $this->assertSame($this->company, $project->getClient());

        $this->company->removeProject($project);
        $this->assertNull($project->getClient());
    }

    public function testInheritedProperties(): void
    {
        // Test inherited active status
        $this->assertTrue($this->company->isActive());
        $this->company->setActive(false);
        $this->assertFalse($this->company->isActive());

        // Test inherited notes
        $notes = 'Important company notes';
        $this->company->setNotes($notes);
        $this->assertSame($notes, $this->company->getNotes());

        // Test inherited timestamps
        $this->assertNotNull($this->company->getCreatedAt());
        $this->assertNotNull($this->company->getUpdatedAt());
    }

    public function testFullCompanyName(): void
    {
        $this->company->setName('Acme Corporation');
        $this->company->setNameExtension('LLC');

        // While there's no explicit getFullName method, we can test the components
        $fullName = $this->company->getName() . ' ' . $this->company->getNameExtension();
        $this->assertSame('Acme Corporation LLC', $fullName);
    }
}
