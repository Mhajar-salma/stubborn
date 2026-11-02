<?php

namespace App\Controller;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\String\ByteString;

class RegistrationController extends AbstractController
{
    #[Route('/register', name: 'app_register', methods: ['GET', 'POST'])]
    public function register(
        Request $request,
        EntityManagerInterface $entityManager,
        UserPasswordHasherInterface $passwordHasher
    ) {
        if ($request->isMethod('POST')) {
            $email = strtolower(trim($request->request->get('email', '')));
            $password = $request->request->get('password', '');

            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $this->addFlash('error', 'Adresse e-mail invalide.');
                return $this->redirectToRoute('app_register');
            }

            if (strlen($password) < 8) {
                $this->addFlash('error', 'Le mot de passe doit contenir au moins 8 caractères.');
                return $this->redirectToRoute('app_register');
            }

            if ($entityManager->getRepository(User::class)->findOneBy(['email' => $email])) {
                $this->addFlash('error', 'Cette adresse e-mail est déjà utilisée.');
                return $this->redirectToRoute('app_register');
            }

            $user = new User();
            $user->setEmail($email);
            $user->setRoles(['ROLE_USER']);
            $user->setPassword($passwordHasher->hashPassword($user, $password));
            $user->setIsVerified(false);

            $token = ByteString::fromRandom(32)->toString();
            $user->setVerificationToken($token);

            $entityManager->persist($user);
            $entityManager->flush();

            $verificationUrl = $this->generateUrl(
                'app_verify_email',
                ['token' => $token],
                UrlGeneratorInterface::ABSOLUTE_URL
            );

            $this->addFlash('success', 'Inscription réussie ! Active ton compte avec ce lien : ' . $verificationUrl);

            return $this->redirectToRoute('app_login');
        }

        return $this->render('registration/register.html.twig');
    }

    #[Route('/verify-email/{token}', name: 'app_verify_email', methods: ['GET'])]
    public function verifyEmail(string $token, EntityManagerInterface $entityManager)
    {
        $user = $entityManager->getRepository(User::class)
            ->findOneBy(['verificationToken' => $token]);

        if (!$user) {
            $this->addFlash('error', 'Lien de vérification invalide ou déjà utilisé.');
            return $this->redirectToRoute('app_login');
        }

        $user->setIsVerified(true);
        $user->setVerificationToken(null);
        $entityManager->flush();

        $this->addFlash('success', 'Ton compte est activé ! Tu peux maintenant te connecter.');

        return $this->redirectToRoute('app_login');
    }
}
