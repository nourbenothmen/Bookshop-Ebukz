<?php

namespace App\Form;

use App\Entity\Livre;
use App\Entity\Editeur;
use App\Entity\Categorie;
use App\Entity\Auteur;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Validator\Constraints\File; // IMPORT MANQUANT



class LivreType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('titre')
            ->add('resume', TextareaType::class, [
    'required' => false,
    'label' => 'Résumé du livre',
    'attr' => [
        'rows' => 5,
        'placeholder' => 'Entrez un résumé du livre'
    ]
])

            ->add('qte')
            ->add('pu')
            ->add('isbn')
            ->add('datepub', DateType::class, [
                'widget' => 'single_text',
            ])
            ->add('editeur', EntityType::class, [
                'class' => Editeur::class,
                'choice_label' => 'nom',
            ])
            ->add('categorie', EntityType::class, [
                'class' => Categorie::class,
                'choice_label' => 'designation',
                'required' => false,
            ])
            ->add('auteurs', EntityType::class, [
                'class' => Auteur::class,
                'choice_label' => function ($a) {
                    return $a->getPrenom() . ' ' . $a->getNom();
                },
                'multiple' => true,
                'expanded' => false, // mettre true si tu veux checkbox
            ])
           ->add('image', FileType::class, [
                'label' => 'Image du livre',
                'mapped' => false,
                'required' => false,
                'constraints' => [
                    new File([
                        'maxSize' => '1024k',
                        'mimeTypes' => [
                            'image/jpeg',
                            'image/png',
                            'image/jpg',
                            'image/gif',
                        ],
                        'mimeTypesMessage' => 'Veuillez uploader une image valide (JPEG, PNG, JPG, GIF)',
                    ])
                ],
                'attr' => ['class' => 'form-control']
            ]);

            
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Livre::class,
        ]);
    }
}
