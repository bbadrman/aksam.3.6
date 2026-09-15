<?php

namespace App\Form;

use App\Dto\UserFormDTO;
use App\Entity\Product;
use App\Entity\Team;
use App\Security\Permission;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;

class UserType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('username', TextType::class, [
                'label' => 'Identifiant',
                'constraints' => [new NotBlank(message: "L'identifiant est obligatoire.")],
            ])
            ->add('nom', TextType::class, [
                'label' => 'Nom',
                'constraints' => [new NotBlank(message: 'Le nom est obligatoire.')],
            ])
            ->add('prenom', TextType::class, [
                'label' => 'Prénom',
                'constraints' => [new NotBlank(message: 'Le prénom est obligatoire.')],
            ])
            ->add('plainPassword', PasswordType::class, [
                'label' => $options['is_edit'] ? 'Nouveau mot de passe' : 'Mot de passe',
                'required' => !$options['is_edit'],
                'constraints' => $options['is_edit'] ? [] : [
                    new NotBlank(message: 'Le mot de passe est obligatoire.'),
                    new Length(min: 8, minMessage: 'Le mot de passe doit contenir au moins {{ limit }} caractères.'),
                ],
                'help' => $options['is_edit'] ? 'Laisser vide pour conserver le mot de passe actuel.' : null,
            ])
            ->add('roles', ChoiceType::class, [
                'label' => 'Rôles',
                'choices' => [
                    'Utilisateur' => 'ROLE_USER',
                    'Administrateur' => 'ROLE_ADMIN',
                ],
                'multiple' => true,
                'expanded' => true,
            ])
            ->add('teams', EntityType::class, [
                'class' => Team::class,
                'choice_label' => 'nom',
                'label' => 'Équipes',
                'multiple' => true,
                'expanded' => false,
                'required' => false,
            ])
            ->add('products', EntityType::class, [
                'class' => Product::class,
                'choice_label' => 'nom',
                'label' => 'Produits',
                'multiple' => true,
                'expanded' => false,
                'required' => false,
            ])
            ->add('permissions', ChoiceType::class, [
                'label' => 'Permissions',
                'choices' => Permission::choices(),
                'multiple' => true,
                'expanded' => true,
                'required' => false,
            ])
            ->add('actif', CheckboxType::class, [
                'label' => 'Compte actif',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => UserFormDTO::class]);
        $resolver->setDefined('is_edit');
        $resolver->setDefault('is_edit', false);
        $resolver->setAllowedTypes('is_edit', 'bool');
    }
}
