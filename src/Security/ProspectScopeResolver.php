<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Bundle\SecurityBundle\Security;

/**
 * Détermine le ProspectScope de l'utilisateur courant :
 * - ROLE_ADMIN : tout voir
 * - ROLE_CHEF_EQUIPE (ou simplement membre d'une équipe) : restreint à ses équipes
 * - sinon (commercial) : restreint à ses propres prospects assignés
 */
class ProspectScopeResolver
{
    public function __construct(private readonly Security $security)
    {
    }

    public function resolve(): ProspectScope
    {
        if ($this->security->isGranted('ROLE_ADMIN')) {
            return ProspectScope::all();
        }

        /** @var User $user */
        $user = $this->security->getUser();

        if ($this->security->isGranted('ROLE_CHEF_EQUIPE') && !$user->getTeams()->isEmpty()) {
            $teamIds = array_map(fn ($team) => $team->getId(), $user->getTeams()->toArray());

            return ProspectScope::forTeams($teamIds);
        }

        return ProspectScope::forCommercial($user);
    }
}
