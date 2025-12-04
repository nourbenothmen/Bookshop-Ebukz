<?php

namespace App\Controller\Client;

use App\Entity\Editeur;
use App\Form\EditeurType;
use App\Repository\EditeurRepository;
use App\Repository\CategorieRepository;
use Knp\Component\Pager\PaginatorInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('client/editeur')]
final class EditeurController extends AbstractController
{
   #[Route(name: 'client_editeur_index', methods: ['GET'])]
public function index(
    Request $request,
    EditeurRepository $editeurRepository,
    CategorieRepository $categorieRepository,
    PaginatorInterface $paginator
): Response
{
    // Récupérer tous les éditeurs
    $query = $editeurRepository->createQueryBuilder('e')
        ->getQuery();

    // Pagination : 6 éditeurs par page
    $editeurs = $paginator->paginate(
        $query,                              // Query ou array
        $request->query->getInt('page', 1),  // Numéro de la page
        6                                     // Éléments par page
    );

    return $this->render('client/editeur/index.html.twig', [
        'editeurs' => $editeurs,
        'categorie' => $categorieRepository->findAll(),
    ]);
}


    #[Route('/new', name: 'client_editeur_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $editeur = new Editeur();
        $form = $this->createForm(EditeurType::class, $editeur);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($editeur);
            $entityManager->flush();

            return $this->redirectToRoute('client_editeur_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('client/editeur/new.html.twig', [
            'editeur' => $editeur,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'client_editeur_show', methods: ['GET'])]
    public function show(Editeur $editeur): Response
    {
        return $this->render('client/editeur/show.html.twig', [
            'editeur' => $editeur,
        ]);
    }

    #[Route('/{id}/edit', name: 'client_editeur_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Editeur $editeur, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(EditeurType::class, $editeur);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('client_editeur_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('client/editeur/edit.html.twig', [
            'editeur' => $editeur,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'client_editeur_delete', methods: ['POST'])]
    public function delete(Request $request, Editeur $editeur, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete'.$editeur->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($editeur);
            $entityManager->flush();
        }

        return $this->redirectToRoute('client_editeur_index', [], Response::HTTP_SEE_OTHER);
    }
}
