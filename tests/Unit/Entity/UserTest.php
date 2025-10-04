<?php

declare(strict_types=1);

namespace C3net\CoreBundle\Tests\Unit\Entity;

use C3net\CoreBundle\Entity\AuditLogs;
use C3net\CoreBundle\Entity\Category;
use C3net\CoreBundle\Entity\Company;
use C3net\CoreBundle\Entity\Project;
use C3net\CoreBundle\Entity\User;
use C3net\CoreBundle\Entity\UserGroup;
use C3net\CoreBundle\Enum\UserRole;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\UserInterface;

class UserTest extends TestCase
{
    private User $user;

    protected function setUp(): void
    {
        $this->user = new User();
    }

    public function testConstructor(): void
    {
        $user = new User();

        // Test that collections are initialized
        $this->assertCount(0, $user->getProjects());
        $this->assertCount(0, $user->getUserGroups());
        $this->assertCount(0, $user->getAuditLogs());

        // Test inherited AbstractEntity properties
        $this->assertNotNull($user->getCreatedAt());
        $this->assertNotNull($user->getUpdatedAt());
        $this->assertTrue($user->isActive());
        $this->assertFalse($user->isLocked());
    }

    public function testImplementsInterfaces(): void
    {
        $this->assertInstanceOf(UserInterface::class, $this->user);
        $this->assertInstanceOf(PasswordAuthenticatedUserInterface::class, $this->user);
        $this->assertInstanceOf(\Stringable::class, $this->user);
    }

    public function testEmailProperty(): void
    {
        $email = 'test@example.com';

        $this->user->setEmail($email);

        $this->assertSame($email, $this->user->getEmail());
        $this->assertSame($email, $this->user->getUserIdentifier());
        $this->assertSame($email, $this->user->getUsername());
    }

    public function testPasswordProperty(): void
    {
        $password = 'hashedPassword123';

        $this->user->setPassword($password);

        $this->assertSame($password, $this->user->getPassword());
    }

    public function testLockedProperty(): void
    {
        $this->assertFalse($this->user->isLocked());

        $this->user->setLocked(true);
        $this->assertTrue($this->user->isLocked());
        $this->assertTrue($this->user->getIsLocked());

        $this->user->setLocked(false);
        $this->assertFalse($this->user->isLocked());
    }

    public function testLastLoginProperty(): void
    {
        $this->assertNull($this->user->getLastLogin());

        $lastLogin = new \DateTimeImmutable('2025-01-01 12:00:00');
        $this->user->setLastLogin($lastLogin);

        $this->assertSame($lastLogin, $this->user->getLastLogin());
    }

    public function testPasswordResetToken(): void
    {
        $this->assertNull($this->user->getPasswordResetToken());

        $token = 'reset-token-123';
        $this->user->setPasswordResetToken($token);

        $this->assertSame($token, $this->user->getPasswordResetToken());
    }

    public function testPasswordResetTokenExpiry(): void
    {
        $this->assertNull($this->user->getPasswordResetTokenExpiresAt());

        $expiry = new \DateTimeImmutable('+1 hour');
        $this->user->setPasswordResetTokenExpiresAt($expiry);

        $this->assertSame($expiry, $this->user->getPasswordResetTokenExpiresAt());
    }

    public function testRolesHandling(): void
    {
        // Test default role
        $roles = $this->user->getRoles();
        $this->assertContains(UserRole::ROLE_USER->value, $roles);

        // Test setting roles from enums
        $userRoles = [UserRole::ROLE_ADMIN, UserRole::ROLE_MANAGER];
        $this->user->setRolesFromEnums($userRoles);

        $roleEnums = $this->user->getRoleEnums();
        $this->assertContains(UserRole::ROLE_ADMIN, $roleEnums);
        $this->assertContains(UserRole::ROLE_MANAGER, $roleEnums);

        // Test that ROLE_USER is always included
        $allRoles = $this->user->getRoles();
        $this->assertContains(UserRole::ROLE_USER->value, $allRoles);
        $this->assertContains(UserRole::ROLE_ADMIN->value, $allRoles);
        $this->assertContains(UserRole::ROLE_MANAGER->value, $allRoles);
    }

