<?php

namespace App\Dto;

use App\Entity\Client;
use App\Entity\Compartenaire;
use App\Entity\Product;
use App\Entity\User;

/**
 * Objet de formulaire pour la création d'un contrat — évite de binder
 * ContratType directement sur l'entité Contrat, qui porte des relations
 * (Payment, Document, extensions produit) sans rapport avec la saisie
 * initiale du contrat.
 */
class ContratFormDTO
{
    public ?Client $client = null;
    public ?Product $product = null;
    public ?Compartenaire $compagnie = null;
    public ?User $gestionnaire = null;
    public ?string $cotisation = null;
    public ?string $fraction = null;
    public ?string $garanties = null;
    public ?string $comment = null;

    // Champs spécifiques véhicule — utilisés uniquement si product = véhicule.
    public ?string $immatriculation = null;
    public ?string $conducteur = null;
    public ?string $typePermis = null;
    public ?\DateTimeImmutable $datePermis = null;
    public ?string $crmActuel = null;
}
