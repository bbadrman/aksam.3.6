<?php

namespace App\Dto;

use App\Entity\Product;
use App\Entity\Team;
use App\Entity\User;

/**
 * Objet de formulaire pour la création manuelle d'un prospect (écran
 * "Nouveau prospect", cf. l'ancien /prospect/new) — même principe que
 * ContratFormDTO : évite de binder le formulaire directement sur
 * l'entité Prospect, qui porte des relations (extensions produit,
 * historique de relance) sans rapport avec la saisie initiale.
 */
class ProspectFormDTO
{
    public ?string $nom = null;
    public ?string $prenom = null;
    public ?string $phone = null;
    public ?string $email = null;
    public ?Product $product = null;
    public ?Team $team = null;
    public ?User $commercial = null;

    // Champs spécifiques véhicule — utilisés uniquement si product = véhicule.
    public ?string $immatriculation = null;
    public ?string $marque = null;
    public ?string $modele = null;

    // Champs spécifiques prévoyance — utilisés uniquement si product = prévoyance.
    public ?string $situationFamiliale = null;
    public ?string $profession = null;

    // Champs spécifiques construction — utilisés uniquement si product = construction.
    public ?string $typeBien = null;
    public ?string $adresseBien = null;
}
