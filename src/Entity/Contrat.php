<?php

namespace App\Entity;

use App\Repository\ContratRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

/**
 * Champs communs à tous les produits. Les champs spécifiques à un produit
 * (ex: immatriculation, permis pour un contrat véhicule) vivent dans une
 * entité d'extension dédiée (ContratVehiculeDetails, ...) — même pattern
 * que Prospect, pour éviter de reproduire le "god entity" de l'ancien
 * projet qui mélangeait tout dans une seule table de 1050 lignes.
 */
#[ORM\Entity(repositoryClass: ContratRepository::class)]
#[ORM\Index(columns: ['statut'], name: 'idx_contrat_statut')]
class Contrat
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Client::class, inversedBy: 'contrats')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Client $client = null;

    #[ORM\ManyToOne(targetEntity: Product::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Product $product = null;

    #[ORM\ManyToOne(targetEntity: Compartenaire::class)]
    private ?Compartenaire $compagnie = null;

    /**
     * Commercial/gestionnaire responsable du contrat.
     */
    #[ORM\ManyToOne(targetEntity: User::class)]
    private ?User $gestionnaire = null;

    #[ORM\Column(length: 20)]
    private string $statut = ContratStatut::BROUILLON->value;

    /**
     * Attribué au moment de la validation — cf. ContratService::valider().
     */
    #[ORM\Column(length: 255, nullable: true, unique: true)]
    private ?string $numeroPolice = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $dateSouscription = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $dateEffet = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?string $cotisation = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $fraction = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $garanties = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $comment = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\OneToOne(inversedBy: 'contrat', cascade: ['persist', 'remove'])]
    private ?Payment $payment = null;

    #[ORM\OneToOne(inversedBy: 'contrat', cascade: ['persist', 'remove'])]
    private ?Document $document = null;

    #[ORM\OneToOne(mappedBy: 'contrat', cascade: ['persist', 'remove'])]
    private ?ContratVehiculeDetails $vehiculeDetails = null;

    /**
     * @var Collection<int, Frais>
     */
    #[ORM\OneToMany(targetEntity: Frais::class, mappedBy: 'contrat', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $frais;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->frais = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getClient(): ?Client
    {
        return $this->client;
    }

    public function setClient(Client $client): static
    {
        $this->client = $client;

        return $this;
    }

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(Product $product): static
    {
        $this->product = $product;

        return $this;
    }

    public function getCompagnie(): ?Compartenaire
    {
        return $this->compagnie;
    }

    public function setCompagnie(?Compartenaire $compagnie): static
    {
        $this->compagnie = $compagnie;

        return $this;
    }

    public function getGestionnaire(): ?User
    {
        return $this->gestionnaire;
    }

    public function setGestionnaire(?User $gestionnaire): static
    {
        $this->gestionnaire = $gestionnaire;

        return $this;
    }

    public function getStatut(): ContratStatut
    {
        return ContratStatut::from($this->statut);
    }

    public function setStatut(ContratStatut $statut): static
    {
        $this->statut = $statut->value;

        return $this;
    }

    public function getNumeroPolice(): ?string
    {
        return $this->numeroPolice;
    }

    public function setNumeroPolice(?string $numeroPolice): static
    {
        $this->numeroPolice = $numeroPolice;

        return $this;
    }

    public function getDateSouscription(): ?\DateTimeImmutable
    {
        return $this->dateSouscription;
    }

    public function setDateSouscription(?\DateTimeImmutable $dateSouscription): static
    {
        $this->dateSouscription = $dateSouscription;

        return $this;
    }

    public function getDateEffet(): ?\DateTimeImmutable
    {
        return $this->dateEffet;
    }

    public function setDateEffet(?\DateTimeImmutable $dateEffet): static
    {
        $this->dateEffet = $dateEffet;

        return $this;
    }

    public function getCotisation(): ?string
    {
        return $this->cotisation;
    }

    public function setCotisation(?string $cotisation): static
    {
        $this->cotisation = $cotisation;

        return $this;
    }

    public function getFraction(): ?string
    {
        return $this->fraction;
    }

    public function setFraction(?string $fraction): static
    {
        $this->fraction = $fraction;

        return $this;
    }

    public function getGaranties(): ?string
    {
        return $this->garanties;
    }

    public function setGaranties(?string $garanties): static
    {
        $this->garanties = $garanties;

        return $this;
    }

    public function getComment(): ?string
    {
        return $this->comment;
    }

    public function setComment(?string $comment): static
    {
        $this->comment = $comment;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getPayment(): ?Payment
    {
        return $this->payment;
    }

    public function setPayment(?Payment $payment): static
    {
        $this->payment = $payment;

        return $this;
    }

    public function getDocument(): ?Document
    {
        return $this->document;
    }

    public function setDocument(?Document $document): static
    {
        $this->document = $document;

        return $this;
    }

    public function getVehiculeDetails(): ?ContratVehiculeDetails
    {
        return $this->vehiculeDetails;
    }

    public function setVehiculeDetails(ContratVehiculeDetails $details): static
    {
        $details->setContrat($this);
        $this->vehiculeDetails = $details;

        return $this;
    }

    /**
     * @return Collection<int, Frais>
     */
    public function getFrais(): Collection
    {
        return $this->frais;
    }

    public function addFrais(Frais $frais): static
    {
        if (!$this->frais->contains($frais)) {
            $this->frais->add($frais);
            $frais->setContrat($this);
        }

        return $this;
    }

    /**
     * Clone les champs pertinents d'un contrat existant vers un nouveau, en
     * un seul endroit — résout le problème de l'ancien
     * ContratController::new() qui recopiait ~35 champs à la main dans le
     * contrôleur (et partageait par erreur le même Payment entre les deux
     * contrats, ce qui violait la contrainte d'unicité OneToOne).
     *
     * Ne copie jamais Payment/Document : ce sont des objets propres à une
     * souscription donnée, pas des gabarits à dupliquer. Le nouveau contrat
     * démarre toujours en BROUILLON, sans numéro de police.
     */
    public function duplicate(): self
    {
        $copie = new self();
        $copie->setClient($this->client);
        $copie->setProduct($this->product);
        $copie->setCompagnie($this->compagnie);
        $copie->setGestionnaire($this->gestionnaire);
        $copie->setCotisation($this->cotisation);
        $copie->setFraction($this->fraction);
        $copie->setGaranties($this->garanties);

        if ($this->vehiculeDetails !== null) {
            $copie->setVehiculeDetails($this->vehiculeDetails->duplicate());
        }

        foreach ($this->frais as $frais) {
            $copie->addFrais($frais->duplicate());
        }

        return $copie;
    }
}
