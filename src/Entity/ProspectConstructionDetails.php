<?php

namespace App\Entity;

use App\Repository\ProspectConstructionDetailsRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProspectConstructionDetailsRepository::class)]
class ProspectConstructionDetails
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'constructionDetails')]
    #[ORM\JoinColumn(nullable: false, unique: true)]
    private ?Prospect $prospect = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $typeBien = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?string $surfaceM2 = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $dateConstruction = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $adresseBien = null;

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

    public function getTypeBien(): ?string
    {
        return $this->typeBien;
    }

    public function setTypeBien(?string $typeBien): static
    {
        $this->typeBien = $typeBien;

        return $this;
    }

    public function getSurfaceM2(): ?string
    {
        return $this->surfaceM2;
    }

    public function setSurfaceM2(?string $surfaceM2): static
    {
        $this->surfaceM2 = $surfaceM2;

        return $this;
    }

    public function getDateConstruction(): ?\DateTimeImmutable
    {
        return $this->dateConstruction;
    }

    public function setDateConstruction(?\DateTimeImmutable $dateConstruction): static
    {
        $this->dateConstruction = $dateConstruction;

        return $this;
    }

    public function getAdresseBien(): ?string
    {
        return $this->adresseBien;
    }

    public function setAdresseBien(?string $adresseBien): static
    {
        $this->adresseBien = $adresseBien;

        return $this;
    }
}
