<?php

namespace App\Controller\Client;

use App\Entity\Livre;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Routing\Annotation\Route;

class CartWidgetController extends AbstractController
{
    #[Route('/mini-cart', name: 'client_mini_cart')]
    public function miniCart(SessionInterface $session, EntityManagerInterface $em): Response
    {
        $panier = $session->get('panier', []);

        $items = [];
        $total = 0;
        $totalQuantity = 0;

        foreach ($panier as $id => $quantite) {
            $livre = $em->getRepository(Livre::class)->find($id);
            if ($livre) {
                $items[] = [
                    'livre' => $livre,
                    'quantite' => $quantite,
                ];
                $total += $livre->getPu() * $quantite;
                $totalQuantity += $quantite;
            } else {
                // Nettoyage silencieux
                unset($panier[$id]);
            }
        }

        // Optionnel : remettre le panier nettoyé
        $session->set('panier', $panier);

        return $this->render('client/cart/mini_cart.html.twig', [
            'items'          => $items,
            'total'          => $total,
            'totalQuantity'  => $totalQuantity,
        ]);
    }
}