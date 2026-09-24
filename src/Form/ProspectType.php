<?php

namespace App\Form;

use App\Dto\ProspectFormDTO;
use App\Entity\Product;
use App\Entity\Team;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
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
            ->add('ville', TextType::class, ['label' => 'Ville', 'required' => false])
            ->add('adresse', TextType::class, ['label' => 'Adresse', 'required' => false])
            ->add('codePostal', TextType::class, ['label' => 'Code postal', 'required' => false])
            ->add('dateNaissance', DateType::class, [
                'label' => 'Date de naissance',
                'required' => false,
                'widget' => 'single_text',
            ])
            ->add('source', ChoiceType::class, [
                'label' => 'Source',
                'required' => false,
                'placeholder' => 'Sélectionner…',
                'choices' => [
                    'Saisie manuelle' => '1',
                    'Revendeur' => '2',
                    'Propre site' => '3',
                ],
            ])
            ->add('typeProspect', ChoiceType::class, [
                'label' => 'Type de prospect',
                'required' => false,
                'placeholder' => 'Sélectionner…',
                'choices' => [
                    'Particulier' => 'Particulier',
                    'Professionnel' => 'Professionnel',
                ],
            ])
            ->add('raisonSociale', TextType::class, ['label' => 'Raison sociale', 'required' => false])
            ->add('activites', ChoiceType::class, [
                'label' => 'Activités',
                'required' => false,
                'placeholder' => 'Sélectionner…',
                'choices' => [
                    'TPM' => '1',
                    'VTC' => '2',
                    'Sociéte' => '3',
                    'Décennale' => '4',
                    'Dommage' => '5',
                    'Marchandise' => '6',
                    'Négociant' => '7',
                    'Prof auto' => '8',
                    'Ecole' => '9',
                    'Garage' => '10',
                    'Location-vehicule' => '11',
                    'retraite' => '12',
                    'Taxi' => '13',
                ],
            ])
            ->add('url', ChoiceType::class, [
                'label' => 'URL',
                'required' => false,
                'placeholder' => 'Sélectionner…',
                'choices' => [
                    'ADV' => '1',
                    'AGP' => '2',
                    'APT' => '3',
                    'APV' => '4',
                    'LDR' => '5',
                    'LGD' => '6',
                    'LGD/ADE' => '29',
                    'LGD/ADM' => '30',
                    'LGD/ADRRNP' => '31',
                    'LGD/PADAE' => '32',
                    'LGD/ADP' => '33',
                    'LGD/ADRDP' => '46',
                    'CEL' => '7',
                    'APC' => '8',
                    'AFE' => '9',
                    'AFE/FA' => '37',
                    'AFE/FC' => '38',
                    'AFE/FB' => '39',
                    'AFE/FM' => '40',
                    'AFE/AFEL' => '41',
                    'AFE/FTM' => '42',
                    'AVP' => '10',
                    'AVP/U' => '22',
                    'AVP/PL' => '23',
                    'LDT' => '11',
                    'LDT/ATM' => '43',
                    'LDT/ATM/AMT' => '44',
                    'LDT/AVTDM' => '45',
                    'AEC' => '13',
                    'APA' => '14',
                    'APA/AE' => '15',
                    'APA/NE' => '16',
                    'APA/GA' => '17',
                    'APA/LV' => '18',
                    'PS' => '19',
                    'AMS' => '20',
                    'AMS/SA' => '34',
                    'AMS/FO' => '35',
                    'AMS/IN' => '36',
                    'TMS' => '21',
                    'ATV' => '24',
                    'ATV/PL' => '25',
                    'ATV/M' => '26',
                    'ATV/U' => '27',
                    'DAO' => '28',
                ],
            ])
            ->add('assurer', ChoiceType::class, [
                'label' => 'Assuré actuellement',
                'required' => false,
                'placeholder' => 'Sélectionner…',
                'choices' => [
                    'Oui' => 'Oui',
                    'Non' => 'Non',
                ],
            ])
            ->add('lastAssure', ChoiceType::class, [
                'label' => 'Déjà assuré',
                'required' => false,
                'placeholder' => 'Sélectionner…',
                'choices' => [
                    'Oui' => 'Oui',
                    'Non' => 'Non',
                ],
            ])
            ->add('motifResil', ChoiceType::class, [
                'label' => 'Motif de résiliation',
                'required' => false,
                'placeholder' => 'Sélectionner…',
                'choices' => [
                    'Aggravation de risque' => '1',
                    'Amiable' => '2',
                    'Échéance' => '3',
                    'Non-paiement' => '4',
                    'Sinistre' => '5',
                    'Suspension de permis' => '6',
                    'Fausse déclaration' => '7',
                ],
            ])
            ->add('motifSaisi', ChoiceType::class, [
                'label' => 'Motif saisi',
                'required' => false,
                'placeholder' => 'Sélectionner…',
                'choices' => [
                    'Parrainage' => '1',
                    'Appel Entrant' => '2',
                    'Avenant' => '3',
                    'Ancienne contrat' => '4',
                    'Propre site' => '5',
                    'Revendeur' => '6',
                    'Hors cible' => '7',
                    'Test' => '8',
                    'Ancienne fiche' => '9',
                ],
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
