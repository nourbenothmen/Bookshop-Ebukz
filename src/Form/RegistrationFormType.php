<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;     // AJOUTE ÇA
use Symfony\Component\Form\Extension\Core\Type\EmailType;    // (facultatif mais propre)
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class RegistrationFormType extends AbstractType
{
 public function buildForm(FormBuilderInterface $builder, array $options): void
{
    $builder
        ->add('prenom', TextType::class, [
            'label' => 'Prénom',
            'attr' => ['placeholder' => 'Mohamed']
        ])
        ->add('nom', TextType::class, [
            'label' => 'Nom',
            'attr' => ['placeholder' => 'Ben Othmen']
        ])
        ->add('email', null, [
            'label' => 'Email',
            'attr' => ['placeholder' => 'exemple@gmail.com']
        ])
        ->add('plainPassword', PasswordType::class, [
            'mapped' => false,
            'label' => 'Mot de passe',
            'attr' => ['autocomplete' => 'new-password', 'placeholder' => 'Minimum 6 caractères'],
            'constraints' => [
                new NotBlank(['message' => 'Veuillez entrer un mot de passe']),
                new Length(['min' => 6, 'minMessage' => 'Minimum 6 caractères']),
            ],
        ])
        ->add('agreeTerms', CheckboxType::class, [
            'mapped' => false,
            'label' => 'J\'accepte les conditions d\'utilisation',
            'constraints' => [new IsTrue(['message' => 'Vous devez accepter les conditions'])],
        ])
    ;
}
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
