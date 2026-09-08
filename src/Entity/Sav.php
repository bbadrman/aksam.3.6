<?php

namespace App\Entity;

use App\Repository\SavRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SavRepository::class)]
#[ORM\Index(columns: ['statut'], name: 'idx_sav_statut')]
class Sav
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Contrat::class, inversedBy: 'savs')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Contrat $contrat = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $natureDemande = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $motif = null;

    #[ORM\Column(length: 20)]
    private string $statut = SavStatus::OUVERT->value;

    #[ORM\ManyToOne(targetEntity: User::class)]
    private ?User $gestionnaire = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?string $montant = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $relanceAt = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $closedAt = null;

    /**
     * @var Collection<int, SavRelanceHistory>
     */
    #[ORM\OneToMany(targetEntity: SavRelanceHistory::class, mappedBy: 'sav', cascade: ['persist', 'remove'])]
    private Collection $relanceHistory;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
        $this->relanceHistory = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContrat(): ?Contrat
    {
        return $this->contrat;
    }

    public function setContrat(Contrat $contrat): static
    {
        $this->contrat = $contrat;

        return $this;
    }

    public function getNatureDemande(): ?string
    {
        return $this->natureDemande;
    }

    public function setNatureDemande(?string $natureDemande): static
    {
        $this->natureDemande = $natureDemande;

        return $this;
    }

    public function getMotif(): ?string
    {
        return $this->motif;
    }

    public function setMotif(?string $motif): static
    {
        $this->motif = $motif;

        return $this;
    }

    public function getStatut(): SavStatus
    {
        return SavStatus::from($this->statut);
    }

    public function setStatut(SavStatus $statut): static
    {
        $this->statut = $statut->value;

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

    public function getMontant(): ?string
    {
        return $this->montant;
    }

    public function setMontant(?string $montant): static
    {
        $this->montant = $montant;

        return $this;
    }

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
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

    public function getClosedAt(): ?\DateTimeImmutable
    {
        return $this->closedAt;
    }

    public function setClosedAt(?\DateTimeImmutable $closedAt): static
    {
        $this->closedAt = $closedAt;

        return $this;
    }

    /**
     * @return Collection<int, SavRelanceHistory>
     */
    public function getRelanceHistory(): Collection
    {
        return $this->relanceHistory;
    }

    public function addRelanceHistory(SavRelanceHistory $entry): static
    {
        if (!$this->relanceHistory->contains($entry)) {
            $this->relanceHistory->add($entry);
            $entry->setSav($this);
        }

        return $this;
    }
}
