<?php

namespace App\Entity;

use App\Repository\SavRelanceHistoryRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Trace chaque relance effectuée sur un SAV — même principe que
 * ProspectRelanceHistory pour Prospect.
 */
#[ORM\Entity(repositoryClass: SavRelanceHistoryRepository::class)]
class SavRelanceHistory
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Sav::class, inversedBy: 'relanceHistory')]
    #[ORM\JoinColumn(nullable: false)]
    private ?Sav $sav = null;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $comment = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    private ?User $auteur = null;

    #[ORM\Column]
    private \DateTimeImmutable $createdAt;

    public function __construct()
    {
        $this->createdAt = new \DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getSav(): ?Sav
    {
        return $this->sav;
    }

    public function setSav(Sav $sav): static
    {
        $this->sav = $sav;

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

    public function getCreatedAt(): \DateTimeImmutable
    {
        return $this->createdAt;
    }
}
