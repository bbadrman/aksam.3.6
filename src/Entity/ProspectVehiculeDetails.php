<?php

namespace App\Entity;

use App\Repository\ProspectVehiculeDetailsRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ProspectVehiculeDetailsRepository::class)]
class ProspectVehiculeDetails
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'vehiculeDetails')]
    #[ORM\JoinColumn(nullable: false, unique: true)]
    private ?Prospect $prospect = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $immatriculation = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $marque = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $modele = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $dateMiseCirculation = null;

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

    public function getImmatriculation(): ?string
    {
        return $this->immatriculation;
    }

    public function setImmatriculation(?string $immatriculation): static
    {
        $this->immatriculation = $immatriculation;

        return $this;
    }

    public function getMarque(): ?string
    {
        return $this->marque;
    }

    public function setMarque(?string $marque): static
    {
        $this->marque = $marque;

        return $this;
    }

    public function getModele(): ?string
    {
        return $this->modele;
    }

    public function setModele(?string $modele): static
    {
        $this->modele = $modele;

        return $this;
    }

    public function getDateMiseCirculation(): ?\DateTimeImmutable
    {
        return $this->dateMiseCirculation;
    }

    public function setDateMiseCirculation(?\DateTimeImmutable $dateMiseCirculation): static
    {
        $this->dateMiseCirculation = $dateMiseCirculation;

        return $this;
    }
}
