<?php

namespace App\Entity;

use App\Repository\ContratVehiculeDetailsRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ContratVehiculeDetailsRepository::class)]
class ContratVehiculeDetails
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(inversedBy: 'vehiculeDetails')]
    #[ORM\JoinColumn(nullable: false, unique: true)]
    private ?Contrat $contrat = null;

    #[ORM\Column(length: 20, nullable: true)]
    private ?string $immatriculation = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $conducteur = null;

    #[ORM\Column(length: 255, nullable: true)]
    private ?string $typePermis = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $datePermis = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?string $crmActuel = null;

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

    public function getImmatriculation(): ?string
    {
        return $this->immatriculation;
    }

    public function setImmatriculation(?string $immatriculation): static
    {
        $this->immatriculation = $immatriculation;

        return $this;
    }

    public function getConducteur(): ?string
    {
        return $this->conducteur;
    }

    public function setConducteur(?string $conducteur): static
    {
        $this->conducteur = $conducteur;

        return $this;
    }

    public function getTypePermis(): ?string
    {
        return $this->typePermis;
    }

    public function setTypePermis(?string $typePermis): static
    {
        $this->typePermis = $typePermis;

        return $this;
    }

    public function getDatePermis(): ?\DateTimeImmutable
    {
        return $this->datePermis;
    }

    public function setDatePermis(?\DateTimeImmutable $datePermis): static
    {
        $this->datePermis = $datePermis;

        return $this;
    }

    public function getCrmActuel(): ?string
    {
        return $this->crmActuel;
    }

    public function setCrmActuel(?string $crmActuel): static
    {
        $this->crmActuel = $crmActuel;

        return $this;
    }

    /**
     * Clone les valeurs vers une nouvelle instance, sans lien vers le
     * contrat d'origine — appelé depuis Contrat::duplicate().
     */
    public function duplicate(): self
    {
        return (new self())
            ->setImmatriculation($this->immatriculation)
            ->setConducteur($this->conducteur)
            ->setTypePermis($this->typePermis)
            ->setDatePermis($this->datePermis)
            ->setCrmActuel($this->crmActuel);
    }
}
