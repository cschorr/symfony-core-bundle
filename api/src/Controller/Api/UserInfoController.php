<?php

namespace App\Controller\Api;

use App\Service\JWTUserService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;

#[Route('/api', name: 'api_')]
class UserInfoController extends AbstractController
{
    #[Route('/userinfo', name: 'userinfo', methods: ['POST'])]
    public function userInfoFromToken(
        Request $request,
        JWTUserService $jwtUserService
    ): JsonResponse {
        // Extract token from Authorization header
        $authHeader = $request->headers->get('Authorization');

        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return $this->json(['error' => 'Authorization header with Bearer token required'], 400);
        }

        // Remove "Bearer " prefix to get the actual token
        $token = substr($authHeader, 7);

        if (empty($token)) {
            return $this->json(['error' => 'Token required'], 400);
        }

        $user = $jwtUserService->getUserFromToken($token);

        if (!$user) {
            return $this->json(['error' => 'Invalid token'], 401);
        }

        return $this->json([
            'id' => method_exists($user, 'getId') ? $user->getId() : null,
            'username' => $user->getUserIdentifier(),
            'roles' => $user->getRoles(),
            'firstName' => method_exists($user, 'getNameFirst') ? $user->getNameFirst() : null,
            'lastName' => method_exists($user, 'getNameLast') ? $user->getNameLast() : null,
            'isActive' => method_exists($user, 'isActive') ? $user->isActive() : true,
            'isLocked' => method_exists($user, 'isLocked') ? $user->isLocked() : false,
        ]);
    }
}
