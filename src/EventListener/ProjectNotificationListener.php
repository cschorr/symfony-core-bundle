<?php

declare(strict_types=1);

namespace C3net\CoreBundle\EventListener;

use C3net\CoreBundle\Entity\Notification;
use C3net\CoreBundle\Entity\Project;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsEntityListener;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Events;

#[AsEntityListener(event: Events::postPersist, method: 'postPersist', entity: Project::class)]
class ProjectNotificationListener
{
    public function postPersist(Project $project, PostPersistEventArgs $event): void
    {
        $entityManager = $event->getObjectManager();

        $notification = new Notification();
        $notification->setType('project_created');
        $notification->setText(sprintf('Project "%s" has been created', $project->getName()));
        $notification->setProject($project);

        $entityManager->persist($notification);
        $entityManager->flush();
    }
}
