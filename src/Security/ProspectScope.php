<?php

namespace App\Security;

use App\Entity\User;

/**
 * Restriction à appliquer à une requête de liste de prospects, selon le
 * rôle de l'utilisateur courant. Remplace le pattern de l'ancien projet qui
 * dupliquait chaque requête en 3 méthodes (Admin/Chef/Commercial) :
 * ici une seule méthode de repository accepte un ProspectScope.
 */
final class ProspectScope
{
    private function __construct(
        public readonly bool $unrestricted,
        public readonly ?array $teamIds,
        public readonly ?User $commercial,
    ) {
    }

    public static function all(): self
    {
        return new self(true, null, null);
    }

    /**
     * @param int[] $teamIds
     */
    public static function forTeams(array $teamIds): self
    {
        return new self(false, $teamIds, null);
    }

    public static function forCommercial(User $commercial): self
    {
        return new self(false, null, $commercial);
    }
}
