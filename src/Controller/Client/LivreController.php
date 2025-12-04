<?php

namespace App\Controller\Client;

use App\Entity\Livre;
use App\Form\LivreType;
use App\Repository\LivreRepository;
use App\Repository\CategorieRepository;
use App\Repository\AuteurRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\String\Slugger\SluggerInterface;
use Knp\Component\Pager\PaginatorInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;


#[Route('/client/livre')]
final class LivreController extends AbstractController
{
  #[Route('/', name: 'client_livre_index')]
    public function index(
        Request $request,
        LivreRepository $livreRepository,
        CategorieRepository $categorieRepository,
        AuteurRepository $auteurRepository,
        PaginatorInterface $paginator,
        EntityManagerInterface $em // ← AJOUTE ÇA
    ): Response {
        // Récupérer les filtres depuis la requête GET
        $minPrice = $request->query->get('min_price');
        $maxPrice = $request->query->get('max_price');
       $categorieIds = $request->query->all('categories'); // renvoie un tableau même vide
       $auteurIds = $request->query->all('auteurs');       // renvoie un tableau même vide


        // Construction de la requête
        $qb = $livreRepository->createQueryBuilder('l');

        if ($minPrice !== null) {
            $qb->andWhere('l.pu >= :minPrice')
               ->setParameter('minPrice', $minPrice);
        }

        if ($maxPrice !== null) {
            $qb->andWhere('l.pu <= :maxPrice')
               ->setParameter('maxPrice', $maxPrice);
        }

        if (!empty($categorieIds)) {
            $qb->join('l.categorie', 'c')
               ->andWhere('c.id IN (:categorieIds)')
               ->setParameter('categorieIds', $categorieIds);
        }

        if (!empty($auteurIds)) {
            $qb->join('l.auteurs', 'a')
               ->andWhere('a.id IN (:auteurIds)')
               ->setParameter('auteurIds', $auteurIds);
        }

        $query = $qb->getQuery();

        // Pagination
        $livres = $paginator->paginate(
            $query,
            $request->query->getInt('page', 1),
            8
        );
        // NOUVELLE PARTIE : on vérifie pour chaque livre si l'utilisateur connecté a un emprunt en cours dessus
    $user = $this->getUser();
  // Dans LivreController.php → dans la boucle foreach
foreach ($livres as $livre) {
    $hasEmpruntEnCours = false;
    if ($user) {
        $hasEmpruntEnCours = $em->getRepository(\App\Entity\Emprunt::class)
            ->existsAnyEmpruntEnCoursForUser($user);
    }
    $livre->setHasEmpruntEnCours($hasEmpruntEnCours); // ✅ Utilisation du setter
}


        return $this->render('client/shop.html.twig', [
            'livres' => $livres,
            'categorie' => $categorieRepository->findAll(),
            'auteurs' => $auteurRepository->findAll(),
            'min_price' => $minPrice,
            'max_price' => $maxPrice,
            'selected_categories' => $categorieIds,
            'selected_auteurs' => $auteurIds
        ]);
    }


