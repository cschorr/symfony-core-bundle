<?php

declare(strict_types=1);

namespace C3net\CoreBundle\Tests\Unit\Enum;

use C3net\CoreBundle\Enum\ProjectStatus;
use PHPUnit\Framework\TestCase;

class ProjectStatusTest extends TestCase
{
    public function testEnumExists(): void
    {
        $this->assertTrue(enum_exists(ProjectStatus::class));
    }

    public function testEnumIsBackedByString(): void
    {
        $reflection = new \ReflectionEnum(ProjectStatus::class);
        $this->assertSame('string', $reflection->getBackingType()?->getName());
    }

    public function testAllCasesExist(): void
    {
        $this->assertSame('planning', ProjectStatus::PLANNING->value);
        $this->assertSame('in_progress', ProjectStatus::IN_PROGRESS->value);
        $this->assertSame('on_hold', ProjectStatus::ON_HOLD->value);
        $this->assertSame('completed', ProjectStatus::COMPLETED->value);
        $this->assertSame('cancelled', ProjectStatus::CANCELLED->value);
    }

    public function testFromString(): void
    {
        $this->assertSame(ProjectStatus::PLANNING, ProjectStatus::from('planning'));
        $this->assertSame(ProjectStatus::IN_PROGRESS, ProjectStatus::from('in_progress'));
        $this->assertSame(ProjectStatus::ON_HOLD, ProjectStatus::from('on_hold'));
        $this->assertSame(ProjectStatus::COMPLETED, ProjectStatus::from('completed'));
        $this->assertSame(ProjectStatus::CANCELLED, ProjectStatus::from('cancelled'));
    }

    public function testTryFromString(): void
    {
        $this->assertSame(ProjectStatus::PLANNING, ProjectStatus::tryFrom('planning'));
        $this->assertSame(ProjectStatus::IN_PROGRESS, ProjectStatus::tryFrom('in_progress'));
        $this->assertNull(ProjectStatus::tryFrom('invalid_status'));
    }

    public function testFromStringThrowsExceptionForInvalidValue(): void
    {
        $this->expectException(\ValueError::class);
        ProjectStatus::from('invalid_status');
    }

    public function testCases(): void
    {
        $cases = ProjectStatus::cases();
        
        $this->assertCount(5, $cases);
        $this->assertContainsOnlyInstancesOf(ProjectStatus::class, $cases);
        
        $values = array_map(fn(ProjectStatus $status) => $status->value, $cases);
        $this->assertContains('planning', $values);
        $this->assertContains('in_progress', $values);
        $this->assertContains('on_hold', $values);
        $this->assertContains('completed', $values);
        $this->assertContains('cancelled', $values);
    }

    public function testGetLabel(): void
    {
        $this->assertSame('Planning', ProjectStatus::PLANNING->getLabel());
        $this->assertSame('In Progress', ProjectStatus::IN_PROGRESS->getLabel());
        $this->assertSame('On Hold', ProjectStatus::ON_HOLD->getLabel());
        $this->assertSame('Completed', ProjectStatus::COMPLETED->getLabel());
        $this->assertSame('Cancelled', ProjectStatus::CANCELLED->getLabel());
    }

    public function testGetBadgeClass(): void
    {
        $this->assertSame('secondary', ProjectStatus::PLANNING->getBadgeClass());
        $this->assertSame('primary', ProjectStatus::IN_PROGRESS->getBadgeClass());
        $this->assertSame('warning', ProjectStatus::ON_HOLD->getBadgeClass());
        $this->assertSame('success', ProjectStatus::COMPLETED->getBadgeClass());
        $this->assertSame('danger', ProjectStatus::CANCELLED->getBadgeClass());
    }

    public function testLabelsAreHumanReadable(): void
    {
        foreach (ProjectStatus::cases() as $status) {
            $label = $status->getLabel();
            
            $this->assertIsString($label);
            $this->assertNotEmpty($label);
            $this->assertMatchesRegularExpression('/^[A-Z][a-z\s]*$/', $label, "Label '{$label}' should be properly formatted");
        }
    }

    public function testBadgeClassesAreValidBootstrapClasses(): void
    {
        $validClasses = ['primary', 'secondary', 'success', 'danger', 'warning', 'info', 'light', 'dark'];
        
        foreach (ProjectStatus::cases() as $status) {
            $badgeClass = $status->getBadgeClass();
            
            $this->assertIsString($badgeClass);
            $this->assertContains($badgeClass, $validClasses, "Badge class '{$badgeClass}' should be a valid Bootstrap class");
        }
    }

