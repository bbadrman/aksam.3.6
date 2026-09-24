<?php

namespace App\Entity;

use App\Repository\TeamProductLimitRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Quota de prospects actifs qu'une équipe peut recevoir pour un produit
 * donné — consulté par ProspectDistributionService lors de la distribution
 * automatique (round-robin avec limite souple, voir selectTeam()).
 */
#[ORM\Entity(repositoryClass: TeamProductLimitRepository::class)]
#[ORM\Table(name: 'team_product_limit')]
#[ORM\UniqueConstraint(name: 'uniq_team_product', columns: ['team_id', 'product_id'])]
class TeamProductLimit
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: Team::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Team $team = null;

    #[ORM\ManyToOne(targetEntity: Product::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private ?Product $product = null;

    /**
     * Nombre maximum de prospects actifs pour ce couple équipe/produit.
     * null = illimité. 0 = équipe exclue de la distribution pour ce produit.
     */
    #[ORM\Column(nullable: true)]
    private ?int $maxProspects = null;

    public function getId(): ?int
    {
        return $this->id;
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

    public function getProduct(): ?Product
    {
        return $this->product;
    }

    public function setProduct(?Product $product): static
    {
        $this->product = $product;

        return $this;
    }

    public function getMaxProspects(): ?int
    {
        return $this->maxProspects;
    }

    public function setMaxProspects(?int $maxProspects): static
    {
        $this->maxProspects = $maxProspects;

        return $this;
    }

    public function isLimited(): bool
    {
        return $this->maxProspects !== null;
    }

    public function isBlocked(): bool
    {
        return $this->maxProspects === 0;
    }
}
