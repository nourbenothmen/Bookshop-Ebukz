<?php

namespace App\Controller\Client;

use App\Entity\Livre;
use App\Entity\Commande;
use App\Entity\CommandeItem;
use Doctrine\ORM\EntityManagerInterface;
use Stripe\Stripe;
use Stripe\Checkout\Session;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

#[Route('client/caisse')]
class CaisseController extends AbstractController
{
    #[Route('', name: 'client_caisse', methods: ['GET', 'POST'])]
    public function index(
        SessionInterface $session,
        EntityManagerInterface $em,
        Request $request
    ): Response {
        $panier = $session->get('panier', []);

        if (empty($panier)) {
            $this->addFlash('warning', 'Votre panier est vide.');
            return $this->redirectToRoute('client_panier');
        }

        // Construction du panier
        $items = [];
        $total = 0;
        foreach ($panier as $id => $quantite) {
            $livre = $em->getRepository(Livre::class)->find($id);
            if ($livre) {
                $items[] = [
                    'livre'     => $livre,
                    'quantite'  => $quantite,
                    'sousTotal' => $livre->getPu() * $quantite
                ];
                $total += $livre->getPu() * $quantite;
            }
        }

        // ==============================================
        // 1. PAIEMENT STRIPE → on redirige vers Stripe Checkout
        // ==============================================
        if ($request->request->get('payment_method') === 'stripe') {
            Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);

            $lineItems = [];
            foreach ($items as $item) {
                $lineItems[] = [
                    'price_data' => [
                        'currency' => 'eur',
                        'product_data' => ['name' => $item['livre']->getTitre()],
                        'unit_amount' => $item['livre']->getPu() * 100,
                    ],
                    'quantity' => $item['quantite'],
                ];
            }

            $checkout_session = Session::create([
                'payment_method_types' => ['card'],
                'line_items' => $lineItems,
                'mode' => 'payment',
                'success_url' => $this->generateUrl('client_caisse_success', [], UrlGeneratorInterface::ABSOLUTE_URL) . '?session_id={CHECKOUT_SESSION_ID}',
                'cancel_url'  => $this->generateUrl('client_caisse', [], UrlGeneratorInterface::ABSOLUTE_URL),
            ]);

            // SAUVEGARDE OBLIGATOIRE DES DONNÉES CLIENT
    $session->set('checkout_client_data', [
        'prenom' => $request->request->get('prenom'),
        'nom' => $request->request->get('nom'),
        'email' => $request->request->get('email'),
        'telephone' => $request->request->get('telephone'),
        'adresse' => $request->request->get('adresse'),
        'adresse_livraison' => $request->request->get('ship_to_different') 
            ? $request->request->get('ship_adresse') 
            : $request->request->get('adresse'),
        'notes' => $request->request->get('notes'),
    ]);

    return $this->redirect($checkout_session->url, 303);
        }

     
       // PAIEMENT À LA LIVRAISON (COD) → on accepte même si payment_method est vide (cas par défaut)
if ($request->isMethod('POST') && $request->request->get('payment_method') !== 'stripe') {

    $commande = new Commande();

    $commande->setPrenom($request->request->get('prenom'));
    $commande->setNom($request->request->get('nom'));
    $commande->setEmail($request->request->get('email'));
    $commande->setTelephone($request->request->get('telephone'));
    $commande->setAdresseFacturation($request->request->get('adresse'));

    if ($request->request->get('ship_to_different')) {
        $commande->setAdresseLivraison($request->request->get('ship_adresse'));
    } else {
        $commande->setAdresseLivraison($request->request->get('adresse'));
    }

    $commande->setNotes($request->request->get('notes') ?: null);
    $commande->setModePaiement('cod');
    $commande->setTotal((string)$total);
    $commande->setStatut('en_attente');
    $commande->setUtilisateur($this->getUser());

    foreach ($items as $item) {
        $cmdItem = new CommandeItem();
        $cmdItem->setLivre($item['livre']);
        $cmdItem->setQuantite($item['quantite']);
        $cmdItem->setPrixUnitaire((string)$item['livre']->getPu());
        $cmdItem->setCommande($commande);
        $commande->addItem($cmdItem);
    }

    $em->persist($commande);
    $em->flush();
    // === MISE À JOUR DU STOCK APRÈS COMMANDE COD ===
foreach ($items as $item) {
    $livre = $item['livre'];
    $quantiteCommandee = $item['quantite'];

    // Sécurité : on vérifie qu'il y a assez de stock
    if ($livre->getQuantiteEnStock() < $quantiteCommandee) {
        $this->addFlash('error', 'Stock insuffisant pour le livre : ' . $livre->getTitre());
        // Optionnel : annuler la commande ou la mettre en attente
        continue;
    }

    $livre->setQuantiteEnStock($livre->getQuantiteEnStock() - $quantiteCommandee);
    $em->persist($livre);
}
$em->flush(); // Deuxième flush pour mettre à jour le stock

    $session->remove('panier');
    $this->addFlash('success', 'Commande n°' . $commande->getId() . ' enregistrée avec succès !');

    return $this->redirectToRoute('client_caisse_success');
}