    public function testRolesBackwardCompatibility(): void
    {
        // Test setting roles with strings
        $stringRoles = ['ROLE_ADMIN', 'ROLE_EDITOR'];
        $this->user->setRoles($stringRoles);

        $roles = $this->user->getRoles();
        $this->assertContains('ROLE_ADMIN', $roles);
        $this->assertContains('ROLE_EDITOR', $roles);
        $this->assertContains(UserRole::ROLE_USER->value, $roles);
    }

    public function testRolesMixedTypes(): void
    {
        // Test setting roles with mixed string/enum array
        $mixedRoles = ['ROLE_ADMIN', UserRole::ROLE_MANAGER];
        $this->user->setRoles($mixedRoles);

        $roles = $this->user->getRoles();
        $this->assertContains('ROLE_ADMIN', $roles);
        $this->assertContains(UserRole::ROLE_MANAGER->value, $roles);
    }

    public function testRolesNullHandling(): void
    {
        $this->user->setRoles(null);

        $roles = $this->user->getRoles();
        $this->assertSame([UserRole::ROLE_USER->value], $roles);
    }

    public function testCompanyRelationship(): void
    {
        $this->assertNull($this->user->getCompany());

        $company = new Company();
        $this->user->setCompany($company);

        $this->assertSame($company, $this->user->getCompany());
    }

    public function testCategoryRelationship(): void
    {
        $this->assertNull($this->user->getCategory());

        $category = new Category();
        $this->user->setCategory($category);

        $this->assertSame($category, $this->user->getCategory());
    }

    public function testProjectsRelationship(): void
    {
        $project1 = new Project();
        $project2 = new Project();

        // Add projects
        $this->user->addProject($project1);
        $this->user->addProject($project2);

        $this->assertCount(2, $this->user->getProjects());
        $this->assertTrue($this->user->getProjects()->contains($project1));
        $this->assertTrue($this->user->getProjects()->contains($project2));
        $this->assertSame($this->user, $project1->getAssignee());
        $this->assertSame($this->user, $project2->getAssignee());

        // Remove project
        $this->user->removeProject($project1);

        $this->assertCount(1, $this->user->getProjects());
        $this->assertFalse($this->user->getProjects()->contains($project1));
        $this->assertNull($project1->getAssignee());
    }

    public function testProjectsNoDuplicates(): void
    {
        $project = new Project();

        $this->user->addProject($project);
        $this->user->addProject($project); // Add same project again

        $this->assertCount(1, $this->user->getProjects());
    }

    public function testUserGroupsRelationship(): void
    {
        $group1 = new UserGroup();
        $group2 = new UserGroup();

        // Add groups
        $this->user->addUserGroup($group1);
        $this->user->addUserGroup($group2);

        $this->assertCount(2, $this->user->getUserGroups());
        $this->assertTrue($this->user->getUserGroups()->contains($group1));
        $this->assertTrue($this->user->getUserGroups()->contains($group2));

        // Remove group
        $this->user->removeUserGroup($group1);

        $this->assertCount(1, $this->user->getUserGroups());
        $this->assertFalse($this->user->getUserGroups()->contains($group1));
    }

    public function testUserGroupsNoDuplicates(): void
    {
        $group = new UserGroup();

        $this->user->addUserGroup($group);
        $this->user->addUserGroup($group); // Add same group again

        $this->assertCount(1, $this->user->getUserGroups());
    }

    public function testAuditLogsRelationship(): void
    {
        $auditLog1 = new AuditLogs();
        $auditLog2 = new AuditLogs();

        // Add audit logs
        $this->user->addAuditLog($auditLog1);
        $this->user->addAuditLog($auditLog2);

        $this->assertCount(2, $this->user->getAuditLogs());
        $this->assertTrue($this->user->getAuditLogs()->contains($auditLog1));
        $this->assertTrue($this->user->getAuditLogs()->contains($auditLog2));
        $this->assertSame($this->user, $auditLog1->getAuthor());
        $this->assertSame($this->user, $auditLog2->getAuthor());

        // Remove audit log
        $this->user->removeAuditLog($auditLog1);

        $this->assertCount(1, $this->user->getAuditLogs());
        $this->assertFalse($this->user->getAuditLogs()->contains($auditLog1));
        $this->assertNull($auditLog1->getAuthor());
    }

