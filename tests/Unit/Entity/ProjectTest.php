<?php

declare(strict_types=1);

namespace C3net\CoreBundle\Tests\Unit\Entity;

use C3net\CoreBundle\Entity\Campaign;
use C3net\CoreBundle\Entity\Category;
use C3net\CoreBundle\Entity\Company;
use C3net\CoreBundle\Entity\Contact;
use C3net\CoreBundle\Entity\Notification;
use C3net\CoreBundle\Entity\Project;
use C3net\CoreBundle\Entity\User;
use C3net\CoreBundle\Enum\ProjectStatus;
use PHPUnit\Framework\TestCase;

class ProjectTest extends TestCase
{
    private Project $project;

    protected function setUp(): void
    {
        $this->project = new Project();
    }

    public function testConstructor(): void
    {
        $project = new Project();
        
        // Test that collections are initialized
        $this->assertCount(0, $project->getNotifications());
        $this->assertCount(0, $project->getContact());
        
        // Test default status
        $this->assertSame(ProjectStatus::PLANNING, $project->getStatus());
        $this->assertTrue($project->isPlanning());
        
        // Test inherited AbstractEntity properties
        $this->assertNotNull($project->getCreatedAt());
        $this->assertNotNull($project->getUpdatedAt());
        $this->assertTrue($project->isActive());
    }

    public function testExtendsAbstractEntity(): void
    {
        $this->assertInstanceOf(\C3net\CoreBundle\Entity\AbstractEntity::class, $this->project);
        $this->assertInstanceOf(\Stringable::class, $this->project);
    }

    public function testStatusProperty(): void
    {
        // Test default status
        $this->assertSame(ProjectStatus::PLANNING, $this->project->getStatus());
        
        // Test setting different statuses
        $this->project->setStatus(ProjectStatus::IN_PROGRESS);
        $this->assertSame(ProjectStatus::IN_PROGRESS, $this->project->getStatus());
        
        $this->project->setStatus(ProjectStatus::COMPLETED);
        $this->assertSame(ProjectStatus::COMPLETED, $this->project->getStatus());
        
        $this->project->setStatus(ProjectStatus::CANCELLED);
        $this->assertSame(ProjectStatus::CANCELLED, $this->project->getStatus());
        
        $this->project->setStatus(ProjectStatus::ON_HOLD);
        $this->assertSame(ProjectStatus::ON_HOLD, $this->project->getStatus());
    }

    public function testStatusHelperMethods(): void
    {
        // Test planning status
        $this->project->setStatus(ProjectStatus::PLANNING);
        $this->assertTrue($this->project->isPlanning());
        $this->assertFalse($this->project->isInProgress());
        $this->assertFalse($this->project->isCompleted());
        $this->assertFalse($this->project->isCancelled());
        $this->assertFalse($this->project->isOnHold());
        
        // Test in progress status
        $this->project->setStatus(ProjectStatus::IN_PROGRESS);
        $this->assertFalse($this->project->isPlanning());
        $this->assertTrue($this->project->isInProgress());
        $this->assertFalse($this->project->isCompleted());
        $this->assertFalse($this->project->isCancelled());
        $this->assertFalse($this->project->isOnHold());
        
        // Test completed status
        $this->project->setStatus(ProjectStatus::COMPLETED);
        $this->assertFalse($this->project->isPlanning());
        $this->assertFalse($this->project->isInProgress());
        $this->assertTrue($this->project->isCompleted());
        $this->assertFalse($this->project->isCancelled());
        $this->assertFalse($this->project->isOnHold());
        
        // Test cancelled status
        $this->project->setStatus(ProjectStatus::CANCELLED);
        $this->assertFalse($this->project->isPlanning());
        $this->assertFalse($this->project->isInProgress());
        $this->assertFalse($this->project->isCompleted());
        $this->assertTrue($this->project->isCancelled());
        $this->assertFalse($this->project->isOnHold());
        
        // Test on hold status
        $this->project->setStatus(ProjectStatus::ON_HOLD);
        $this->assertFalse($this->project->isPlanning());
        $this->assertFalse($this->project->isInProgress());
        $this->assertFalse($this->project->isCompleted());
        $this->assertFalse($this->project->isCancelled());
        $this->assertTrue($this->project->isOnHold());
    }

