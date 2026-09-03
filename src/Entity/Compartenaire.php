<?php

namespace App\Entity;

use App\Repository\CompartenaireRepository;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: CompartenaireRepository::class)]
class Compartenaire
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\Column(length: 255)]
    private ?string $nom = null;

    #[ORM\Column(length: 255, nullable: true, unique: true)]
    private ?string $apiToken = null;

    #[ORM\Column]
    private bool $apiActif = false;

    /**
     * Produit envoyé par ce partenaire — chaque site est dédié à un seul
     * produit (véhicule, prévoyance, construction...), connu à l'avance.
     */
    #[ORM\ManyToOne(targetEntity: Product::class)]
    #[ORM\JoinColumn(nullable: true)]
    private ?Product $product = null;

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

    public function getApiToken(): ?string
    {
        return $this->apiToken;
    }

    public function setApiToken(?string $apiToken): static
    {
        $this->apiToken = $apiToken;

        return $this;
    }

    public function isApiActif(): bool
    {
        return $this->apiActif;
    }

    public function setApiActif(bool $apiActif): static
    {
        $this->apiActif = $apiActif;

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

    public function __toString(): string
    {
        return (string) $this->nom;
    }
}
