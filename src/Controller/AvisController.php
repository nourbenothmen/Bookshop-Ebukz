<?php

namespace App\Controller;

use App\Entity\Avis;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/livre')]
#[IsGranted('ROLE_USER')]
class AvisController extends AbstractController
{
    #[Route('/{id}/avis', name: 'client_livre_avis', methods: ['GET', 'POST'])]
    public function ajouterAvis(Request $request, $id, EntityManagerInterface $em): Response
    {
        $livre = $em->getRepository(\App\Entity\Livre::class)->find($id);

        if (!$livre) {
            throw $this->createNotFoundException('Livre non trouvé');
        }

        if ($request->isMethod('POST')) {
            $note = (int) $request->request->get('note');
            $commentaire = $request->request->get('commentaire', '');

            // Empêcher double avis
            $dejaNote = $em->getRepository(Avis::class)->findOneBy([
                'livre' => $livre,
                'utilisateur' => $this->getUser()
            ]);

            if ($dejaNote) {
                $this->addFlash('warning', 'Vous avez déjà noté ce livre !');
            } elseif ($note < 1 || $note > 5) {
                $this->addFlash('danger', 'Note invalide !');
            } else {
                $avis = new Avis();
                $avis->setLivre($livre);
                $avis->setUtilisateur($this->getUser());
                $avis->setNote($note);
                $avis->setCommentaire($commentaire);

                $em->persist($avis);
                $em->flush();

                $this->addFlash('success', 'Merci pour votre avis !');
            }

            return $this->redirectToRoute('client_livre_show', ['id' => $livre->getId()]);
        }

        return $this->render('avis/avis.html.twig', [
            'livre' => $livre
        ]);
    }
}