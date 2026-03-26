<?php

namespace App\Controller;

use App\Entity\User;
use App\Service\EmailVerificationService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;

class RegistrationController extends AbstractController
{
    #[Route('/api/register', name: 'api_register', methods: ['POST'])]
    public function register(
        Request $request, 
        UserPasswordHasherInterface $hasher, 
        EntityManagerInterface $em,
        EmailVerificationService $emailService
    ): JsonResponse {
        $data = json_decode($request->getContent(), true);

        // Check if username already exists
        $existingUser = $em->getRepository(User::class)->findOneBy(['username' => $data['username']]);
        if ($existingUser) {
            return $this->json(['message' => 'Username already taken'], 409);
        }
        
        // 1. Create the User object
        $user = new User();
        $user->setUsername($data['username']);
        $user->setRoles(['ROLE_USER']);
        $user->setIsVerified(false); 

        // 2. Hash the password
        $hashedPassword = $hasher->hashPassword($user, $data['password']);
        $user->setPassword($hashedPassword);

        // 3. Generate the verification token using your Service
        $emailService->generateVerificationToken($user);

        // 4. Save to database
        $em->persist($user);
        $em->flush();

        return $this->json([
            'message' => 'User registered successfully! Check your console for the verification token.',
            'username' => $user->getUsername()
        ], 201);
    }
}
