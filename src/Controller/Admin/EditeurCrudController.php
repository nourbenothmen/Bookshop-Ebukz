<?php

namespace App\Controller\Admin;

use App\Entity\Editeur;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\CollectionField;

class EditeurCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Editeur::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),

            TextField::new('nom', 'Nom de l’éditeur'),

            TextField::new('pays', 'Pays'),

            TextField::new('adresse', 'Adresse'),

            IntegerField::new('telephone', 'Téléphone'),

            // Relation OneToMany → affichage en lecture seule
            CollectionField::new('livres', 'Livres publiés')
                ->onlyOnDetail() // Affiché uniquement dans la page "show"
                ->setTemplatePath('admin/fields/livres_list.html.twig'),
        ];
    }
}
