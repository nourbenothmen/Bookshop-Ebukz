<?php
// src/Controller/Client/EmpruntController.php

namespace App\Controller\Client;

use App\Entity\Livre;
use App\Entity\Emprunt;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext; // CETTE LIGNE MANQUE !
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use Stripe\Stripe;
use Stripe\Checkout\Session;

#[Route('/client/emprunt')]
#[IsGranted('ROLE_USER')]                   
class EmpruntController extends AbstractController
{
    #[Route('/ajouter/{id}', name: 'client_emprunt_ajouter', methods: ['POST'])]
    public function ajouterAuPanierEmprunt(Livre $livre, Request $request, EntityManagerInterface $em): Response
    {
        if ($livre->getQuantiteEnStock() < 1) {
            $this->addFlash('danger', 'Ce livre n\'est pas disponible à l\'emprunt.');
            return $this->redirectToRoute('client_livre_show', ['id' => $livre->getId()]);
        }

        // Vérifier si l'utilisateur a déjà un emprunt en cours
        $empruntEnCours = $em->getRepository(Emprunt::class)->findOneBy([
            'emprunteur' => $this->getUser(),
            'statut' => 'en_cours'
        ]);

        if ($empruntEnCours) {
            $this->addFlash('warning', 'Vous avez déjà un livre en cours d\'emprunt. Terminez-le avant d\'en emprunter un autre.');
            return $this->redirectToRoute('client_emprunt_panier');
        }

        // Ajouter au panier d'emprunt (session)
        $session = $request->getSession();
        $panierEmprunt = $session->get('panier_emprunt', []);
        
        // 1 seul livre max
        if (!empty($panierEmprunt)) {
            $this->addFlash('info', 'Vous ne pouvez emprunter qu\'un seul livre à la fois. Votre panier a été remplacé.');
        }

        $panierEmprunt = [$livre->getId() => 1]; // Quantité fixe = 1
        $session->set('panier_emprunt', $panierEmprunt);

        $this->addFlash('success', 'Livre ajouté à votre panier d\'emprunt !');
        return $this->redirectToRoute('client_emprunt_panier');
    }

    #[Route('/panier', name: 'client_emprunt_panier')]
    public function panier(Request $request, EntityManagerInterface $em): Response
    {
        $session = $request->getSession();
        $panier = $session->get('panier_emprunt', []);

        $items = [];
        $total = 0;

        foreach ($panier as $id => $quantite) {
            $livre = $em->getRepository(Livre::class)->find($id);
            if ($livre) {
                $frais = (float)$livre->getFraisEmprunt();
                $caution = (float)$livre->getCaution();
                $total += $frais + $caution;

                $items[] = [
                    'livre' => $livre,
                    'quantite' => 1,
                    'frais' => $frais,
                    'caution' => $caution,
                    'totalLigne' => $frais + $caution
                ];
            }
        }

        return $this->render('client/emprunt/panier.html.twig', [
            'items' => $items,
            'total' => $total
        ]);
    }

    #[Route('/payer', name: 'client_emprunt_payer', methods: ['POST'])]
    public function payer(Request $request, EntityManagerInterface $em): Response
    {

        if (!$request->getSession()->get('cin_uploaded')) {
        $this->addFlash('danger', 'Vous devez envoyer votre C.I.N. avant de payer.');
        return $this->redirectToRoute('client_emprunt_panier');
        }
        $session = $request->getSession();
        $panier = $session->get('panier_emprunt', []);

        if (empty($panier)) {
            return $this->redirectToRoute('client_emprunt_panier');
        }

        $livreId = array_keys($panier)[0];
        $livre = $em->getRepository(Livre::class)->find($livreId);

        Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);

        $checkout_session = Session::create([
            'payment_method_types' => ['card'],
            'line_items' => [[
                'price_data' => [
                    'currency' => 'eur',
                    'product_data' => [
                        'name' => 'Emprunt : ' . $livre->getTitre(),
                        'description' => 'Frais + Caution remboursable',
                    ],
                    'unit_amount' => (int)(($livre->getFraisEmprunt() + $livre->getCaution()) * 100),
                ],
                'quantity' => 1,
            ]],
            'mode' => 'payment',
            'success_url' => $this->generateUrl('client_emprunt_success', [], UrlGeneratorInterface::ABSOLUTE_URL) . '?session_id={CHECKOUT_SESSION_ID}',
            'cancel_url' => $this->generateUrl('client_emprunt_panier', [], UrlGeneratorInterface::ABSOLUTE_URL),
            'metadata' => [
                'type' => 'emprunt',
                'livre_id' => $livre->getId(),
                'user_id' => $this->getUser()->getId()
            ]
        ]);

        return $this->redirect($checkout_session->url, 303);
    }

 #[Route('/success', name: 'client_emprunt_success')]
