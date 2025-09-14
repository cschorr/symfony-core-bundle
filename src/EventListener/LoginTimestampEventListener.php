<?php

declare(strict_types=1);

namespace C3net\CoreBundle\EventListener;

use C3net\CoreBundle\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\EventDispatcher\Attribute\AsEventListener;
use Symfony\Component\Security\Http\Event\LoginSuccessEvent;

#[AsEventListener(event: LoginSuccessEvent::class)]
final readonly class LoginTimestampEventListener
{
    public function __construct(
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function __invoke(LoginSuccessEvent $event): void
    {
        $user = $event->getUser();

        if (!$user instanceof User) {
            return;
        }

        // Setze den aktuellen Timestamp als lastLogin
        $user->setLastLogin(new \DateTimeImmutable());

        // Speichere die Änderung in der Datenbank
        $this->entityManager->flush();
    }
}
