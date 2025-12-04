<?php

namespace App\Controller\Admin;

use App\Entity\Categorie;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;

class CategorieCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Categorie::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [

            IdField::new('id')->hideOnForm(),

            TextField::new('designation', 'Désignation')
                ->setRequired(true),

            // Affichage des livres de la catégorie → lecture seule
            CollectionField::new('livres', 'Livres associés')
                ->onlyOnDetail()
                ->setTemplatePath('admin/fields/livres_list.html.twig'),
        ];
    }
}
