<?php

namespace App\Security\Voter;

use App\Entity\SystemEntity;
use App\Entity\User;
use App\Repository\UserSystemEntityPermissionRepository;
use App\Repository\SystemEntityRepository;
use Psr\Log\LoggerInterface;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\User\UserInterface;

class SystemEntityVoter extends Voter
{
    public const READ = 'read';
    public const WRITE = 'write';
    public const EDIT = 'edit';
    public const DELETE = 'delete';

    public function __construct(
        private readonly UserSystemEntityPermissionRepository $permissionRepository,
        private readonly SystemEntityRepository $systemEntityRepository,
        private readonly LoggerInterface $logger
    ) {
    }

    protected function supports(string $attribute, mixed $subject): bool
    {
        // Support both SystemEntity entities and system entity codes/names (strings)
        return in_array($attribute, [self::READ, self::WRITE, self::EDIT, self::DELETE])
            && ($subject instanceof SystemEntity || is_string($subject));
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token, ?Vote $vote = null): bool
    {
        $user = $token->getUser();

        // User must be logged in
        if (!$user instanceof UserInterface) {
            return false;
        }

        // Admin users have all permissions
        if ($user instanceof User && in_array('ROLE_ADMIN', $user->getRoles())) {
            return true;
        }

        // Get the system entity
        $systemEntity = $subject;
        if (is_string($subject)) {
            // Try to resolve string to SystemEntity entity
            $systemEntity = $this->resolveSystemEntityFromString($subject);
            if (!$systemEntity) {
                // Debug: Log when resolution fails
                $this->logger->debug("SystemEntityVoter: Could not resolve '{$subject}' to SystemEntity");
                return false;
            }
            // Debug: Log successful resolution
            $this->logger->debug("SystemEntityVoter: Resolved '{$subject}' to SystemEntity ID: {$systemEntity->getId()}");
        }

        if (!$systemEntity instanceof SystemEntity) {
            return false;
        }

        // Check specific permission
        switch ($attribute) {
            case self::READ:
                $canRead = $this->canRead($systemEntity, $user);
                error_log("SystemEntityVoter: User {$user->getUserIdentifier()} can read {$systemEntity->getCode()}: " . ($canRead ? 'YES' : 'NO'));
                return $canRead;
            case self::WRITE:
            case self::EDIT:
            case self::DELETE:
                $canWrite = $this->canWrite($systemEntity, $user);
                error_log("SystemEntityVoter: User {$user->getUserIdentifier()} can write {$systemEntity->getCode()}: " . ($canWrite ? 'YES' : 'NO'));
                return $canWrite;
        }

        return false;
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
     * Try to resolve a string (system entity code or name) to a SystemEntity entity
     */
    private function resolveSystemEntityFromString(string $subject): ?SystemEntity
    {
        // Try to find by code first, then by name
        $systemEntity = $this->systemEntityRepository->findOneBy(['code' => $subject]);

        if (!$systemEntity) {
            $systemEntity = $this->systemEntityRepository->findOneBy(['name' => $subject]);
        }

        return $systemEntity;
    }
}
