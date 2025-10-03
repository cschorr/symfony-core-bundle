<?php

declare(strict_types=1);

namespace C3net\CoreBundle\State;

use ApiPlatform\Metadata\Operation;
use ApiPlatform\State\ProcessorInterface;
use C3net\CoreBundle\Entity\User;
use C3net\CoreBundle\Entity\UserGroup;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Handles UserGroup write operations to properly manage User relationships.
 */
final readonly class UserGroupWriteProcessor implements ProcessorInterface
{
    public function __construct(
        private ProcessorInterface $persistProcessor,
        private EntityManagerInterface $entityManager,
    ) {
    }

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        if ($data instanceof UserGroup) {
            $this->processUserRelationships($data);
        }

        return $this->persistProcessor->process($data, $operation, $uriVariables, $context);
    }

    private function processUserRelationships(UserGroup $userGroup): void
    {
        $currentUsers = $userGroup->getUsers();

        foreach ($currentUsers as $user) {
            // Ensure the user exists in the EntityManager
            if (!$this->entityManager->contains($user)) {
                // If the user has an ID, merge it to get the managed entity
                if (null !== $user->getId()) {
                    $managedUser = $this->entityManager->find(User::class, $user->getId());
                    if (null !== $managedUser) {
                        // Replace the user in the collection with the managed entity
                        $userGroup->removeUser($user);
                        $userGroup->addUser($managedUser);
                    }
                }
            }
        }
    }
}
