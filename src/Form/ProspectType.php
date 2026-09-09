<?php

namespace App\Form;

use App\Dto\ProspectFormDTO;
use App\Entity\Product;
use App\Entity\Team;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\TelType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class ProspectType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom', TextType::class, [
                'label' => 'Nom',
                'constraints' => [new NotBlank(message: 'Le nom est obligatoire.')],
            ])
            ->add('prenom', TextType::class, ['label' => 'Prénom', 'required' => false])
            ->add('phone', TelType::class, [
                'label' => 'Téléphone',
                'constraints' => [new NotBlank(message: 'Le téléphone est obligatoire.')],
            ])
            ->add('email', EmailType::class, ['label' => 'Email', 'required' => false])
            ->add('product', EntityType::class, [
                'class' => Product::class,
                'choice_label' => 'nom',
                'label' => 'Produit',
                'constraints' => [new NotBlank(message: 'Le produit est obligatoire.')],
            ])
            ->add('team', EntityType::class, [
                'class' => Team::class,
                'choice_label' => 'nom',
                'label' => 'Équipe',
                'required' => false,
            ])
            ->add('commercial', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'username',
                'label' => 'Commercial',
                'required' => false,
            ])
            // Champs spécifiques produit — affichage conditionnel géré côté
            // template/JS, le routage vers la bonne extension reste dans
            // ProspectService, pas dans le formulaire.
            ->add('immatriculation', TextType::class, ['label' => 'Immatriculation', 'required' => false])
            ->add('marque', TextType::class, ['label' => 'Marque', 'required' => false])
            ->add('modele', TextType::class, ['label' => 'Modèle', 'required' => false])
            ->add('situationFamiliale', TextType::class, ['label' => 'Situation familiale', 'required' => false])
            ->add('profession', TextType::class, ['label' => 'Profession', 'required' => false])
            ->add('typeBien', TextType::class, ['label' => 'Type de bien', 'required' => false])
            ->add('adresseBien', TextType::class, ['label' => 'Adresse du bien', 'required' => false])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => ProspectFormDTO::class]);
    }
}