    public function testStatusProgression(): void
    {
        // Test logical status progression
        $progressiveStatuses = [
            ProjectStatus::PLANNING,
            ProjectStatus::IN_PROGRESS,
            ProjectStatus::COMPLETED
        ];
        
        foreach ($progressiveStatuses as $status) {
            $this->assertInstanceOf(ProjectStatus::class, $status);
        }
        
        // Test alternative statuses
        $alternativeStatuses = [
            ProjectStatus::ON_HOLD,
            ProjectStatus::CANCELLED
        ];
        
        foreach ($alternativeStatuses as $status) {
            $this->assertInstanceOf(ProjectStatus::class, $status);
        }
    }

    public function testStatusComparison(): void
    {
        $status1 = ProjectStatus::IN_PROGRESS;
        $status2 = ProjectStatus::IN_PROGRESS;
        $status3 = ProjectStatus::COMPLETED;
        
        $this->assertSame($status1, $status2);
        $this->assertNotSame($status1, $status3);
        $this->assertTrue($status1 === $status2);
        $this->assertFalse($status1 === $status3);
    }

    public function testAllStatusesHaveUniqueValues(): void
    {
        $values = array_map(fn(ProjectStatus $status) => $status->value, ProjectStatus::cases());
        $uniqueValues = array_unique($values);
        
        $this->assertCount(count($uniqueValues), $values, 'All status values should be unique');
    }

    public function testAllStatusesHaveUniqueLabels(): void
    {
        $labels = array_map(fn(ProjectStatus $status) => $status->getLabel(), ProjectStatus::cases());
        $uniqueLabels = array_unique($labels);
        
        $this->assertCount(count($uniqueLabels), $labels, 'All status labels should be unique');
    }

    public function testEnumSerialization(): void
    {
        $status = ProjectStatus::IN_PROGRESS;
        
        // Test JSON serialization
        $json = json_encode($status);
        $this->assertSame('"in_progress"', $json);
        
        // Test string value
        $this->assertSame('in_progress', $status->value);
    }

    public function testMatchExpressionCompleteness(): void
    {
        // Test that getLabel() handles all cases
        foreach (ProjectStatus::cases() as $status) {
            $label = $status->getLabel();
            $this->assertIsString($label);
            $this->assertNotEmpty($label);
        }
        
        // Test that getBadgeClass() handles all cases  
        foreach (ProjectStatus::cases() as $status) {
            $badgeClass = $status->getBadgeClass();
            $this->assertIsString($badgeClass);
            $this->assertNotEmpty($badgeClass);
        }
    }

    public function testStatusSemantics(): void
    {
        // Test that status values make semantic sense
        $this->assertStringContainsString('progress', ProjectStatus::IN_PROGRESS->value);
        $this->assertStringContainsString('hold', ProjectStatus::ON_HOLD->value);
        $this->assertStringContainsString('complete', ProjectStatus::COMPLETED->value);
        $this->assertStringContainsString('cancel', ProjectStatus::CANCELLED->value);
    }

    public function testBadgeClassSemantics(): void
    {
        // Test that badge classes make semantic sense
        $this->assertSame('success', ProjectStatus::COMPLETED->getBadgeClass()); // Green for success
        $this->assertSame('danger', ProjectStatus::CANCELLED->getBadgeClass()); // Red for cancelled
        $this->assertSame('warning', ProjectStatus::ON_HOLD->getBadgeClass()); // Yellow for warning
        $this->assertSame('primary', ProjectStatus::IN_PROGRESS->getBadgeClass()); // Blue for active
        $this->assertSame('secondary', ProjectStatus::PLANNING->getBadgeClass()); // Gray for planning
    }

    public function testMethodReturnTypes(): void
    {
        $status = ProjectStatus::IN_PROGRESS;
        
        $this->assertIsString($status->getLabel());
        $this->assertIsString($status->getBadgeClass());
        $this->assertIsString($status->value);
    }

    public function testCompleteStatusWorkflow(): void
    {
        // Test complete project status workflow
        $statuses = [
            ProjectStatus::PLANNING => ['Planning', 'secondary'],
            ProjectStatus::IN_PROGRESS => ['In Progress', 'primary'],
            ProjectStatus::ON_HOLD => ['On Hold', 'warning'],
            ProjectStatus::COMPLETED => ['Completed', 'success'],
            ProjectStatus::CANCELLED => ['Cancelled', 'danger']
        ];
        
        foreach ($statuses as $status => [$expectedLabel, $expectedBadgeClass]) {
            $this->assertSame($expectedLabel, $status->getLabel());
            $this->assertSame($expectedBadgeClass, $status->getBadgeClass());
        }
    }
}