<?php

namespace App\Entity;

use App\Repository\ProspectRelanceHistoryRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Trace chaque appel/relance effectué sur un prospect — l'entité Prospect
 * ne garde que le dernier motif (`relance`), cet historique garde tout.
 */
#[ORM\Entity(repositoryClass: ProspectRelanceHistoryRepository::class)]
class ProspectRelanceHistory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Prospect::class, inversedBy: 'relanceHistory')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Prospect $prospect = null;

    #[ORM\Column(type: 'integer', enumType: RelanceMotif::class)]
    private ?RelanceMotif $motif = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $comment = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    private ?User $auteur = null;

    #[ORM\Column]
    private \DateTimeImmutable $relancedAt;

    public function __construct()
    {
        $this->relancedAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getProspect(): ?Prospect
    {
        return $this->prospect;
    }

    public function setProspect(Prospect $prospect): static
    {
        $this->prospect = $prospect;

        return $this;
    }

    public function getMotif(): ?RelanceMotif
    {
        return $this->motif;
    }

    public function setMotif(RelanceMotif $motif): static
    {
        $this->motif = $motif;

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

    public function getAuteur(): ?User
    {
        return $this->auteur;
    }

    public function setAuteur(?User $auteur): static
    {
        $this->auteur = $auteur;

        return $this;
    }

    public function getRelancedAt(): \DateTimeImmutable
    {
        return $this->relancedAt;
    }
}
