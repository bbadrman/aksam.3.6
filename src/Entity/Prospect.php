<?php

namespace App\Entity;

use App\Repository\ProspectRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProspectRepository::class)]
#[ORM\Index(columns: ['statut'], name: 'idx_prospect_statut')]
#[ORM\Index(columns: ['relance_at'], name: 'idx_prospect_relance_at')]
#[ORM\Index(columns: ['affect_at'], name: 'idx_prospect_affect_at')]
#[ORM\Index(columns: ['created_at'], name: 'idx_prospect_created_at')]
class Prospect
{
    public const STATUT_NOUVEAU = 'nouveau';
    public const STATUT_EN_COURS = 'en_cours';
    public const STATUT_CONVERTI = 'converti';
    public const STATUT_PERDU = 'perdu';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $prenom = null;

    #[ORM\Column(length: 255)]
    private ?string $phone = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $email = null;

    #[ORM\Column(length: 30)]
    private string $statut = self::STATUT_NOUVEAU;

    #[ORM\ManyToOne(targetEntity: Product::class)]
    #[ORM\JoinColumn(nullable: false)]
    private ?Product $product = null;

    #[ORM\ManyToOne(targetEntity: Team::class)]
    private ?Team $team = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    private ?User $commercial = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $affectAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $relanceAt = null;

    #[ORM\OneToOne(mappedBy: 'prospect', cascade: ['persist', 'remove'])]
    private ?ProspectVehiculeDetails $vehiculeDetails = null;

    #[ORM\OneToOne(mappedBy: 'prospect', cascade: ['persist', 'remove'])]
    private ?ProspectPrevoyanceDetails $prevoyanceDetails = null;

    #[ORM\OneToOne(mappedBy: 'prospect', cascade: ['persist', 'remove'])]
    private ?ProspectConstructionDetails $constructionDetails = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getNom(): ?string
    {
        return $this->nom;
    }

    public function setNom(string $nom): static
    {
        $this->nom = $nom;

        return $this;
    }

    public function getPrenom(): ?string
    {
        return $this->prenom;
    }

    public function setPrenom(?string $prenom): static
    {
        $this->prenom = $prenom;

        return $this;
    }

    public function getPhone(): ?string
    {
        return $this->phone;
    }

    public function setPhone(string $phone): static
    {
        $this->phone = $phone;

        return $this;
    }

    public function getEmail(): ?string
    {
        return $this->email;
    }

    public function setEmail(?string $email): static
    {
        $this->email = $email;

        return $this;
    }

    public function getStatut(): string
    {
        return $this->statut;
    }

    public function setStatut(string $statut): static
    {
        $this->statut = $statut;

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

    public function getTeam(): ?Team
    {
        return $this->team;
    }

    public function setTeam(?Team $team): static
    {
        $this->team = $team;

        return $this;
    }

    public function getCommercial(): ?User
    {
        return $this->commercial;
    }

    public function setCommercial(?User $commercial): static
    {
        $this->commercial = $commercial;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getAffectAt(): ?\DateTimeImmutable
    {
        return $this->affectAt;
    }

    public function setAffectAt(?\DateTimeImmutable $affectAt): static
    {
        $this->affectAt = $affectAt;

        return $this;
    }

    public function getRelanceAt(): ?\DateTimeImmutable
    {
        return $this->relanceAt;
    }

    public function setRelanceAt(?\DateTimeImmutable $relanceAt): static
    {
        $this->relanceAt = $relanceAt;

        return $this;
    }

    public function getVehiculeDetails(): ?ProspectVehiculeDetails
    {
        return $this->vehiculeDetails;
    }

    public function setVehiculeDetails(ProspectVehiculeDetails $details): static
    {
        $details->setProspect($this);
        $this->vehiculeDetails = $details;

        return $this;
    }

    public function getPrevoyanceDetails(): ?ProspectPrevoyanceDetails
    {
        return $this->prevoyanceDetails;
    }

    public function setPrevoyanceDetails(ProspectPrevoyanceDetails $details): static
    {
        $details->setProspect($this);
        $this->prevoyanceDetails = $details;

        return $this;
    }

    public function getConstructionDetails(): ?ProspectConstructionDetails
    {
        return $this->constructionDetails;
    }

    public function setConstructionDetails(ProspectConstructionDetails $details): static
    {
        $details->setProspect($this);
        $this->constructionDetails = $details;

        return $this;
    }
}