    #[Route('/search', name: 'client_livre_search')]
public function search(
    Request $request, 
    LivreRepository $livreRepository, 
    CategorieRepository $categorieRepository, 
    PaginatorInterface $paginator
): Response {
    $term = $request->query->get('search');

    $qb = $livreRepository->createQueryBuilder('l')
        ->leftJoin('l.categorie', 'c')
        ->leftJoin('l.auteurs', 'a')
        ->addSelect('c')
        ->addSelect('a');

    if ($term) {
        $words = explode(' ', $term); // séparer les mots de la recherche

        $orX = $qb->expr()->orX();

        // Recherche dans le titre et la catégorie
        $orX->add($qb->expr()->like('l.titre', ':term'));
        $orX->add($qb->expr()->like('c.designation', ':term'));

        // Recherche pour chaque mot dans nom ou prénom
        foreach ($words as $i => $word) {
            $orX->add($qb->expr()->like("a.nom", ":word$i"));
            $orX->add($qb->expr()->like("a.prenom", ":word$i"));
            $qb->setParameter("word$i", "%$word%");
        }

        $qb->andWhere($orX)
           ->setParameter('term', "%$term%");
    }

    $query = $qb->getQuery();

    $livres = $paginator->paginate(
        $query,
        $request->query->getInt('page', 1),
        8
    );

    return $this->render('client/shop.html.twig', [
    'livres' => $livres,
    'categorie' => $categorieRepository->findAll(),
    'auteurs' => [], // ou récupérer tous les auteurs si besoin
    'selected_categories' => [],
    'selected_auteurs' => [],
    'min_price' => null,
    'max_price' => null
    ]);
}
/*
#[Route('/livre/{id}/avis', name: 'client_livre_avis', methods: ['GET'])]
public function avis(int $id, LivreRepository $livreRepository): Response
{
    $livre = $livreRepository->find($id);

    if (!$livre) {
        throw $this->createNotFoundException("Livre introuvable.");
    }

    return $this->render('avis/avis.html.twig', [
        'livre' => $livre
    ]);
}*/




#[Route('/client/livre/categorie/{id}', name: 'client_livre_par_categorie')]
public function livresParCategorie(
    $id,
    Request $request,
    LivreRepository $livreRepository,
    CategorieRepository $categorieRepository,
    PaginatorInterface $paginator
): Response {
    
    $categorie = $categorieRepository->find($id);

    $query = $livreRepository->createQueryBuilder('l')
        ->where('l.categorie = :cat')
        ->setParameter('cat', $categorie)
        ->getQuery();

    $livres = $paginator->paginate(
        $query,
        $request->query->getInt('page', 1),
        8
    );

    return $this->render('client/shop.html.twig', [
    'livres' => $livres,
    'categorie' => $categorieRepository->findAll(),
    'auteurs' => [], // ou $auteurRepository->findAll()
    'selected_categories' => [$id], // puisque l'utilisateur filtre par une catégorie
    'selected_auteurs' => [],
    'min_price' => null,
    'max_price' => null
    ]);
}



    #[Route('/new', name: 'client_livre_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $livre = new Livre();
        $form = $this->createForm(LivreType::class, $livre);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            /** @var UploadedFile $imageFile */
            $imageFile = $form->get('imageFile')->getData();
            
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();
                
                try {
                    $imageFile->move(
                        $this->getParameter('images_directory'),
                        $newFilename
                    );
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload de l\'image.');
                }
                
                $livre->setImage($newFilename);
            }
            
            $entityManager->persist($livre);
            $entityManager->flush();

            $this->addFlash('success', 'Livre créé avec succès.');
            return $this->redirectToRoute('client_livre_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('client/livre/new.html.twig', [
            'livre' => $livre,
            'form' => $form,
        ]);
    }

    #[Route('/{id<\d+>}', name: 'client_livre_show', methods: ['GET'])]
    public function show(Livre $livre): Response
    {
        return $this->render('client/livre/show.html.twig', [
            'livre' => $livre,
        ]);
    }

    #[Route('/{id<\d+>}/edit', name: 'client_livre_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Livre $livre, EntityManagerInterface $entityManager, SluggerInterface $slugger): Response
    {
        $form = $this->createForm(LivreType::class, $livre);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $imageFile = $form->get('imageFile')->getData();
            
            if ($imageFile) {
                $originalFilename = pathinfo($imageFile->getClientOriginalName(), PATHINFO_FILENAME);
                $safeFilename = $slugger->slug($originalFilename);
                $newFilename = $safeFilename.'-'.uniqid().'.'.$imageFile->guessExtension();
                
                try {
                    $imageFile->move(
                        $this->getParameter('images_directory'),
                        $newFilename
                    );
                    
                    $oldImage = $livre->getImage();
                    if ($oldImage) {
                        $oldImagePath = $this->getParameter('images_directory').'/'.$oldImage;
                        if (file_exists($oldImagePath)) unlink($oldImagePath);
                    }
                    
                    $livre->setImage($newFilename);
                    
                } catch (FileException $e) {
                    $this->addFlash('error', 'Erreur lors de l\'upload de l\'image.');
                }
            }
            
            $entityManager->flush();

            $this->addFlash('success', 'Livre modifié avec succès.');
            return $this->redirectToRoute('client_livre_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('client/livre/edit.html.twig', [
            'livre' => $livre,
            'form' => $form,
        ]);
    }

    #[Route('/{id<\d+>}/delete', name: 'client_livre_delete', methods: ['POST'])]
    public function delete(Request $request, Livre $livre, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$livre->getId(), $request->request->get('_token'))) {
            $image = $livre->getImage();
            if ($image) {
                $imagePath = $this->getParameter('images_directory').'/'.$image;
                if (file_exists($imagePath)) unlink($imagePath);
            }
            
            $entityManager->remove($livre);
            $entityManager->flush();
            
            $this->addFlash('success', 'Livre supprimé avec succès.');
        }

        return $this->redirectToRoute('client_livre_index', [], Response::HTTP_SEE_OTHER);
    }
}