public function success(Request $request, EntityManagerInterface $em): Response
{
    $sessionId = $request->query->get('session_id');
    if (!$sessionId) {
        return $this->redirectToRoute('client_livre_index');
    }

    Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);
    $checkout = Session::retrieve($sessionId);

    if ($checkout->payment_status === 'paid' && $checkout->metadata->type === 'emprunt') {
        $livre = $em->getRepository(Livre::class)->find($checkout->metadata->livre_id);

        if (!$livre || $livre->getQuantiteEnStock() < 1) {
            $this->addFlash('danger', 'Stock épuisé ! Emprunt annulé.');
            return $this->redirectToRoute('client_emprunt_mes_emprunts');
        }

        $emprunt = new Emprunt();
        $emprunt->setEmprunteur($this->getUser());
        $emprunt->setLivre($livre);
        $emprunt->setFraisEmprunt($livre->getFraisEmprunt());
        $emprunt->setCaution($livre->getCaution());
        $emprunt->setDateEmprunt(new \DateTime());
        $emprunt->setDateRetourPrevue((new \DateTime())->modify('+ ' . $livre->getDureeMaxEmprunt() . ' days'));
        $emprunt->setStatut('en_cours'); // ← IMPORTANT
        $emprunt->setStripeSessionId($sessionId);

        // DÉDUCTION DU STOCK LORSQUE L'EMPRUNT COMMENCE
        $livre->setQuantiteEnStock($livre->getQuantiteEnStock() - 1);

        $em->persist($emprunt);
        $em->persist($livre); // Important !
        $em->flush();

        $request->getSession()->remove('panier_emprunt');
        $this->addFlash('success', 'Emprunt confirmé ! À rendre avant le ' . $emprunt->getDateRetourPrevue()->format('d/m/Y'));
    }

    return $this->redirectToRoute('client_emprunt_mes_emprunts');
}

    #[Route('/vider-panier', name: 'client_emprunt_vider')]
    public function vider(Request $request): Response
    {
        $request->getSession()->remove('panier_emprunt');
        $this->addFlash('info', 'Panier d\'emprunt vidé.');
        return $this->redirectToRoute('client_livre_index');
    }

#[Route('/upload-cin', name: 'client_emprunt_upload_cin', methods: ['POST'])]
public function uploadCin(Request $request, EntityManagerInterface $em): Response
{
    $recto = $request->files->get('cin_recto');
    $verso = $request->files->get('cin_verso');

    if (!$recto || !$verso) {
        $this->addFlash('danger', 'Les deux côtés de la C.I.N. sont obligatoires.');
        return $this->redirectToRoute('client_emprunt_panier');
    }

    $session = $request->getSession();
    $panier = $session->get('panier_emprunt', []);

    // PROTÉGER CONTRE PANIER VIDE
    if (empty($panier)) {
        $this->addFlash('danger', 'Votre panier est vide. Veuillez choisir un livre à emprunter.');
        return $this->redirectToRoute('client_livre_index');
    }

    $livreId = array_keys($panier)[0] ?? null;
    $livre = $livreId ? $em->getRepository(Livre::class)->find($livreId) : null;

    if (!$livre || $livre->getQuantiteEnStock() < 1) {
        $this->addFlash('danger', 'Ce livre n\'est plus disponible à l\'emprunt.');
        $session->remove('panier_emprunt');
        return $this->redirectToRoute('client_livre_index');
    }

    // Upload des fichiers
    $rectoName = 'cin_recto_' . uniqid() . '.' . $recto->guessExtension();
    $versoName = 'cin_verso_' . uniqid() . '.' . $verso->guessExtension();
    $recto->move($this->getParameter('cin_directory'), $rectoName);
    $verso->move($this->getParameter('cin_directory'), $versoName);

    // Créer l'emprunt en attente
    $emprunt = new Emprunt();
    $emprunt->setEmprunteur($this->getUser());
    $emprunt->setLivre($livre);
    $emprunt->setFraisEmprunt($livre->getFraisEmprunt());
    $emprunt->setCaution($livre->getCaution());
    $emprunt->setDateRetourPrevue((new \DateTime())->modify('+ ' . $livre->getDureeMaxEmprunt() . ' days'));
    $emprunt->setStatut('attente_validation_cin');
    $emprunt->setCinRecto($rectoName);
    $emprunt->setCinVerso($versoName);
    $emprunt->setCinValidated(false);

    $em->persist($emprunt);
    $em->flush();

    // Vider le panier
    $session->remove('panier_emprunt');

    $this->addFlash('success', 'C.I.N. envoyée avec succès ! Notre équipe va la vérifier sous 24h. Vous recevrez le lien de paiement par e-mail.');
    
    return $this->redirectToRoute('client_emprunt_mes_emprunts');
}
private function uploadFile($file, $prefix): string
{
    $filename = $prefix . '_' . uniqid() . '.' . $file->guessExtension();
    $file->move($this->getParameter('cin_directory'), $filename);
    return $filename;
}

    #[Route('/mes-emprunts', name: 'client_emprunt_mes_emprunts', methods: ['GET'])]
    public function mesEmprunts(EntityManagerInterface $em): Response
    {
        $emprunts = $em->getRepository(Emprunt::class)->findBy(
            ['emprunteur' => $this->getUser()],
            ['dateEmprunt' => 'DESC']
        );

        return $this->render('client/emprunt/mes_emprunts.html.twig', [
            'emprunts' => $emprunts
        ]);
    }



/*
    #[Route('/retourner/{id}', name: 'client_emprunt_retourner', methods: ['POST'])]
#[IsGranted('ROLE_USER')]
public function retourner(Emprunt $emprunt, EntityManagerInterface $em): Response
{
    if ($emprunt->getEmprunteur() !== $this->getUser()) {
        throw $this->createAccessDeniedException();
    }

    if ($emprunt->getStatut() !== 'en_cours') {
        $this->addFlash('warning', 'Cet emprunt ne peut pas être retourné.');
        return $this->redirectToRoute('client_emprunt_mes_emprunts');
    }

    // CHANGEMENT DE STATUT
    $emprunt->setStatut('retourne');
    $emprunt->setDateRetourEffective(new \DateTime());

    // REMISE EN STOCK +1
    $livre = $emprunt->getLivre();
    $livre->setQuantiteEnStock($livre->getQuantiteEnStock() + 1);

    $em->persist($emprunt);
    $em->persist($livre);
    $em->flush();

    $this->addFlash('success', 'Livre retourné avec succès ! Merci');
    return $this->redirectToRoute('client_emprunt_mes_emprunts');
}*/
}