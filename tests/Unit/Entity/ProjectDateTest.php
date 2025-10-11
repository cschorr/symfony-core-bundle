<?php

declare(strict_types=1);

namespace C3net\CoreBundle\Tests\Unit\Entity;

use C3net\CoreBundle\Entity\Project;
use C3net\CoreBundle\Entity\ProjectDate;
use PHPUnit\Framework\TestCase;

class ProjectDateTest extends TestCase
{
    private ProjectDate $projectDate;

    protected function setUp(): void
    {
        $this->projectDate = new ProjectDate();
    }

    public function testExtendsAbstractEntity(): void
    {
        $this->assertInstanceOf(\C3net\CoreBundle\Entity\AbstractEntity::class, $this->projectDate);
    }

    public function testProjectProperty(): void
    {
        $this->assertNull($this->projectDate->getProject());

        $project = $this->createMock(Project::class);
        $this->projectDate->setProject($project);
        $this->assertSame($project, $this->projectDate->getProject());

        $this->projectDate->setProject(null);
        $this->assertNull($this->projectDate->getProject());
    }

    public function testDateProperty(): void
    {
        $this->assertNull($this->projectDate->getDate());

        $date = new \DateTimeImmutable('2025-12-31 15:30:00');
        $this->projectDate->setDate($date);
        $this->assertSame($date, $this->projectDate->getDate());

        $this->projectDate->setDate(null);
        $this->assertNull($this->projectDate->getDate());
    }

    public function testNoticeProperty(): void
    {
        $this->assertNull($this->projectDate->getNotice());

        $notice = 'Important deadline for project milestone';
        $this->projectDate->setNotice($notice);
        $this->assertSame($notice, $this->projectDate->getNotice());

        $this->projectDate->setNotice(null);
        $this->assertNull($this->projectDate->getNotice());
    }

    public function testLabelProperty(): void
    {
        $this->assertNull($this->projectDate->getLabel());

        $label = 'Delivery Date';
        $this->projectDate->setLabel($label);
        $this->assertSame($label, $this->projectDate->getLabel());

        $this->projectDate->setLabel(null);
        $this->assertNull($this->projectDate->getLabel());
    }

    public function testToStringWithDateAndLabel(): void
    {
        $date = new \DateTimeImmutable('2025-12-31');
        $this->projectDate->setDate($date);
        $this->projectDate->setLabel('Deadline');

        $this->assertSame('2025-12-31 (Deadline)', (string) $this->projectDate);
    }

    public function testToStringWithDateOnly(): void
    {
        $date = new \DateTimeImmutable('2025-12-31');
        $this->projectDate->setDate($date);

        $this->assertSame('2025-12-31', (string) $this->projectDate);
    }

    public function testToStringWithoutDate(): void
    {
        $this->assertSame('No date', (string) $this->projectDate);
    }

    public function testFluentSetters(): void
    {
        $date = new \DateTimeImmutable('2025-12-31');
        $project = $this->createMock(Project::class);

        $result = $this->projectDate
            ->setDate($date)
            ->setNotice('Test notice')
            ->setLabel('Test label')
            ->setProject($project);

        $this->assertSame($this->projectDate, $result);
    }

    public function testCompleteProjectDate(): void
    {
        $project = $this->createMock(Project::class);
        $date = new \DateTimeImmutable('2025-06-15 10:00:00');
        $notice = 'Client presentation scheduled. Ensure all materials are ready 24 hours in advance.';
        $label = 'Client Presentation';

        $this->projectDate
            ->setProject($project)
            ->setDate($date)
            ->setNotice($notice)
            ->setLabel($label);

        $this->assertSame($project, $this->projectDate->getProject());
        $this->assertSame($date, $this->projectDate->getDate());
        $this->assertSame($notice, $this->projectDate->getNotice());
        $this->assertSame($label, $this->projectDate->getLabel());
        $this->assertSame('2025-06-15 (Client Presentation)', (string) $this->projectDate);
    }

    public function testLongNoticeText(): void
    {
        $longNotice = str_repeat('This is a very long notice text. ', 50);
        $this->projectDate->setNotice($longNotice);

        $this->assertSame($longNotice, $this->projectDate->getNotice());
    }

    public function testMultipleDateFormats(): void
    {
        $testDates = [
            new \DateTimeImmutable('2025-01-01 00:00:00'),
            new \DateTimeImmutable('2025-12-31 23:59:59'),
            new \DateTimeImmutable('2025-06-15 12:30:45'),
            new \DateTimeImmutable('now'),
        ];

        foreach ($testDates as $date) {
            $this->projectDate->setDate($date);
            $this->assertSame($date, $this->projectDate->getDate());
            $this->assertInstanceOf(\DateTimeImmutable::class, $this->projectDate->getDate());
        }
    }
}
