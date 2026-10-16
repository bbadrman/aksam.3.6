<?php

namespace App\Form;

use App\Entity\Client;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type as Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;

/**
 * Décision sur un pré-client (valider / rejeter / annuler). Non mappé sur
 * l'entité : la transition est appliquée par ClientService::decider().
 */
class ClientStatusType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('status', Type\ChoiceType::class, [
                'label' => 'status',
                'required' => true,
                'attr' => ['class' => 'status-choice'], // classe sur le conteneur
                'choices' => Client::STATUS_CHOICES,
                'expanded' => true,
                'multiple' => false,
                'constraints' => [new NotBlank(message: 'Choisissez une décision.')],
            ])
        ;
    }
}
