<?php

namespace App\Controller\Admin;

use App\Entity\Promotion;
use EasyCorp\Bundle\EasyAdminBundle\Config\Actions;
use EasyCorp\Bundle\EasyAdminBundle\Config\Action;
use EasyCorp\Bundle\EasyAdminBundle\Config\Crud;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateTimeField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ChoiceField;

class PromotionCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Promotion::class;
    }

    public function configureCrud(Crud $crud): Crud
    {
        return $crud
            ->setPageTitle('index', 'Gestion des Promotions')
            ->setPageTitle('new', 'Créer une Promotion')
            ->setPageTitle('edit', 'Modifier la Promotion')
            ->setEntityLabelInSingular('Promotion')
            ->setEntityLabelInPlural('Promotions')
            ->setDefaultSort(['dateDebut' => 'DESC'])
            ->setSearchFields(['nom']);
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),

            TextField::new('nom')
                ->setLabel('Nom de la promotion')
                ->setHelp('Ex: Soldes Noël 2025, Black Friday, Promo Été'),

            NumberField::new('pourcentage')
                ->setLabel('Réduction (%)')
                ->setHelp('Ex: 30 pour -30%')
                ->setNumDecimals(2)
                ->setFormTypeOptions(['scale' => 2]),

            DateTimeField::new('dateDebut')
                ->setLabel('Date de début'),

            DateTimeField::new('dateFin')
                ->setLabel('Date de fin'),

            BooleanField::new('active')
                ->setLabel('Active')
                ->renderAsSwitch(true),

            // UN SEUL des 3 champs ci-dessous doit être rempli
            AssociationField::new('livre')
                ->setLabel('Livre spécifique (optionnel)')
                ->setRequired(false),

            AssociationField::new('categorie')
                ->setLabel('Toute la catégorie (optionnel)')
                ->setRequired(false),

            AssociationField::new('editeur')
                ->setLabel('Tout l\'éditeur (optionnel)')
                ->setRequired(false),
        ];
    }

    public function configureActions(Actions $actions): Actions
    {
        return $actions
            ->add(Crud::PAGE_INDEX, Action::DETAIL)
            ->update(Crud::PAGE_INDEX, Action::NEW, function (Action $action) {
                return $action->setIcon('fa fa-plus')->setLabel('Nouvelle Promo');
            })
            ->update(Crud::PAGE_INDEX, Action::EDIT, function (Action $action) {
                return $action->setIcon('fa fa-edit');
            })
            ->update(Crud::PAGE_INDEX, Action::DELETE, function (Action $action) {
                return $action->setIcon('fa fa-trash');
            });
    }
}