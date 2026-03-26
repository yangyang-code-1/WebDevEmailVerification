<?php

namespace App\Controller;

use App\Service\EmailVerificationService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse; // Use JSON
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Attribute\Route;

class EmailVerificationController extends AbstractController
{
    #[Route('/api/verify', name: 'api_verify_email', methods: ['GET'])]
    public function verifyUserEmail(
        Request $request,
        EmailVerificationService $emailService
    ): JsonResponse {
        $token = $request->query->get('token');

        if (!$token) {
            return $this->json(['error' => 'Verification token is missing.'], 400);
        }

        $user = $emailService->verifyToken($token);

        if (!$user) {
            return $this->json(['error' => 'Invalid or expired verification token.'], 400);
        }

        return $this->json([
            'message' => 'Your email has been verified! You can now log in.',
            'username' => $user->getUsername()
        ], 200);
    }
}