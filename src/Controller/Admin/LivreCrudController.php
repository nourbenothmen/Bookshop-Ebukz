<?php

namespace App\Controller\Admin;

use App\Entity\Livre;
use EasyCorp\Bundle\EasyAdminBundle\Controller\AbstractCrudController;
use EasyCorp\Bundle\EasyAdminBundle\Field\IdField;
use EasyCorp\Bundle\EasyAdminBundle\Field\TextField;
use EasyCorp\Bundle\EasyAdminBundle\Field\IntegerField;
use EasyCorp\Bundle\EasyAdminBundle\Field\NumberField;
use EasyCorp\Bundle\EasyAdminBundle\Field\DateField;
use EasyCorp\Bundle\EasyAdminBundle\Field\AssociationField;
use EasyCorp\Bundle\EasyAdminBundle\Field\ImageField;
use EasyCorp\Bundle\EasyAdminBundle\Field\BooleanField;

class LivreCrudController extends AbstractCrudController
{
    public static function getEntityFqcn(): string
    {
        return Livre::class;
    }

    public function configureFields(string $pageName): iterable
    {
        return [
            IdField::new('id')->hideOnForm(),

            TextField::new('titre', 'Titre du livre'),
             TextField::new('resume', 'résumé'),

            IntegerField::new('quantiteEnStock', 'Quantité en stock'),

            NumberField::new('pu', 'Prix unitaire'),

            IntegerField::new('isbn', 'ISBN'),

            // Image upload
            ImageField::new('image', 'Image du livre')
                ->setBasePath('uploads/livres')
                ->setUploadDir('public/uploads/livres')
                ->setRequired(false),

            DateField::new('datepub', 'Date de publication'),

            // Champs emprunt
            NumberField::new('fraisEmprunt', 'Frais d\'emprunt')
                ->setNumDecimals(2)
                ->setRequired(false),

            NumberField::new('caution', 'Caution')
                ->setNumDecimals(2)
                ->setRequired(false),

            IntegerField::new('dureeMaxEmprunt', 'Durée max emprunt (jours)'),

            BooleanField::new('empruntDisponible', 'Emprunt disponible'),

            // Relations
            AssociationField::new('editeur', 'Éditeur'),

            AssociationField::new('categorie', 'Catégorie'),

            AssociationField::new('auteurs', 'Auteurs')
                ->setFormTypeOptions([
                    'by_reference' => false, // Important pour ManyToMany add/remove
                ]),
        ];
    }
}
