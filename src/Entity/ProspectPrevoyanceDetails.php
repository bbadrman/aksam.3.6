<?php

namespace App\Entity;

use App\Repository\ProspectPrevoyanceDetailsRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProspectPrevoyanceDetailsRepository::class)]
class ProspectPrevoyanceDetails
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'prevoyanceDetails')]
    #[ORM\JoinColumn(nullable: false, unique: true)]
    private ?Prospect $prospect = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?string $capitalSouhaite = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $situationFamiliale = null;

    #[ORM\Column(nullable: true)]
    private ?int $nombreEnfants = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $profession = null;

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

    public function getCapitalSouhaite(): ?string
    {
        return $this->capitalSouhaite;
    }

    public function setCapitalSouhaite(?string $capitalSouhaite): static
    {
        $this->capitalSouhaite = $capitalSouhaite;

        return $this;
    }

    public function getSituationFamiliale(): ?string
    {
        return $this->situationFamiliale;
    }

    public function setSituationFamiliale(?string $situationFamiliale): static
    {
        $this->situationFamiliale = $situationFamiliale;

        return $this;
    }

    public function getNombreEnfants(): ?int
    {
        return $this->nombreEnfants;
    }

    public function setNombreEnfants(?int $nombreEnfants): static
    {
        $this->nombreEnfants = $nombreEnfants;

        return $this;
    }

    public function getProfession(): ?string
    {
        return $this->profession;
    }

    public function setProfession(?string $profession): static
    {
        $this->profession = $profession;

        return $this;
    }
}
