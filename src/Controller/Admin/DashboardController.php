<?php

namespace App\Controller\Admin;

use App\Repository\LivreRepository;
use App\Repository\CommandeRepository;
use App\Repository\UserRepository;
use App\Repository\EmpruntRepository;
use Doctrine\Persistence\ManagerRegistry;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractDashboardController;
use Symfony\Component\HttpFoundation\Response;
use EasyCorp\Bundle\EasyAdminBundle\Config\Dashboard;
use EasyCorp\Bundle\EasyAdminBundle\Config\MenuItem;
use EasyCorp\Bundle\EasyAdminBundle\Attribute\AdminDashboard;
use App\Entity\Auteur;
use App\Entity\Categorie;
use App\Entity\Commande;
use App\Entity\CommandeItem;
use App\Entity\Editeur;
use App\Entity\Emprunt;
use App\Entity\Livre;
use App\Entity\User;
use App\Entity\Promotion;

#[AdminDashboard(routePath: '/admin/bookshop', routeName: 'admin')]
class DashboardController extends AbstractDashboardController
{
    private LivreRepository $livres;
    private CommandeRepository $commandes;
    private UserRepository $users;
    private EmpruntRepository $emprunts;
    private ManagerRegistry $doctrine;

    // On ajoute juste ManagerRegistry pour les requêtes personnalisées
    public function __construct(
        LivreRepository $livres,
        CommandeRepository $commandes,
        UserRepository $users,
        EmpruntRepository $emprunts,
        ManagerRegistry $doctrine
    ) {
        $this->livres = $livres;
        $this->commandes = $commandes;
        $this->users = $users;
        $this->emprunts = $emprunts;
        $this->doctrine = $doctrine;
    }

    public function index(): Response
    {
        $em = $this->doctrine->getManager();
        $now = new \DateTime();
    $firstDayMonth = (clone $now)->modify('first day of this month 00:00:00');

        // Tes stats classiques (inchangées)
        $nbLivres = $this->livres->count([]);
        $nbCommandes = $this->commandes->count([]);
        $nbUsers = $this->users->count([]);
        $nbEmprunts = $this->emprunts->count([]);

        // NOUVEAU : Chiffre d'affaires total (commandes payées, expédiées ou livrées)
        $caTotal = $em->createQueryBuilder()
            ->select('COALESCE(SUM(c.total), 0)')
            ->from(Commande::class, 'c')
            ->where('c.statut IN (:statuts)')
            ->setParameter('statuts', ['payee', 'expediee', 'livree'])
            ->getQuery()
            ->getSingleScalarResult();

        // NOUVEAU : CA du mois en cours
        $premierJourMois = new \DateTime('first day of this month 00:00:00');
        $caMois = $em->createQueryBuilder()
            ->select('COALESCE(SUM(c.total), 0)')
            ->from(Commande::class, 'c')
            ->where('c.statut IN (:statuts)')
            ->andWhere('c.dateCommande >= :debut')
            ->setParameter('statuts', ['payee', 'expediee', 'livree'])
            ->setParameter('debut', $premierJourMois)
            ->getQuery()
            ->getSingleScalarResult();
          
            // === NOUVELLES STATS DEMANDÉES ===

    // 8. Commandes en attente de paiement (COD)
    $commandesEnAttente = $this->commandes->count(['statut' => 'en_attente']);

    // 9. Commandes payées mais non expédiées
    $commandesPayeesNonExpediees = $this->commandes->count(['statut' => 'payee']);

    // 10. Top 10 livres les plus vendus
    $top10Livres = $em->createQueryBuilder()
        ->select('l.titre, l.isbn, SUM(ci.quantite) as ventes')
        ->from(CommandeItem::class, 'ci')
        ->join('ci.commande', 'c')
        ->join('ci.livre', 'l')
        ->where('c.statut IN (:ok)')
        ->setParameter('ok', ['payee', 'expediee', 'livree'])
        ->groupBy('l.id')
        ->orderBy('ventes', 'DESC')
        ->setMaxResults(10)
        ->getQuery()
        ->getResult();

    // 11. Livres en rupture de stock
$livresRupture = $this->livres->createQueryBuilder('l')
    ->where('l.quantiteEnStock = 0')
    ->orderBy('l.titre', 'ASC')
    ->getQuery()
    ->getResult();

// 12. Livres avec stock faible (< 5 exemplaires)
$livresStockFaible = $this->livres->createQueryBuilder('l')
    ->where('l.quantiteEnStock > 0')
    ->andWhere('l.quantiteEnStock < 5')
    ->orderBy('l.quantiteEnStock', 'ASC')
    ->getQuery()
    ->getResult();

    // 13. Commandes annulées ou remboursées
    $commandesAnnulees = $this->commandes->count(['statut' => 'annulee']);

    // 15. Nouveaux clients ce mois-ci
    $nouveauxClientsMois = $em->getRepository(User::class)->createQueryBuilder('u')
        ->select('COUNT(u.id)')
        ->where('u.createdAt >= :debut')
        ->setParameter('debut', $firstDayMonth)
        ->getQuery()
        ->getSingleScalarResult();

    return $this->render('admin/dashboard.html.twig', [
        // tes anciennes vars
        'nbLivres'       => $nbLivres,
        'nbCommandes'    => $nbCommandes,
        'nbUsers'        => $nbUsers,
        'nbEmprunts'     => $nbEmprunts,
        'caTotal'        => $caTotal ?? 0,
        'caMois'         => $caMois ?? 0,
        'moisActuel'     => $now->format('F Y'),

        // nouvelles stats
        'commandesEnAttente'          => $commandesEnAttente,
        'commandesPayeesNonExpediees' => $commandesPayeesNonExpediees,
        'top10Livres'                 => $top10Livres,
        'livresRupture'               => $livresRupture,
        'livresStockFaible'           => $livresStockFaible,
        'commandesAnnulees'           => $commandesAnnulees,
        'nouveauxClientsMois'         => $nouveauxClientsMois,
    ]);
    }

    public function configureDashboard(): Dashboard
    {
        return Dashboard::new()
            ->setTitle('Bookshop.tn - Administration')
            ->setFaviconPath('favicon.ico');
    }

    public function configureMenuItems(): iterable
    {
        yield MenuItem::linkToDashboard('Tableau de bord', 'fa fa-home');

        yield MenuItem::section('Catalogue');
        yield MenuItem::linkToCrud('Livres', 'fa fa-book', Livre::class);
        yield MenuItem::linkToCrud('Auteurs', 'fa fa-feather', Auteur::class);
        yield MenuItem::linkToCrud('Catégories', 'fa fa-tags', Categorie::class);
        yield MenuItem::linkToCrud('Éditeurs', 'fa fa-building', Editeur::class);
        yield MenuItem::linkToCrud('Promotions', 'fa fa-percent', Promotion::class);

        yield MenuItem::section('Ventes');
        yield MenuItem::linkToCrud('Commandes', 'fa fa-shopping-cart', Commande::class);
        yield MenuItem::linkToCrud('Lignes de commande', 'fa fa-list-alt', CommandeItem::class);

        yield MenuItem::section('Emprunts');
        yield MenuItem::linkToCrud('Emprunts', 'fa fa-hand-holding-heart', Emprunt::class);

        yield MenuItem::section('Utilisateurs');
        yield MenuItem::linkToCrud('Utilisateurs', 'fa fa-users', User::class);

        yield MenuItem::linkToRoute('Retour au site', 'fa fa-arrow-left', 'client_livre_index')
            ->setLinkTarget('_blank');
    }
}