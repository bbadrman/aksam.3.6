<?php

namespace App\Service;

use App\Entity\Compartenaire;
use App\Entity\Product;
use App\Entity\Prospect;
use App\Entity\ProspectConstructionDetails;
use App\Entity\ProspectPrevoyanceDetails;
use App\Entity\ProspectVehiculeDetails;
use Doctrine\ORM\EntityManagerInterface;

class ProspectService
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    /**
     * Crée un Prospect à partir d'un payload API entrant, en routant les
     * champs spécifiques vers la bonne entité de détails selon le produit
     * du partenaire (chaque site est dédié à un seul produit, connu via
     * son token — pas d'ambiguïté à la réception).
     *
     * @param array<string, mixed> $data
     */
    public function createFromApiPayload(Compartenaire $partenaire, array $data): Prospect
    {
        $product = $partenaire->getProduct();
        if (!$product instanceof Product) {
            throw new \RuntimeException(sprintf('Le partenaire "%s" n\'a pas de produit configuré.', $partenaire->getNom()));
        }

        $prospect = new Prospect();
        $prospect->setNom((string) ($data['nom'] ?? ''));
        $prospect->setPrenom($data['prenom'] ?? null);
        $prospect->setPhone((string) ($data['phone'] ?? ''));
        $prospect->setEmail($data['email'] ?? null);
        $prospect->setProduct($product);

        match ($product->getCode()) {
            Product::CODE_VEHICULE => $prospect->setVehiculeDetails($this->buildVehiculeDetails($data)),
            Product::CODE_PREVOYANCE => $prospect->setPrevoyanceDetails($this->buildPrevoyanceDetails($data)),
            Product::CODE_CONSTRUCTION => $prospect->setConstructionDetails($this->buildConstructionDetails($data)),
            default => null,
        };

        $this->entityManager->persist($prospect);
        $this->entityManager->flush();

        return $prospect;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function buildVehiculeDetails(array $data): ProspectVehiculeDetails
    {
        $details = new ProspectVehiculeDetails();
        $details->setImmatriculation($data['immatriculation'] ?? null);
        $details->setMarque($data['marque'] ?? null);
        $details->setModele($data['modele'] ?? null);

        return $details;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function buildPrevoyanceDetails(array $data): ProspectPrevoyanceDetails
    {
        $details = new ProspectPrevoyanceDetails();
        $details->setCapitalSouhaite(isset($data['capitalSouhaite']) ? (string) $data['capitalSouhaite'] : null);
        $details->setSituationFamiliale($data['situationFamiliale'] ?? null);
        $details->setProfession($data['profession'] ?? null);

        return $details;
    }

    /**
     * @param array<string, mixed> $data
     */
    private function buildConstructionDetails(array $data): ProspectConstructionDetails
    {
        $details = new ProspectConstructionDetails();
        $details->setTypeBien($data['typeBien'] ?? null);
        $details->setSurfaceM2(isset($data['surfaceM2']) ? (string) $data['surfaceM2'] : null);
        $details->setAdresseBien($data['adresseBien'] ?? null);

        return $details;
    }
}
