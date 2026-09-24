<?php

namespace App\Entity;

use App\Repository\ProspectRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
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

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $ville = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $adresse = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $codePostal = null;

    #[ORM\Column(type: 'date_immutable', nullable: true)]
    private ?\DateTimeImmutable $dateNaissance = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $source = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $typeProspect = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $raisonSociale = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $activites = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $url = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $assurer = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $lastAssure = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $motifResil = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $motifSaisi = null;

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

    /**
     * Motif de l'issue du dernier appel — null tant que le prospect n'a
     * jamais été traité. Distinct de `statut`, qui reflète le cycle de vie
     * global (nouveau/converti/perdu) plutôt que le détail de chaque appel.
     */
    #[ORM\Column(type: 'integer', nullable: true, enumType: RelanceMotif::class)]
    private ?RelanceMotif $relance = null;

    /**
     * @var Collection<int, ProspectRelanceHistory>
     */
    #[ORM\OneToMany(targetEntity: ProspectRelanceHistory::class, mappedBy: 'prospect', cascade: ['persist', 'remove'])]
    private Collection $relanceHistory;

    #[ORM\OneToOne(mappedBy: 'prospect', cascade: ['persist', 'remove'])]
    private ?ProspectVehiculeDetails $vehiculeDetails = null;

    #[ORM\OneToOne(mappedBy: 'prospect', cascade: ['persist', 'remove'])]
    private ?ProspectPrevoyanceDetails $prevoyanceDetails = null;

    #[ORM\OneToOne(mappedBy: 'prospect', cascade: ['persist', 'remove'])]
    private ?ProspectConstructionDetails $constructionDetails = null;

    #[ORM\OneToOne(mappedBy: 'prospect')]
    private ?Client $client = null;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->relanceHistory = new ArrayCollection();
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

    public function getVille(): ?string
    {
        return $this->ville;
    }

    public function setVille(?string $ville): static
    {
        $this->ville = $ville;

        return $this;
    }

    public function getAdresse(): ?string
    {
        return $this->adresse;
    }

    public function setAdresse(?string $adresse): static
    {
        $this->adresse = $adresse;

        return $this;
    }

    public function getCodePostal(): ?string
    {
        return $this->codePostal;
    }

    public function setCodePostal(?string $codePostal): static
    {
        $this->codePostal = $codePostal;

        return $this;
    }

    public function getDateNaissance(): ?\DateTimeImmutable
    {
        return $this->dateNaissance;
    }

    public function setDateNaissance(?\DateTimeImmutable $dateNaissance): static
    {
        $this->dateNaissance = $dateNaissance;

        return $this;
    }

    public function getSource(): ?string
    {
        return $this->source;
    }

    public function setSource(?string $source): static
    {
        $this->source = $source;

        return $this;
    }

    public function getTypeProspect(): ?string
    {
        return $this->typeProspect;
    }

    public function setTypeProspect(?string $typeProspect): static
    {
        $this->typeProspect = $typeProspect;

        return $this;
    }

    public function getRaisonSociale(): ?string
    {
        return $this->raisonSociale;
    }

    public function setRaisonSociale(?string $raisonSociale): static
    {
        $this->raisonSociale = $raisonSociale;

        return $this;
    }

    public function getActivites(): ?string
    {
        return $this->activites;
    }

    public function setActivites(?string $activites): static
    {
        $this->activites = $activites;

        return $this;
    }

    public function getUrl(): ?string
    {
        return $this->url;
    }

    public function setUrl(?string $url): static
    {
        $this->url = $url;

        return $this;
    }

    public function getAssurer(): ?string
    {
        return $this->assurer;
    }

    public function setAssurer(?string $assurer): static
    {
        $this->assurer = $assurer;

        return $this;
    }

    public function getLastAssure(): ?string
    {
        return $this->lastAssure;
    }

    public function setLastAssure(?string $lastAssure): static
    {
        $this->lastAssure = $lastAssure;

        return $this;
    }

    public function getMotifResil(): ?string
    {
        return $this->motifResil;
    }

    public function setMotifResil(?string $motifResil): static
    {
        $this->motifResil = $motifResil;

        return $this;
    }

    public function getMotifSaisi(): ?string
    {
        return $this->motifSaisi;
    }

    public function setMotifSaisi(?string $motifSaisi): static
    {
        $this->motifSaisi = $motifSaisi;

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

    public function getRelance(): ?RelanceMotif
    {
        return $this->relance;
    }

    public function setRelance(?RelanceMotif $relance): static
    {
        $this->relance = $relance;

        return $this;
    }

    /**
     * @return Collection<int, ProspectRelanceHistory>
     */
    public function getRelanceHistory(): Collection
    {
        return $this->relanceHistory;
    }

    public function addRelanceHistory(ProspectRelanceHistory $entry): static
    {
        if (!$this->relanceHistory->contains($entry)) {
            $this->relanceHistory->add($entry);
            $entry->setProspect($this);
        }

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

    public function getClient(): ?Client
    {
        return $this->client;
    }
}