    public function testNameTrait(): void
    {
        $name = 'Test Project';
        
        $this->project->setName($name);
        
        $this->assertSame($name, $this->project->getName());
    }

    public function testStartEndTrait(): void
    {
        $startDate = new \DateTimeImmutable('2025-01-01');
        $endDate = new \DateTimeImmutable('2025-12-31');
        
        $this->project->setStartDate($startDate);
        $this->project->setEndDate($endDate);
        
        $this->assertSame($startDate, $this->project->getStartDate());
        $this->assertSame($endDate, $this->project->getEndDate());
    }

    public function testAssigneeRelationship(): void
    {
        $this->assertNull($this->project->getAssignee());
        
        $user = new User();
        $this->project->setAssignee($user);
        
        $this->assertSame($user, $this->project->getAssignee());
    }

    public function testClientRelationship(): void
    {
        $this->assertNull($this->project->getClient());
        
        $company = new Company();
        $this->project->setClient($company);
        
        $this->assertSame($company, $this->project->getClient());
    }

    public function testDescriptionProperty(): void
    {
        $this->assertNull($this->project->getDescription());
        
        $description = 'This is a detailed project description.';
        $this->project->setDescription($description);
        
        $this->assertSame($description, $this->project->getDescription());
    }

    public function testDescriptionHandlesLargeText(): void
    {
        $largeDescription = str_repeat('This is a very long description. ', 100);
        
        $this->project->setDescription($largeDescription);
        
        $this->assertSame($largeDescription, $this->project->getDescription());
        $this->assertGreaterThan(1000, strlen($this->project->getDescription()));
    }

    public function testCategoryRelationship(): void
    {
        $this->assertNull($this->project->getCategory());
        
        $category = new Category();
        $this->project->setCategory($category);
        
        $this->assertSame($category, $this->project->getCategory());
    }

    public function testCampaignRelationship(): void
    {
        $this->assertNull($this->project->getCampaign());
        
        $campaign = new Campaign();
        $this->project->setCampaign($campaign);
        
        $this->assertSame($campaign, $this->project->getCampaign());
    }

    public function testDueDateProperty(): void
    {
        $this->assertNull($this->project->getDueDate());
        
        $dueDate = new \DateTimeImmutable('2025-06-30');
        $this->project->setDueDate($dueDate);
        
        $this->assertSame($dueDate, $this->project->getDueDate());
    }

    public function testNotificationsRelationship(): void
    {
        $notification1 = new Notification();
        $notification2 = new Notification();
        
        // Add notifications
        $this->project->addNotification($notification1);
        $this->project->addNotification($notification2);
        
        $this->assertCount(2, $this->project->getNotifications());
        $this->assertTrue($this->project->getNotifications()->contains($notification1));
        $this->assertTrue($this->project->getNotifications()->contains($notification2));
        $this->assertSame($this->project, $notification1->getProject());
        $this->assertSame($this->project, $notification2->getProject());
        
        // Remove notification
        $this->project->removeNotification($notification1);
        
        $this->assertCount(1, $this->project->getNotifications());
        $this->assertFalse($this->project->getNotifications()->contains($notification1));
        $this->assertNull($notification1->getProject());
    }

    public function testNotificationsNoDuplicates(): void
    {
        $notification = new Notification();
        
        $this->project->addNotification($notification);
        $this->project->addNotification($notification); // Add same notification again
        
        $this->assertCount(1, $this->project->getNotifications());
    }

    public function testContactsRelationship(): void
    {
        $contact1 = new Contact();
        $contact2 = new Contact();
        
        // Add contacts
        $this->project->addContact($contact1);
        $this->project->addContact($contact2);
        
        $this->assertCount(2, $this->project->getContact());
        $this->assertTrue($this->project->getContact()->contains($contact1));
        $this->assertTrue($this->project->getContact()->contains($contact2));
        
        // Remove contact
        $this->project->removeContact($contact1);
        
        $this->assertCount(1, $this->project->getContact());
        $this->assertFalse($this->project->getContact()->contains($contact1));
    }

