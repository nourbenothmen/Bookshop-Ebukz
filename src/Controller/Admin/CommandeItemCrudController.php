<?php
// src/Controller/Admin/CommandeItemCrudController.php

namespace App\Controller\Admin;

use App\Entity\CommandeItem;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Config\Filters;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\MoneyField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Router\AdminUrlGenerator;

class CommandeItemCrudController extends AbstractCrudController
{
    private AdminUrlGenerator $adminUrlGenerator;

    public function __construct(AdminUrlGenerator $adminUrlGenerator)
    {
        $this->adminUrlGenerator = $adminUrlGenerator;
    }

    public static function getEntityFqcn(): string
    {
        return CommandeItem::class;
    }

    public function configureCrud(Crud $crud): Crud
    { 
        return $crud
            ->setEntityLabelInSingular('Ligne de commande')
            ->setEntityLabelInPlural('Lignes de commande')
            ->setPageTitle('index', 'Détail des articles commandés')
            ->setDefaultSort(['commande.dateCommande' => 'DESC', 'id' => 'DESC'])
            ->setPaginatorPageSize(30);
    }

    public function configureFilters(Filters $filters): Filters
    {
        return $filters
            ->add('commande')
            ->add('livre');
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->onlyOnIndex(),

            // Commande liée (cliquable)
            AssociationField::new('commande')
                ->setLabel('Commande')
                ->formatValue(function ($value, $entity) {
                    $commande = $entity->getCommande();
                    if (!$commande) {
                        return '<em class="text-muted">Commande supprimée</em>';
                    }
                    $url = $this->adminUrlGenerator
                        ->setController(CommandeCrudController::class)
                        ->setAction('detail')
                        ->setEntityId($commande->getId())
                        ->generateUrl();

                    $client = trim($commande->getPrenom() . ' ' . $commande->getNom());
                    $date = $commande->getDateCommande()?->format('d/m/Y H:i');

                    return sprintf(
                        '<a href="%s" class="fw-bold text-primary">#%d</a> <small class="text-muted">– %s <em>(%s)</em></small>',
                        $url,
                        $commande->getId(),
                        $client ?: 'Anonyme',
                        $date
                    );
                })
                ->onlyOnIndex(),

            // Livre commandé
            AssociationField::new('livre')
                ->setLabel('Livre')
                ->formatValue(fn ($v, $entity) => $entity->getLivre()?->getTitre() . ' (' . $entity->getLivre()?->getIsbn() . ')'),

            // Quantité
            IntegerField::new('quantite')
                ->setLabel('Quantité')
                ->setTextAlign('center'),

            // Prix unitaire
            MoneyField::new('prixUnitaire')
                ->setCurrency('TND')
                ->setStoredAsCents(false)
                ->setLabel('Prix unitaire')
                ->setTextAlign('right'),

            // Total ligne (champ virtuel)
  TextField::new('totalLigne', 'Total ligne')
    ->setVirtual(true)
    ->onlyOnIndex(),

        ];
    }
}
