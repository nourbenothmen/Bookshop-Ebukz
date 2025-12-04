<?php

namespace App\Controller;
use App\Entity\Wishlist;
use App\Entity\Livre;
use Doctrine\ORM\EntityManagerInterface;
use App\Repository\CategorieRepository;
use App\Repository\WishlistRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/wishlist')]
//#[IsGranted('ROLE_USER')]
class WishlistController extends AbstractController
{
    #[Route('/', name: 'client_wishlist_index')]
    public function index(WishlistRepository $repo,CategorieRepository $categorieRepository): Response
    {
        $wishlist = $repo->findBy(['user' => $this->getUser()], ['addedAt' => 'DESC']);

        return $this->render('client/wishlist/wishlist.html.twig', [
            'wishlist' => $wishlist,
            'categorie' => $categorieRepository->findAll(),
        ]);
    }
#[Route('/wishlist/mini', name: 'client_wishlist_mini')]
    public function miniWishlist(WishlistRepository $wishlistRepo): Response
    {
        $user = $this->getUser();
        $count = 0;

        if ($user) {
            $count = $wishlistRepo->count(['user' => $user]);
        }

        return $this->render('client/wishlist/_mini_wishlist.html.twig', [
            'wishlist_count' => $count,
        ]);
    }

    #[Route('/ajouter/{id}', name: 'client_wishlist_ajouter', methods: ['POST'])]
    public function ajouter(Livre $livre, EntityManagerInterface $em): Response
    {
        $user = $this->getUser();

        // Vérifie s’il est déjà dans la wishlist
        $exists = $em->getRepository(Wishlist::class)->findOneBy([
            'user' => $user,
            'livre' => $livre
        ]);

        if (!$exists) {
            $wishlist = new Wishlist();
            $wishlist->setUser($user);
            $wishlist->setLivre($livre);
            $em->persist($wishlist);
            $em->flush();

            $this->addFlash('success', 'Livre ajouté à votre wishlist !');
        } else {
            $this->addFlash('info', 'Ce livre est déjà dans votre wishlist');
        }

        return $this->redirectToRoute('client_livre_index');
    }

    #[Route('/supprimer/{id}', name: 'client_wishlist_supprimer')]
    public function supprimer(Wishlist $wishlist, EntityManagerInterface $em): Response
    {
        if ($wishlist->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $em->remove($wishlist);
        $em->flush();

        $this->addFlash('success', 'Livre retiré de la wishlist');
        return $this->redirectToRoute('client_wishlist_index');
    }
}