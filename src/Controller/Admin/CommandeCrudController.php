<?php

namespace App\Controller\Admin;

use App\Entity\Commande;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Context\AdminContext; // ← C’EST ÇA QU’IL MANQUAIT !
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\EmailField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TelephoneField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextareaField;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Response;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;
use Symfony\Component\Routing\Annotation\Route;

#[Route("/admin/commande")]
class CommandeCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Commande::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPageTitle('index', 'Gestion des commandes')
            ->setPageTitle('detail', fn (Commande $c) => 'Commande #' . $c->getId() . ' – ' . $c->getPrenom() . ' ' . $c->getNom())
            ->setDefaultSort(['dateCommande' => 'DESC'])
            ->setPaginatorPageSize(20)
            ->showEntityActionsInlined();
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('dateCommande')
            ->add('statut')
            ->add('modePaiement');
    }

public function configureActions(Actions $actions): Actions
{
    $marquerPayee = Action::new('marquerPayee', 'Marquer payée', 'fa fa-check')
        ->linkToRoute('admin_commande_marquer_payee', fn(Commande $c) => ['id' => $c->getId()])
        ->addCssClass('btn btn-success btn-sm')
        ->displayIf(fn (Commande $c) => $c->getStatut() !== 'payee');

    $marquerExpediee = Action::new('marquerExpediee', 'Marquer expédiée', 'fa fa-truck')
        ->linkToRoute('admin_commande_marquer_expediee', fn(Commande $c) => ['id' => $c->getId()])
        ->addCssClass('btn btn-info btn-sm')
        ->displayIf(fn (Commande $c) => $c->getStatut() === 'payee');

    $annuler = Action::new('annuler', 'Annuler', 'fa fa-ban')
        ->linkToRoute('admin_commande_annuler', fn(Commande $c) => ['id' => $c->getId()])
        ->addCssClass('btn btn-danger btn-sm')
        ->displayIf(fn (Commande $c) => !in_array($c->getStatut(), ['expediee', 'livree', 'annulee']));

    return $actions
        ->add(Crud::PAGE_INDEX, $marquerPayee)
        ->add(Crud::PAGE_INDEX, $marquerExpediee)
        ->add(Crud::PAGE_INDEX, $annuler)
        ->disable(Action::NEW, Action::DELETE);
}


    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->onlyOnIndex(),
            DateTimeField::new('dateCommande')->setFormat('dd/MM/yyyy HH:mm'),

            TextField::new('prenom'),
            TextField::new('nom'),
            EmailField::new('email'),
            TelephoneField::new('telephone'),

            MoneyField::new('total')
                ->setCurrency('TND')
                ->setStoredAsCents(false)
                ->setTextAlign('right'),

            ChoiceField::new('statut')
                ->setChoices([
                    'En attente' => 'en_attente',
                    'Payée' => 'payee',
                    'Expédiée' => 'expediee',
                    'Livrée' => 'livree',
                    'Annulée' => 'annulee',
                ])
                ->renderAsBadges([
                    'en_attente' => 'warning',
                    'payee' => 'success',
                    'expediee' => 'info',
                    'livree' => 'primary',
                    'annulee' => 'danger',
                ]),

            ChoiceField::new('modePaiement')
                ->setChoices([
                    'Paiement à la livraison' => 'cod',
                    'Stripe' => 'stripe',
                    'Virement bancaire' => 'virement',
                ])
                ->renderAsBadges([
                    'cod' => 'secondary',
                    'stripe' => 'success',
                    'virement' => 'info',
                ]),

            TextareaField::new('adresseFacturation')->hideOnIndex(),
            TextareaField::new('adresseLivraison')->hideOnIndex(),
            TextareaField::new('notes')->hideOnIndex(),

            AssociationField::new('utilisateur')
                ->formatValue(fn ($v) => $v?->getEmail())
                ->hideOnIndex(),

            AssociationField::new('items')
                ->setLabel('Articles commandés')
                ->onlyOnDetail()
                ->setTemplatePath('admin/fields/commande_items.html.twig'),
        ];
    }

#[Route('/payee/{id}', name: 'admin_commande_marquer_payee', methods: ['GET'])]
public function marquerPayee(int $id, EntityManagerInterface $em): Response
{
    $commande = $em->getRepository(Commande::class)->find($id);

    if (!$commande) {
        $this->addFlash('error', 'Commande non trouvée.');
        return $this->redirectToRoute('admin');
    }

    // SEULEMENT les commandes en "Paiement à la livraison" peuvent être marquées payées manuellement
    if ($commande->getModePaiement() !== 'cod') {
        $this->addFlash('warning', 'Seules les commandes en paiement à la livraison peuvent être marquées payées manuellement.');
        return $this->redirectToRoute('admin');
    }

    // Optionnel : empêcher de marquer deux fois
    if ($commande->getStatut() === 'payee') {
        $this->addFlash('info', 'Cette commande est déjà marquée comme payée.');
       return $this->redirectToRoute('admin');
    }

    $commande->setStatut('payee');
    $em->flush();

    $this->addFlash('success', 'Commande #' . $commande->getId() . ' marquée comme payée (COD)');
    
    return $this->redirectToRoute('admin');
}

    #[Route('/expediee/{id}', name: 'admin_commande_marquer_expediee', methods: ['GET'])]
    public function marquerExpediee(int $id, EntityManagerInterface $em): Response
    {
        $commande = $em->getRepository(Commande::class)->find($id);
        if (!$commande) {
            $this->addFlash('error', 'Commande non trouvée.');
            return $this->redirectToRoute('admin');
        }
        /*
        if ($commande->getStatut() !== 'payee') {
            $this->addFlash('warning', 'Seules les commandes payées peuvent être expédiées.');
            return $this->redirectToRoute('admin');
        }*/

        $commande->setStatut('expediee');
        $em->flush();

        $this->addFlash('success', 'Commande #' . $commande->getId() . ' marquée comme expédiée');
        return $this->redirectToRoute('admin');
    }

    #[Route('/annuler/{id}', name: 'admin_commande_annuler', methods: ['GET'])]
    public function annulerCommande(int $id, EntityManagerInterface $em): Response
    {
        $commande = $em->getRepository(Commande::class)->find($id);
        if (!$commande) {
            $this->addFlash('error', 'Commande non trouvée.');
            return $this->redirectToRoute('admin');
        }

        if (in_array($commande->getStatut(), ['expediee', 'livree', 'annulee'])) {
            $this->addFlash('warning', 'Cette commande ne peut plus être annulée.');
            return $this->redirectToRoute('admin');
        }

        $commande->setStatut('annulee');
        $em->flush();

        $this->addFlash('danger', 'Commande #' . $commande->getId() . ' annulée');
        return $this->redirectToRoute('admin');
    }

}