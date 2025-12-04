<?php

namespace App\Controller;

use App\Entity\User;
use App\Form\RegistrationFormType;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\MailerInterface;
use SymfonyCasts\Bundle\VerifyEmail\VerifyEmailHelperInterface;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mime\Address;

class RegistrationController extends AbstractController
{
    public function __construct(
        private VerifyEmailHelperInterface $verifyEmailHelper,
        private MailerInterface $mailer,
        private EntityManagerInterface $em,
        private UserRepository $userRepository
    ) {}

    #[Route('/inscription', name: 'app_register')]
    public function register(Request $request): Response
    {
        $user = new User();
        $user->setRoles(['ROLE_USER']);

        $form = $this->createForm(RegistrationFormType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // Hash du mot de passe
            $hashedPassword = password_hash(
                $form->get('plainPassword')->getData(),
                PASSWORD_BCRYPT
            );
            $user->setPassword($hashedPassword);

            $this->em->persist($user);
            $this->em->flush();

            // Génération du lien signé
            $signature = $this->verifyEmailHelper->generateSignature(
                'app_verify_email',
                $user->getId(),
                $user->getEmail(),
                ['id' => $user->getId()]
            );

            // Envoi email confirmation
            $email = (new TemplatedEmail())
                ->from(new Address('no-reply@bookshop.tn', 'Ebukz'))
                ->to($user->getEmail())
                ->subject('Activez votre compte')
                ->htmlTemplate('registration/confirmation_email.html.twig')
                ->context([
                    'signedUrl' => $signature->getSignedUrl(),
                    'expiresAt' => $signature->getExpiresAt(),
                ]);

            $this->mailer->send($email);

            $this->addFlash('success', 'Vérifiez votre email pour activer votre compte.');
            return $this->redirectToRoute('app_login');
        }

        return $this->render('registration/register.html.twig', [
            'registrationForm' => $form->createView(),
        ]);
    }

    #[Route('/verify/email', name: 'app_verify_email')]
    public function verifyEmail(Request $request): Response
    {
        $userId = $request->query->get('id');

        if (!$userId) {
            $this->addFlash('danger', 'Lien invalide');
            return $this->redirectToRoute('app_login');
        }

        $user = $this->userRepository->find($userId);

        if (!$user) {
            $this->addFlash('danger', 'Utilisateur introuvable.');
            return $this->redirectToRoute('app_login');
        }

        // Validation du lien signé
        try {
            $this->verifyEmailHelper->validateEmailConfirmation(
                $request->getUri(),
                $userId,
                $user->getEmail()
            );
        } catch (\Exception $e) {
            $this->addFlash('danger', 'Lien invalide ou expiré.');
            return $this->redirectToRoute('app_login');
        }

        // Mise à jour
        $user->setIsVerified(true);
        $this->em->flush();

        $this->addFlash('success', 'Votre compte est activé !');
        return $this->redirectToRoute('app_login');
    }
}
