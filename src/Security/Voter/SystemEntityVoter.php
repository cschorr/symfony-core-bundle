<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\SystemEntity;
use App\Entity\User;
use App\Enum\SystemEntityPermission;
use App\Repository\SystemEntityRepository;
use App\Repository\UserGroupSystemEntityPermissionRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class SystemEntityVoter extends Voter
{
    public function __construct(
        private readonly UserGroupSystemEntityPermissionRepository $permissionRepository,
        private readonly SystemEntityRepository $systemEntityRepository,
        private readonly LoggerInterface $logger,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        // Support both SystemEntity entities and system entity codes/names (strings)
        $validAttributes = array_map(fn (SystemEntityPermission $case) => $case->value, SystemEntityPermission::cases());

        return in_array($attribute, $validAttributes, true)
            && ($subject instanceof SystemEntity || is_string($subject));
    }

    protected function voteOnAttribute(
        string $attribute,
        mixed $subject,
        TokenInterface $token,
        ?Vote $vote = null,
    ): bool {
        /** @var User $user */
        $user = $token->getUser();

        // User must be logged in
        if (!$user instanceof UserInterface) {
            return false;
        }

        // Admin users have all permissions
        if ($user instanceof UserInterface && in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return true;
        }

        // Get the system entity
        $systemEntity = $subject;
        if (is_string($subject)) {
            // Try to resolve string to SystemEntity entity
            $systemEntity = $this->resolveSystemEntityFromString($subject);
            if (null === $systemEntity) {
                // Debug: Log when resolution fails
                $this->logger->debug(sprintf("SystemEntityVoter: Could not resolve '%s' to SystemEntity", $subject));

                return false;
            }

            // Debug: Log successful resolution
            $this->logger->debug(sprintf("SystemEntityVoter: Resolved '%s' to SystemEntity ID: %s", $subject, $systemEntity->getId()));
        }

        if (!$systemEntity instanceof SystemEntity) {
            return false;
        }

        // Check specific permission
        // Convert string to enum for type safety
        $permission = SystemEntityPermission::tryFrom($attribute);
        if (!$permission) {
            return false;
        }

        // Check specific permission using match expression
        return match ($permission) {
            SystemEntityPermission::READ => $this->canRead($systemEntity, $user),
            SystemEntityPermission::WRITE,
            SystemEntityPermission::EDIT,
            SystemEntityPermission::DELETE => $this->canWrite($systemEntity, $user),
        };
    }

    private function canRead(SystemEntity $systemEntity, User $user): bool
    {
        return $this->permissionRepository->userHasReadAccess($user, $systemEntity);
    }

    private function canWrite(SystemEntity $systemEntity, User $user): bool
    {
        return $this->permissionRepository->userHasWriteAccess($user, $systemEntity);
    }

    /**
     * Try to resolve a string (system entity code or name) to a SystemEntity entity.
     */
    private function resolveSystemEntityFromString(string $subject): ?SystemEntity
    {
        // Try to find by code first, then by name
        $systemEntity = $this->systemEntityRepository->findOneBy(['code' => $subject]);

        if (null === $systemEntity) {
            return $this->systemEntityRepository->findOneBy(['name' => $subject]);
        }

        return $systemEntity;
    }
}
