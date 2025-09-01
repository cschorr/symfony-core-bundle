<?php

declare(strict_types=1);

namespace App\Security\Voter;

use App\Entity\DomainEntityPermission;
use App\Entity\User;
use App\Enum\Permission;
use App\Repository\DomainEntityRepository;
use App\Repository\UserGroupDomainEntityPermissionRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class DomainEntityVoter extends Voter
{
    public function __construct(
        private readonly UserGroupDomainEntityPermissionRepository $permissionRepository,
        private readonly DomainEntityRepository $domainEntityRepository,
        private readonly LoggerInterface $logger,
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        // Support both DomainEntityPermission entities and system entity codes/names (strings)
        $validAttributes = array_map(fn (Permission $case) => $case->value, Permission::cases());

        return in_array($attribute, $validAttributes, true)
            && ($subject instanceof DomainEntityPermission || is_string($subject));
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
            // Try to resolve string to DomainEntityPermission entity
            $systemEntity = $this->resolveSystemEntityFromString($subject);
            if (null === $systemEntity) {
                // Debug: Log when resolution fails
                $this->logger->debug(sprintf("SystemEntityVoter: Could not resolve '%s' to DomainEntityPermission", $subject));

                return false;
            }

            // Debug: Log successful resolution
            $this->logger->debug(sprintf("SystemEntityVoter: Resolved '%s' to DomainEntityPermission ID: %s", $subject, $systemEntity->getId()));
        }

        if (!$systemEntity instanceof DomainEntityPermission) {
            return false;
        }

        // Check specific permission
        // Convert string to enum for type safety
        $permission = Permission::tryFrom($attribute);
        if (!$permission) {
            return false;
        }

        // Check specific permission using match expression
        return match ($permission) {
            Permission::READ => $this->canRead($systemEntity, $user),
            Permission::WRITE,
            Permission::EDIT,
            Permission::DELETE => $this->canWrite($systemEntity, $user),
        };
    }

    private function canRead(DomainEntityPermission $systemEntity, User $user): bool
    {
        return $this->permissionRepository->userHasReadAccess($user, $systemEntity);
    }

    private function canWrite(DomainEntityPermission $systemEntity, User $user): bool
    {
        return $this->permissionRepository->userHasWriteAccess($user, $systemEntity);
    }

    /**
     * Try to resolve a string (system entity code or name) to a DomainEntityPermission entity.
     */
    private function resolveSystemEntityFromString(string $subject): ?DomainEntityPermission
    {
        // Try to find by code first, then by name
        $systemEntity = $this->domainEntityRepository->findOneBy(['code' => $subject]);

        if (null === $systemEntity) {
            return $this->domainEntityRepository->findOneBy(['name' => $subject]);
        }

        return $systemEntity;
    }
}
