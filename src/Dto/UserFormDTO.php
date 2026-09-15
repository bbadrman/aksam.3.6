<?php

namespace App\Dto;

use App\Entity\Product;
use App\Entity\Team;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;

/**
 * Objet de formulaire pour la création/édition d'un utilisateur — évite de
 * binder le mot de passe en clair directement sur l'entité User.
 */
class UserFormDTO
{
    public ?string $username = null;
    public ?string $nom = null;
    public ?string $prenom = null;
    public bool $actif = true;
    public ?string $plainPassword = null;

    /** @var list<string> */
    public array $roles = [];

    /** @var list<string> */
    public array $permissions = [];

    /** @var Collection<int, Team> */
    public Collection $teams;

    /** @var Collection<int, Product> */
    public Collection $products;

    public function __construct()
    {
        $this->teams = new ArrayCollection();
        $this->products = new ArrayCollection();
    }
}
