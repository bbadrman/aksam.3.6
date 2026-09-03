<?php

namespace App\Service;

use App\Dto\ContratFormDTO;
use App\Entity\Contrat;
use App\Entity\ContratStatut;
use App\Entity\ContratVehiculeDetails;
use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Porte toute la logique métier autour de Contrat — absent de l'ancien
 * projet, où cette logique (création, duplication, validation) vivait
 * directement dans ContratController.
 */
class ContratService
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function create(ContratFormDTO $dto): Contrat
    {
        $contrat = new Contrat();
        $contrat->setClient($dto->client);
        $contrat->setProduct($dto->product);
        $contrat->setCompagnie($dto->compagnie);
        $contrat->setGestionnaire($dto->gestionnaire);
        $contrat->setCotisation($dto->cotisation);
        $contrat->setFraction($dto->fraction);
        $contrat->setGaranties($dto->garanties);
        $contrat->setComment($dto->comment);

        if ($dto->product?->getCode() === Product::CODE_VEHICULE) {
            $contrat->setVehiculeDetails(
                (new ContratVehiculeDetails())
                    ->setImmatriculation($dto->immatriculation)
                    ->setConducteur($dto->conducteur)
                    ->setTypePermis($dto->typePermis)
                    ->setDatePermis($dto->datePermis)
                    ->setCrmActuel($dto->crmActuel)
            );
        }

        $this->entityManager->persist($contrat);
        $this->entityManager->flush();

        return $contrat;
    }

    /**
     * Duplique un contrat existant — délègue le clonage des champs à
     * Contrat::duplicate() (un seul endroit à maintenir), ne fait ici que
     * l'orchestration persistance.
     */
    public function duplicate(Contrat $source): Contrat
    {
        $copie = $source->duplicate();

        $this->entityManager->persist($copie);
        $this->entityManager->flush();

        return $copie;
    }

    /**
     * Valide un contrat en brouillon : attribue un numéro de police et
     * passe le statut à VALIDE. Refuse un contrat déjà validé/résilié.
     */
    public function valider(Contrat $contrat): void
    {
        if ($contrat->getStatut() !== ContratStatut::BROUILLON) {
            throw new \RuntimeException(sprintf(
                'Le contrat #%d ne peut pas être validé depuis le statut "%s".',
                $contrat->getId(),
                $contrat->getStatut()->label(),
            ));
        }

        $contrat->setNumeroPolice($this->genererNumeroPolice($contrat));
        $contrat->setStatut(ContratStatut::VALIDE);

        $this->entityManager->flush();
    }

    private function genererNumeroPolice(Contrat $contrat): string
    {
        return sprintf('POL-%s-%06d', date('Y'), $contrat->getId());
    }
}
