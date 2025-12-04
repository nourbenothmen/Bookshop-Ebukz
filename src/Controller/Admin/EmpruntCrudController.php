<?php
// src/Controller/Admin/EmpruntCrudController.php

namespace App\Controller\Admin;

use App\Entity\Emprunt;
use Doctrine\ORM\EntityManagerInterface;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\TemplateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Filter\ChoiceFilter;
use EasyCorp\Bundle\EasyAdminBundle\Filter\EntityFilter;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Email;
class EmpruntCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Emprunt::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPageTitle('index', 'Gestion des emprunts')
            ->setDefaultSort(['dateEmprunt' => 'DESC'])
            ->showEntityActionsInlined();
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add(ChoiceFilter::new('statut')->setChoices([
                'En attente C.I.N.'      => 'attente_validation_cin',
                'En attente paiement'    => 'attente_paiement',
                'En cours'               => 'en_cours',
                'Retourné'               => 'retourne',
                'Refusé'                 => 'refuse',
            ]))
            ->add(EntityFilter::new('livre'))
            ->add(EntityFilter::new('emprunteur'));
    }

    public function configureActions(Actions $actions): Actions
    {
        // Bouton Valider C.I.N. + Paiement
        $validerCinEtPayer = Action::new('validerEtPayer', 'Valider + Paiement', 'fa fa-euro-sign')
            ->linkToRoute('admin_emprunt_valider_cin', fn (Emprunt $e) => ['id' => $e->getId()])
            ->addCssClass('btn btn-success btn-sm')
            ->displayIf(fn (Emprunt $e) => $e->getStatut() === 'attente_validation_cin');

        // Bouton Marquer comme retourné
        $marquerRetour = Action::new('marquerCommeRetourne', 'Marquer retourné', 'fa fa-check-circle')
            ->linkToRoute('admin_emprunt_marquer_retourne', fn (Emprunt $e) => ['id' => $e->getId()])
            ->addCssClass('btn btn-primary btn-sm')
            ->displayIf(fn (Emprunt $e) => $e->getStatut() === 'en_cours');
        
            // Bouton Refuser la C.I.N.
$refuserCin = Action::new('refuserCin', 'Refuser C.I.N.', 'fa fa-times-circle')
    ->linkToRoute('admin_emprunt_refuser_cin', fn (Emprunt $e) => ['id' => $e->getId()])
    ->addCssClass('btn btn-danger btn-sm')
    // Rouge = visible et clair
    ->displayIf(fn (Emprunt $e) => $e->getStatut() === 'attente_validation_cin')
    ->setHtmlAttributes(['title' => 'Refuser la demande car C.I.N. non conforme']);

        return $actions
            ->add(Crud::PAGE_INDEX, $validerCinEtPayer)
            ->add(Crud::PAGE_INDEX, $marquerRetour)
            ->add(Crud::PAGE_DETAIL, $validerCinEtPayer)
            ->add(Crud::PAGE_DETAIL, $marquerRetour)
            ->add(Crud::PAGE_INDEX, $refuserCin)
            ->add(Crud::PAGE_DETAIL, $refuserCin) 
            ->disable(Action::NEW, Action::EDIT, Action::DELETE);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->onlyOnIndex(),
            AssociationField::new('emprunteur')
                ->formatValue(fn ($v) => $v?->getNomComplet() . ' (' . $v?->getEmail() . ')'),
            AssociationField::new('livre')->formatValue(fn ($v) => $v?->getTitre()),

                  // Si tu veux uploader une image :
            ImageField::new('cinRecto')
                ->setBasePath('uploads/cin')
                ->setUploadDir('public/uploads/cin')
                ->setRequired(false),

                 ImageField::new('cinVerso')
                ->setBasePath('uploads/cin')
                ->setUploadDir('public/uploads/cin')
                ->setRequired(false),
            

            MoneyField::new('fraisEmprunt')->setCurrency('TND'),
            MoneyField::new('caution')->setCurrency('TND'),

            DateTimeField::new('dateEmprunt'),
            DateTimeField::new('dateRetourPrevue', 'Retour prévu'),
            DateTimeField::new('dateRetourReel', 'Retour réel')->hideOnIndex(),
            
            TextField::new('retard', 'Statut / Retard')
    ->formatValue(function ($value, $emprunt) {
        return $this->renderView('admin/fields/retard.html.twig', [
            'emprunt' => $emprunt,
        ]);
    })
    ->renderAsHtml()
    ->onlyOnIndex(),

            ChoiceField::new('statut')
                ->setChoices([
                    'En attente C.I.N.'      => 'attente_validation_cin',
                    'En attente paiement'    => 'attente_paiement',
                    'En cours'               => 'en_cours',
                    'Retourné'               => 'retourne',
                    'Refusé'                 => 'refuse',
                ])
                ->renderAsBadges([
                    'attente_validation_cin' => 'warning',
                    'attente_paiement'       => 'info',
                    'en_cours'               => 'success',
                    'retourne'               => 'secondary',
                    'refuse'                 => 'danger',
                ]),

            BooleanField::new('cinValidated', 'C.I.N. validée ?'),
        ];
    }

    // ACTION : MARQUER COMME RETOURNÉ
    #[Route('/admin/emprunt/marque-retourne/{id}', name: 'admin_emprunt_marquer_retourne', methods: ['GET'])]
    public function marquerCommeRetourne(int $id, EntityManagerInterface $em): Response
    {
        $this->denyAccessUnlessGranted('ROLE_ADMIN');

        $emprunt = $em->getRepository(Emprunt::class)->find($id);

        if (!$emprunt) {
            $this->addFlash('error', 'Emprunt non trouvé.');
            return $this->redirectToRoute('admin');
        }

        if ($emprunt->getStatut() !== 'en_cours') {
            $this->addFlash('warning', 'Cet emprunt n\'est pas en cours.');
            return $this->redirectToRoute('admin');
        }

        $emprunt->setStatut('retourne');
        $emprunt->setDateRetourReel(new \DateTimeImmutable());

        // Remettre le livre en stock
        $livre = $emprunt->getLivre();
        $livre->setQuantiteEnStock($livre->getQuantiteEnStock() + 1);

        $em->flush();

        $this->addFlash('success', 'Livre marqué comme retourné avec succès !');

        return $this->redirectToRoute('admin');
    }


    #[Route('/admin/emprunt/refuser-cin/{id}', name: 'admin_emprunt_refuser_cin', methods: ['GET'])]
