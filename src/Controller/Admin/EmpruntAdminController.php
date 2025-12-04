<?php
// src/Controller/Admin/EmpruntAdminController.php

namespace App\Controller\Admin;

use App\Entity\Emprunt;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Stripe\Checkout\Session;
use Stripe\Stripe;

#[Route('/admin/emprunt')]
class EmpruntAdminController extends AbstractController
{
    public function __construct(
        private readonly AdminUrlGenerator $adminUrlGenerator // INJECTION ICI
    ) {}

    #[Route('/valider-cin/{id}', name: 'admin_emprunt_valider_cin')]
    public function validerCin(
        Emprunt $emprunt,
        EntityManagerInterface $em,
        MailerInterface $mailer,
        UrlGeneratorInterface $urlGenerator
    ): Response {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        if ($emprunt->getStatut() !== 'attente_validation_cin') {
            $this->addFlash('warning', 'Déjà traité.');
            return $this->redirectToRoute('admin');
        }

        Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);

        $session = Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => ['name' => 'Emprunt : ' . $emprunt->getLivre()->getTitre()],
                    'unit_amount' => (int)(($emprunt->getFraisEmprunt() + $emprunt->getCaution()) * 100),
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => $urlGenerator->generate('client_emprunt_success', ['id' => $emprunt->getId()], UrlGeneratorInterface::ABSOLUTE_URL),
            'cancel_url' => $urlGenerator->generate('client_emprunt_mes_emprunts', [], UrlGeneratorInterface::ABSOLUTE_URL),
        ]);

        $emprunt->setStripeSessionId($session->id);
        $emprunt->setStatut('attente_paiement');
        $emprunt->setCinValidated(true);
        $emprunt->setCinValidatedAt(new \DateTimeImmutable());
        // Mettre le statut en_cours si stripe_session_id est rempli et statut = attente_paiement
if ($emprunt->getStripeSessionId() !== null && $emprunt->getStatut() === 'attente_paiement') {
    $emprunt->setStatut('en_cours');
}
        $em->flush();
        $email = (new \Symfony\Component\Mime\Email())
            ->from('noorbenothmen78@gmail.com')
            ->to($emprunt->getEmprunteur()->getEmail())
            ->subject('Votre emprunt est validé – Payez maintenant')
            ->html($this->renderView('emails/emprunt_paiement.html.twig', [
                'emprunt' => $emprunt,
                'lien_paiement' => $session->url
            ]));

        $mailer->send($email);

        $this->addFlash('success', 'Lien de paiement envoyé !');
        return $this->redirectToRoute('admin');  
      }

      #[Route('/admin/emprunt/marque-retourne/{id}', name: 'admin_emprunt_marquer_retourne')]
public function marquerCommeRetourne(Emprunt $emprunt, EntityManagerInterface $em): Response
{
    $this->denyAccessUnlessGranted('ROLE_ADMIN');

    if ($emprunt->getStatut() !== 'en_cours') {
        $this->addFlash('warning', 'Cet emprunt ne peut pas être marqué comme retourné.');
        return $this->redirectToRoute('admin');
    }

    $emprunt->setStatut('retourne');
    $emprunt->setDateRetourReel(new \DateTimeImmutable());

    // Remettre le livre en stock
    $livre = $emprunt->getLivre();
    $livre->setQuantiteEnStock($livre->getQuantiteEnStock() + 1);

    $em->flush();

    $this->addFlash('success', 'Livre marqué comme retourné avec succès ! Stock mis à jour.');

    return $this->redirectToRoute('admin');
}
}