        // ==============================================
        // 3. AFFICHAGE DE LA PAGE CAISSE (GET)
        // ==============================================
        return $this->render('client/caisse/caisse.html.twig', [
            'items' => $items,
            'total' => $total,
            'stripe_public_key' => $_ENV['STRIPE_PUBLIC_KEY'] ?? '',
        ]);
    }



    #[Route('/success', name: 'client_caisse_success')]
public function success(
    Request $request,
    SessionInterface $session,
    EntityManagerInterface $em
): Response {
    $sessionId = $request->query->get('session_id');

    // Si on revient de Stripe
    if ($sessionId) {
        Stripe::setApiKey($_ENV['STRIPE_SECRET_KEY']);
        try {
            $checkoutSession = Session::retrieve($sessionId);

            if ($checkoutSession->payment_status === 'paid') {
                $clientData = $session->get('checkout_client_data');

                // Protection absolue : si les données client sont perdues
                if (!$clientData || !is_array($clientData)) {
                    $this->addFlash('error', 'Données de commande perdues. Veuillez recommencer.');
                    return $this->redirectToRoute('client_caisse');
                }

                $panier = $session->get('panier', []);
                if (empty($panier)) {
                    $this->addFlash('error', 'Panier vide.');
                    return $this->redirectToRoute('client_livre_index');
                }

                // Reconstruction du total
                $total = 0;
                $items = [];
                foreach ($panier as $id => $quantite) {
                    $livre = $em->getRepository(Livre::class)->find($id);
                    if ($livre) {
                        $items[] = [
                            'livre' => $livre,
                            'quantite' => $quantite,
                            'sousTotal' => $livre->getPu() * $quantite
                        ];
                        $total += $livre->getPu() * $quantite;
                    }
                }

                // ENREGISTREMENT DE LA COMMANDE
                $commande = new Commande();
                $commande->setPrenom($clientData['prenom'] ?? '');
                $commande->setNom($clientData['nom'] ?? '');
                $commande->setEmail($clientData['email'] ?? '');
                $commande->setTelephone($clientData['telephone'] ?? '');
                $commande->setAdresseFacturation($clientData['adresse'] ?? '');
                $commande->setAdresseLivraison($clientData['adresse_livraison'] ?? $clientData['adresse'] ?? '');
                $commande->setNotes($clientData['notes'] ?? null);
                $commande->setModePaiement('stripe');
                $commande->setTotal((string)$total);
                $commande->setStatut('payee');
                $commande->setUtilisateur($this->getUser());

                foreach ($items as $item) {
                    $cmdItem = new CommandeItem();
                    $cmdItem->setLivre($item['livre']);
                    $cmdItem->setQuantite($item['quantite']);
                    $cmdItem->setPrixUnitaire((string)$item['livre']->getPu());
                    $cmdItem->setCommande($commande);
                    $commande->addItem($cmdItem);
                }

                $em->persist($commande);
                // === MISE À JOUR DU STOCK APRÈS PAIEMENT STRIPE ===
foreach ($items as $item) {
    $livre = $item['livre'];
    $quantiteCommandee = $item['quantite'];

    if ($livre->getQuantiteEnStock() < $quantiteCommandee) {
        $this->addFlash('error', 'Stock épuisé pour : ' . $livre->getTitre());
        // Tu peux même annuler la session Stripe si tu veux
        continue;
    }

    $livre->setQuantiteEnStock($livre->getQuantiteEnStock() - $quantiteCommandee);
    $em->persist($livre);
}
                $em->flush();

                // Nettoyage complet
                $session->remove('panier');
                $session->remove('checkout_client_data');

                $this->addFlash('success', 'Paiement Stripe accepté ! Commande n°' . $commande->getId() . ' confirmée.');
            } else {
                $this->addFlash('error', 'Le paiement n\'a pas été effectué.');
            }
        } catch (\Exception $e) {
            $this->addFlash('error', 'Erreur Stripe : ' . $e->getMessage());
        }
    } else {
        // Si quelqu’un arrive sur /success sans session_id → on redirige
        $this->addFlash('info', 'Aucune commande à confirmer.');
    }

    return $this->render('client/caisse/success.html.twig');
}
}