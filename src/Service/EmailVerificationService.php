<?php
namespace App\Service;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;

class EmailVerificationService
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MailerInterface $mailer
    ) {}

    // Add the (User $user) argument here
    public function generateVerificationToken(User $user): string
    {
        $token = bin2hex(random_bytes(32));
        
        // THIS IS THE MISSING LINE:
        $user->setVerificationToken($token);
        
        return $token;
    }
    public function sendVerificationEmail(User $user, string $verificationUrl): void
    {
        $email = (new TemplatedEmail())
            ->from(new Address('sophia@gmail.com', 'Grade Tracker App'))
            ->to(new Address($user->getUsername()))
            ->subject('Please verify your email address')
            ->htmlTemplate('emails/verification.html.twig')
            ->context([
                'user'            => $user,
                'verificationUrl' => $verificationUrl,
            ]);

        $this->mailer->send($email);
    }

    public function verifyToken(string $token): ?User
    {
        $user = $this->entityManager
            ->getRepository(User::class)
            ->findOneBy(['verificationToken' => $token]);

        if (!$user) {
            return null;
        }

        $user->setIsVerified(true);
        $user->setVerificationToken(null);
        $this->entityManager->flush();

        return $user;
    }

    public function needsVerification(User $user): bool
    {
        return !$user->isVerified();
    }
}