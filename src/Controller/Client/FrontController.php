<?php

namespace App\Controller\Client;
use App\Entity\CommandeItem;
use App\Repository\LivreRepository;
use App\Repository\EditeurRepository;
use App\Repository\CategorieRepository;
use App\Repository\PromotionRepository;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class FrontController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function home(
        CategorieRepository $categorieRepository,
        EditeurRepository $editeurRepository,
        LivreRepository $livreRepository,
        PromotionRepository $promotionRepository,
        PaginatorInterface $paginator,
        Request $request
    ): Response
    {
        $now = new \DateTime();


        
    // Récupérer les promotions actives aujourd'hui
    $promotions = $promotionRepository->createQueryBuilder('p')
        ->join('p.livre', 'l') // joindre le livre
        ->addSelect('l')
        ->where('p.dateDebut <= :today')
        ->andWhere('p.dateFin >= :today')
        ->andWhere('p.active = true')
        ->setParameter('today', $now->format('Y-m-d H:i:s'))
        ->orderBy('p.dateDebut', 'DESC')
        ->getQuery()
        ->getResult();
        // ===========================
        // Pagination des catégories
        // ===========================
        $queryCategories = $categorieRepository->createQueryBuilder('c')
            ->orderBy('c.id', 'ASC')
            ->getQuery();

        $pageCategorie = $request->query->getInt('page_categories', 1); // page pour catégories

        $categories = $paginator->paginate(
            $queryCategories,
            $pageCategorie,
            6
        );

        // ===========================
        // Pagination des éditeurs
        // ===========================
        $queryEditeurs = $editeurRepository->createQueryBuilder('e')
            ->orderBy('e.id', 'ASC')
            ->getQuery();

        $pageEditeur = $request->query->getInt('page_editeurs', 1); // page pour éditeurs

        $editeurs = $paginator->paginate(
            $queryEditeurs,
            $pageEditeur,
            6
        );

        $now = new \DateTime();
    $firstDayThisMonth = (clone $now)->modify('first day of this month 00:00:00');
    $firstDayLastMonth = (clone $firstDayThisMonth)->modify('-1 month');

    // LIVRES LES PLUS VENDUS CE MOIS-CI
    $topThisMonth = $livreRepository->createQueryBuilder('l')
        ->select('l.id, l.titre, l.image, l.pu, SUM(ci.quantite) as ventes')
        ->join('l.commandeItems', 'ci')
        ->join('ci.commande', 'c')
        ->where('c.statut IN (:statuts)')
        ->andWhere('c.dateCommande >= :debut')
        ->setParameter('statuts', ['payee', 'expediee', 'livree'])
        ->setParameter('debut', $firstDayThisMonth)
        ->groupBy('l.id')
        ->orderBy('ventes', 'DESC')
        ->setMaxResults(6)
        ->getQuery()
        ->getResult();

    // LIVRES LES PLUS VENDUS LE MOIS DERNIER
    $topLastMonth = $livreRepository->createQueryBuilder('l')
        ->select('l.id, l.titre, l.image, l.pu, SUM(ci.quantite) as ventes')
        ->join('l.commandeItems', 'ci')
        ->join('ci.commande', 'c')
        ->where('c.statut IN (:statuts)')
        ->andWhere('c.dateCommande >= :debut')
        ->andWhere('c.dateCommande < :fin')
        ->setParameter('statuts', ['payee', 'expediee', 'livree'])
        ->setParameter('debut', $firstDayLastMonth)
        ->setParameter('fin', $firstDayThisMonth)
        ->groupBy('l.id')
        ->orderBy('ventes', 'DESC')
        ->setMaxResults(6)
        ->getQuery()
        ->getResult();

    return $this->render('client/home.html.twig', [
        'categorie'      => $categories,
        'editeurs'       => $editeurs,
        'topThisMonth'   => $topThisMonth,
        'topLastMonth'   => $topLastMonth,
        'moisActuel'     => $now->format('F Y'),
        'moisPrecedent'  => (clone $firstDayLastMonth)->format('F Y'),
        'promotions'  => $promotions, // on envoie les promotions
    ]);
    }
/*
      #[Route('client/whishlist',name: 'client_whishlist', methods: ['GET'])]
    public function index(CategorieRepository $categorieRepository): Response
    {
        return $this->render('client/wishlist/wishlist.html.twig', [
          'categorie' => $categorieRepository->findAll(),
        ]);
    }*/
    

}