    public function testAuditLogsNoDuplicates(): void
    {
        $auditLog = new AuditLogs();

        $this->user->addAuditLog($auditLog);
        $this->user->addAuditLog($auditLog); // Add same audit log again

        $this->assertCount(1, $this->user->getAuditLogs());
    }

    public function testRolesFromUserGroups(): void
    {
        $group1 = new UserGroup();
        $group1->setRoles(['ROLE_EDITOR']);

        $group2 = new UserGroup();
        $group2->setRoles(['ROLE_ADMIN', 'ROLE_MANAGER']);

        $this->user->addUserGroup($group1);
        $this->user->addUserGroup($group2);

        $roles = $this->user->getRoles();

        $this->assertContains('ROLE_EDITOR', $roles);
        $this->assertContains('ROLE_ADMIN', $roles);
        $this->assertContains('ROLE_MANAGER', $roles);
        $this->assertContains(UserRole::ROLE_USER->value, $roles);

        // Test uniqueness
        $uniqueRoles = array_unique($roles);
        $this->assertCount(count($uniqueRoles), $roles);
    }

    public function testEraseCredentials(): void
    {
        // This method should be a no-op
        $this->user->eraseCredentials();

        // No assertion needed, just verify method exists and doesn't throw
        $this->assertTrue(method_exists($this->user, 'eraseCredentials'));
    }

    public function testToString(): void
    {
        $this->assertSame('', (string) $this->user);

        $email = 'test@example.com';
        $this->user->setEmail($email);

        $this->assertSame($email, (string) $this->user);
    }

    public function testApiPlatformMethods(): void
    {
        // Test ID string method
        $this->assertNull($this->user->getIdString());

        // Test active status
        $this->assertTrue($this->user->getIsActive());
        $this->user->setActive(false);
        $this->assertFalse($this->user->getIsActive());

        // Test locked status
        $this->assertFalse($this->user->getIsLocked());
        $this->user->setLocked(true);
        $this->assertTrue($this->user->getIsLocked());

        // Test name methods
        $this->user->setNameFirst('John');
        $this->user->setNameLast('Doe');

        $this->assertSame('John', $this->user->getFirstName());
        $this->assertSame('Doe', $this->user->getLastName());

        // Test username
        $email = 'john.doe@example.com';
        $this->user->setEmail($email);
        $this->assertSame($email, $this->user->getUsername());
    }

    public function testCommunicationTraitMethods(): void
    {
        // Test email from SetCommunicationTrait
        $email = 'communication@example.com';
        $this->user->setCommunicationEmail($email);
        $this->assertSame($email, $this->user->getCommunicationEmail());

        // Test phone
        $phone = '+1234567890';
        $this->user->setCommunicationPhone($phone);
        $this->assertSame($phone, $this->user->getCommunicationPhone());
    }

    public function testCompleteUserWorkflow(): void
    {
        $user = new User();

        // Set basic info
        $user->setEmail('workflow@example.com')
             ->setPassword('hashedPassword')
             ->setNameFirst('John')
             ->setNameLast('Doe')
             ->setLocked(false);

        // Set roles
        $user->setRolesFromEnums([UserRole::ROLE_ADMIN]);

        // Add to company and category
        $company = new Company();
        $category = new Category();
        $user->setCompany($company)
             ->setCategory($category);

        // Add to groups
        $group = new UserGroup();
        $group->setRoles(['ROLE_EDITOR']);
        $user->addUserGroup($group);

        // Add projects
        $project = new Project();
        $user->addProject($project);

        // Verify complete setup
        $this->assertSame('workflow@example.com', $user->getEmail());
        $this->assertSame('hashedPassword', $user->getPassword());
        $this->assertSame('John', $user->getFirstName());
        $this->assertSame('Doe', $user->getLastName());
        $this->assertFalse($user->isLocked());
        $this->assertSame($company, $user->getCompany());
        $this->assertSame($category, $user->getCategory());
        $this->assertCount(1, $user->getUserGroups());
        $this->assertCount(1, $user->getProjects());

        $roles = $user->getRoles();
        $this->assertContains(UserRole::ROLE_ADMIN->value, $roles);
        $this->assertContains('ROLE_EDITOR', $roles);
        $this->assertContains(UserRole::ROLE_USER->value, $roles);
    }
}
