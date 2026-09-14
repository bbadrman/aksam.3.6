<?php

namespace App\Service;

use App\Dto\ProspectFormDTO;
use App\Entity\Compartenaire;
use App\Entity\Product;
use App\Entity\Prospect;
use App\Entity\ProspectConstructionDetails;
use App\Entity\ProspectPrevoyanceDetails;
use App\Entity\ProspectVehiculeDetails;
use App\Entity\Team;
use App\Entity\User;
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
     * Création manuelle depuis l'écran "Nouveau prospect" (cf. l'ancien
     * /prospect/new) — équipe/commercial peuvent être choisis directement
     * ici, sans passer par le flux d'ingestion API.
     */
    public function createManually(ProspectFormDTO $dto): Prospect
    {
        $prospect = new Prospect();
        $prospect->setNom((string) $dto->nom);
        $prospect->setPrenom($dto->prenom);
        $prospect->setPhone((string) $dto->phone);
        $prospect->setEmail($dto->email);
        $prospect->setProduct($dto->product);
        $prospect->setTeam($dto->team);
        $prospect->setCommercial($dto->commercial);

        if ($dto->team !== null || $dto->commercial !== null) {
            $prospect->setAffectAt(new \DateTimeImmutable());
        }

        match ($dto->product?->getCode()) {
            Product::CODE_VEHICULE => $prospect->setVehiculeDetails(
                (new ProspectVehiculeDetails())
                    ->setImmatriculation($dto->immatriculation)
                    ->setMarque($dto->marque)
                    ->setModele($dto->modele)
            ),
            Product::CODE_PREVOYANCE => $prospect->setPrevoyanceDetails(
                (new ProspectPrevoyanceDetails())
                    ->setSituationFamiliale($dto->situationFamiliale)
                    ->setProfession($dto->profession)
            ),
            Product::CODE_CONSTRUCTION => $prospect->setConstructionDetails(
                (new ProspectConstructionDetails())
                    ->setTypeBien($dto->typeBien)
                    ->setAdresseBien($dto->adresseBien)
            ),
            default => null,
        };

        $this->entityManager->persist($prospect);
        $this->entityManager->flush();

        return $prospect;
    }

    /**
     * Affecte (ou réaffecte) un prospect à une équipe et/ou un commercial —
     * depuis les écrans de cycle de traitement. Met à jour la date
     * d'affectation à chaque affectation, y compris une réaffectation.
     */
    public function affecter(Prospect $prospect, ?Team $team, ?User $commercial): void
    {
        $prospect->setTeam($team);
        $prospect->setCommercial($commercial);
        $prospect->setAffectAt($team !== null || $commercial !== null ? new \DateTimeImmutable() : null);

        $this->entityManager->flush();
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
