<?php

namespace App\Entity;

use App\Repository\PaymentRepository;
use Doctrine\ORM\Mapping as ORM;

/**
 * Un seul Payment par Contrat (OneToOne côté Contrat) — l'ancien projet
 * nommait ce champ au pluriel (`payments`) alors qu'il s'agissait déjà
 * d'un objet unique, ce qui laissait croire à une collection.
 */
#[ORM\Entity(repositoryClass: PaymentRepository::class)]
class Payment
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column]
    private ?int $id = null;

    #[ORM\OneToOne(mappedBy: 'payment')]
    private ?Contrat $contrat = null;

    #[ORM\Column(type: 'decimal', precision: 10, scale: 2, nullable: true)]
    private ?string $montant = null;

    #[ORM\Column(length: 50, nullable: true)]
    private ?string $methode = null;

    #[ORM\Column(nullable: true)]
    private ?\DateTimeImmutable $datePaiement = null;

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getContrat(): ?Contrat
    {
        return $this->contrat;
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

    public function getMethode(): ?string
    {
        return $this->methode;
    }

    public function setMethode(?string $methode): static
    {
        $this->methode = $methode;

        return $this;
    }

    public function getDatePaiement(): ?\DateTimeImmutable
    {
        return $this->datePaiement;
    }

    public function setDatePaiement(?\DateTimeImmutable $datePaiement): static
    {
        $this->datePaiement = $datePaiement;

        return $this;
    }
}
