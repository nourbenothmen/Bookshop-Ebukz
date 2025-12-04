<?php
// src/Controller/StripeWebhookController.php

// src/Controller/Webhook/StripeWebhookController.php
namespace App\Controller;

use App\Entity\Emprunt;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Stripe\Stripe;
use Stripe\Webhook;
use Stripe\Exception\SignatureVerificationException;

class StripeWebhookController extends AbstractController
{
    public function __construct(private readonly EntityManagerInterface $em) {}

    #[Route('/webhook/stripe', name: 'stripe_webhook', methods: ['POST'])]
    public function __invoke(Request $request): Response
    {
        Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);
        $payload = $request->getContent();
        $sigHeader = $request->headers->get('stripe-signature');
        $webhookSecret = $_ENV['STRIPE_WEBHOOK_SECRET'];

        try {
            $event = Webhook::constructEvent($payload, $sigHeader, $webhookSecret);
        } catch (SignatureVerificationException $e) {
            return new Response('Invalid signature', 400);
        } catch (\UnexpectedValueException $e) {
            return new Response('Invalid payload', 400);
        }

        // On ne traite que les checkout.session.completed
        if ($event->type === 'checkout.session.completed') {
            $session = $event->data->object;
            $emprunt = $this->em->getRepository(Emprunt::class)
                ->findOneBy(['stripeSessionId' => $session->id]);

            if ($emprunt && $emprunt->getStatut() === 'attente_paiement') {
                $emprunt->setStatut('en_cours');
                $emprunt->setPayeAt(new \DateTimeImmutable());
                $this->em->flush();
            }
        }

        return new Response('OK', 200);
    }
}
