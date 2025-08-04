<?php

namespace App\Service;

use App\Repository\UserRepository;
use Lexik\Bundle\JWTAuthenticationBundle\Services\JWTTokenManagerInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class JWTUserService
{
    public function __construct(
        private JWTTokenManagerInterface $jwtManager,
        private UserRepository $userRepository
    ) {}

    public function getUserFromToken(string $token): ?UserInterface
    {
        try {
            $payload = $this->jwtManager->parse($token);
            $username = $payload['username'] ?? null;

            if (!$username) {
                return null;
            }
            return $this->userRepository->findOneBy(['email' => $username]);

            #return $this->userProvider->loadUserByIdentifier($username);
        } catch (\Exception $e) {
            return null;
        }
    }
}
