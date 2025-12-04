<?php

namespace App\Controller\Client;

use App\Entity\Livre;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Repository\CategorieRepository;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/panier')]
class PanierController extends AbstractController
{
    #[Route('/ajouter/{id}', name: 'client_panier_ajouter')]
    public function ajouter(Livre $livre, SessionInterface $session, Request $request): Response
    {
        $panier = $session->get('panier', []);
        $id = $livre->getId();

        $panier[$id] = ($panier[$id] ?? 0) + 1;

        $session->set('panier', $panier);

        $this->addFlash('success', "Le livre '{$livre->getTitre()}' a été ajouté au panier !");

        return $this->redirect($request->headers->get('referer') ?: $this->generateUrl('client_livre_index'));
    }

   #[Route('', name: 'client_panier')]
public function index(
    SessionInterface $session,
    EntityManagerInterface $em,
    CategorieRepository $categorieRepository  // ← on injecte le repo
): Response
{
    $panier = $session->get('panier', []);
    $data = ['livres' => [], 'total' => 0];

    foreach ($panier as $id => $qte) {
        $livre = $em->getRepository(Livre::class)->find($id);
        if ($livre) {
            $data['livres'][$id] = $livre;
            $data['total'] += $livre->getPu() * $qte;
        } else {
            unset($panier[$id]);
        }
    }

    $session->set('panier', $panier);

    // ON AJOUTE LES CATÉGORIES ICI
    $categorie = $categorieRepository->findBy([], ['designation' => 'ASC']);

    return $this->render('client/panier/panier.html.twig', [
        'panier'     => $panier,
        'livres'     => $data['livres'],
        'total'      => $data['total'],
        'categorie' => $categorie,        // ou 'categorie' si ton layout utilise ce nom
    ]);
}

    #[Route('/update-quantity/{id}', name: 'client_panier_update_qty', methods: ['POST'])]
    public function updateQuantity(int $id, Request $request, SessionInterface $session, EntityManagerInterface $em): JsonResponse
    {
        $data = json_decode($request->getContent(), true);
        $quantity = max(1, (int)($data['quantity'] ?? 1));

        $panier = $session->get('panier', []);

        $livre = $em->getRepository(Livre::class)->find($id);

        if (!$livre) {
            unset($panier[$id]);
        } else {
            if ($quantity <= 0) {
                unset($panier[$id]);
            } else {
                $panier[$id] = $quantity;
            }
        }

        $session->set('panier', $panier);

        // Calcul du total
        $total = 0;
        foreach ($panier as $lid => $q) {
            $l = $em->getRepository(Livre::class)->find($lid);
            if ($l) $total += $l->getPu() * $q;
        }

        return $this->json([
            'success' => true,
            'qte' => $panier[$id] ?? 0,
            'totalGeneral' => $total,
            'totalQuantity' => array_sum($panier) // ← important !0
        ]);
    }

    #[Route('/supprimer/{id}', name: 'client_panier_supprimer')]
    public function supprimer(int $id, SessionInterface $session): Response
    {
        $panier = $session->get('panier', []);
        unset($panier[$id]);
        $session->set('panier', $panier);

        $this->addFlash('success', 'Article supprimé du panier');
        return $this->redirectToRoute('client_panier');
    }
}