    public function testContactsNoDuplicates(): void
    {
        $contact = new Contact();
        
        $this->project->addContact($contact);
        $this->project->addContact($contact); // Add same contact again
        
        $this->assertCount(1, $this->project->getContact());
    }

    public function testToStringWithName(): void
    {
        $projectName = 'My Test Project';
        $this->project->setName($projectName);
        
        $this->assertSame($projectName, (string) $this->project);
    }

    public function testToStringWithoutName(): void
    {
        // When no name is set
        $this->assertSame('Unnamed Project', (string) $this->project);
    }

    public function testToStringWithEmptyName(): void
    {
        $this->project->setName('');
        
        $this->assertSame('Unnamed Project', (string) $this->project);
    }

    public function testCompleteProjectWorkflow(): void
    {
        $project = new Project();
        
        // Set up complete project
        $project->setName('Complete Test Project')
                ->setDescription('A comprehensive test project')
                ->setStatus(ProjectStatus::IN_PROGRESS);
        
        // Set dates
        $startDate = new \DateTimeImmutable('2025-01-01');
        $endDate = new \DateTimeImmutable('2025-12-31');
        $dueDate = new \DateTimeImmutable('2025-11-30');
        
        $project->setStartDate($startDate)
                ->setEndDate($endDate)
                ->setDueDate($dueDate);
        
        // Set relationships
        $assignee = new User();
        $client = new Company();
        $category = new Category();
        $campaign = new Campaign();
        
        $project->setAssignee($assignee)
                ->setClient($client)
                ->setCategory($category)
                ->setCampaign($campaign);
        
        // Add contacts and notifications
        $contact = new Contact();
        $notification = new Notification();
        
        $project->addContact($contact)
                ->addNotification($notification);
        
        // Verify complete setup
        $this->assertSame('Complete Test Project', $project->getName());
        $this->assertSame('A comprehensive test project', $project->getDescription());
        $this->assertTrue($project->isInProgress());
        $this->assertSame($startDate, $project->getStartDate());
        $this->assertSame($endDate, $project->getEndDate());
        $this->assertSame($dueDate, $project->getDueDate());
        $this->assertSame($assignee, $project->getAssignee());
        $this->assertSame($client, $project->getClient());
        $this->assertSame($category, $project->getCategory());
        $this->assertSame($campaign, $project->getCampaign());
        $this->assertCount(1, $project->getContact());
        $this->assertCount(1, $project->getNotifications());
        $this->assertSame('Complete Test Project', (string) $project);
    }

    public function testProjectStatusTransitions(): void
    {
        // Test typical project lifecycle
        $this->assertTrue($this->project->isPlanning());
        
        // Start project
        $this->project->setStatus(ProjectStatus::IN_PROGRESS);
        $this->assertTrue($this->project->isInProgress());
        
        // Put on hold
        $this->project->setStatus(ProjectStatus::ON_HOLD);
        $this->assertTrue($this->project->isOnHold());
        
        // Resume project
        $this->project->setStatus(ProjectStatus::IN_PROGRESS);
        $this->assertTrue($this->project->isInProgress());
        
        // Complete project
        $this->project->setStatus(ProjectStatus::COMPLETED);
        $this->assertTrue($this->project->isCompleted());
    }

    public function testProjectCancellation(): void
    {
        $this->project->setStatus(ProjectStatus::IN_PROGRESS);
        $this->assertTrue($this->project->isInProgress());
        
        // Cancel project
        $this->project->setStatus(ProjectStatus::CANCELLED);
        $this->assertTrue($this->project->isCancelled());
        $this->assertFalse($this->project->isInProgress());
        $this->assertFalse($this->project->isCompleted());
    }

    public function testInheritedActiveProperty(): void
    {
        // Test inherited active status from AbstractEntity
        $this->assertTrue($this->project->isActive());
        
        $this->project->setActive(false);
        $this->assertFalse($this->project->isActive());
    }

    public function testInheritedNotesProperty(): void
    {
        // Test inherited notes from AbstractEntity
        $notes = 'Internal project notes';
        $this->project->setNotes($notes);
        
        $this->assertSame($notes, $this->project->getNotes());
    }
}