public function refuserCin(int $id, EntityManagerInterface $em, MailerInterface $mailer): Response
{
    $this->denyAccessUnlessGranted('ROLE_ADMIN');

    $emprunt = $em->getRepository(Emprunt::class)->find($id);

    if (!$emprunt) {
        $this->addFlash('error', 'Emprunt non trouvé.');
        return $this->redirectToRoute('admin');
    }

    if ($emprunt->getStatut() !== 'attente_validation_cin') {
        $this->addFlash('warning', 'Cet emprunt ne peut pas être refusé (statut incorrect).');
        return $this->redirectToRoute('admin');
    }

    // Changer le statut
    $emprunt->setStatut('refuse');

    // Remettre le livre en stock (au cas où il aurait été réservé)
    $livre = $emprunt->getLivre();
    $livre->setQuantiteEnStock($livre->getQuantiteEnStock() + 1);

    $em->flush();

    // ENVOI DE L’EMAIL
    $email = (new Email())
        ->from('noorbenothmen78@gmail.com')
        ->to($emprunt->getEmprunteur()->getEmail())
        ->subject('Votre demande d’emprunt a été refusée')
        ->html($this->renderView('emails/emprunt_refuse_cin.html.twig', [
            'emprunt' => $emprunt,
            'emprunteur' => $emprunt->getEmprunteur(),
            'livre' => $livre,
        ]));

    $mailer->send($email);

    $this->addFlash('danger', 'Emprunt refusé et email envoyé à l’emprunteur.');

    return $this->redirectToRoute('admin');
}
}