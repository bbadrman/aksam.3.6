<?php

namespace App\Form;

use App\Dto\ContratFormDTO;
use App\Entity\Client;
use App\Entity\Compartenaire;
use App\Entity\Product;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\NotBlank;

class ContratType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('client', EntityType::class, [
                'class' => Client::class,
                'choice_label' => 'displayName',
                'constraints' => [new NotBlank()],
            ])
            ->add('product', EntityType::class, [
                'class' => Product::class,
                'choice_label' => 'nom',
                'constraints' => [new NotBlank()],
            ])
            ->add('compagnie', EntityType::class, [
                'class' => Compartenaire::class,
                'choice_label' => 'nom',
                'required' => false,
            ])
            ->add('gestionnaire', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'username',
                'required' => false,
            ])
            ->add('cotisation', MoneyType::class, ['required' => false, 'divisor' => 1])
            ->add('fraction', TextType::class, ['required' => false])
            ->add('garanties', TextareaType::class, ['required' => false])
            ->add('comment', TextareaType::class, ['required' => false])
            // Champs véhicule — affichage conditionnel géré côté template/JS,
            // la validation métier (produit véhicule ⇒ champs pertinents)
            // reste dans ContratService, pas dans le formulaire.
            ->add('immatriculation', TextType::class, ['required' => false])
            ->add('conducteur', TextType::class, ['required' => false])
            ->add('typePermis', TextType::class, ['required' => false])
            ->add('datePermis', DateTimeType::class, ['required' => false, 'widget' => 'single_text'])
            ->add('crmActuel', TextType::class, ['required' => false])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => ContratFormDTO::class]);
    }
}
