<?php

namespace App\Service;

use App\Entity\Product;
use App\Entity\Prospect;
use App\Entity\Team;
use App\Repository\ProspectRepository;
use App\Repository\TeamProductLimitRepository;

/**
 * Distribution automatique (round-robin par charge) des prospects entrants
 * vers une équipe gérant le produit concerné — l'affectation à un
 * commercial précis au sein de l'équipe reste manuelle (chef d'équipe,
 * via ProspectService::affecter()). Voir docs/distribution-affectation-auto.md.
 */
class ProspectDistributionService
{
    public function __construct(
        private readonly ProspectRepository $prospectRepository,
        private readonly TeamProductLimitRepository $limitRepository,
    ) {
    }

    public function distribute(Prospect $prospect): void
    {
        $product = $prospect->getProduct();
        if ($product === null) {
            return;
        }

        $team = $this->selectTeam($product);
        if ($team === null) {
            return;
        }

        $prospect->setTeam($team);
        $prospect->setAffectAt(new \DateTimeImmutable());
    }

    private function selectTeam(Product $product): ?Team
    {
        /** @var array<int, Team> $teams */
        $teams = [];
        foreach ($product->getUsers() as $user) {
            if (!$user->isActif()) {
                continue;
            }
            foreach ($user->getTeams() as $team) {
                $teams[$team->getId()] = $team;
            }
        }

        if ($teams === []) {
            return null;
        }

        // Une limite à 0 exclut définitivement l'équipe (même en secours) ;
        // une limite positive est une préférence — si toutes les équipes
        // éligibles l'ont atteinte, on retombe quand même sur la moins
        // chargée d'entre elles plutôt que de laisser le prospect orphelin.
        $eligible = null;
        $eligibleLoad = null;
        $fallback = null;
        $fallbackLoad = null;

        foreach ($teams as $team) {
            $limit = $this->limitRepository->findByTeamAndProduct($team, $product);
            if ($limit !== null && $limit->isBlocked()) {
                continue;
            }

            $load = $this->prospectRepository->countActifsByTeamAndProduct($team, $product);
            $overQuota = $limit !== null && $limit->isLimited() && $load >= $limit->getMaxProspects();

            if (!$overQuota) {
                if ($eligibleLoad === null || $load < $eligibleLoad || ($load === $eligibleLoad && $team->getNom() < $eligible->getNom())) {
                    $eligible = $team;
                    $eligibleLoad = $load;
                }
            }

            if ($fallbackLoad === null || $load < $fallbackLoad || ($load === $fallbackLoad && $team->getNom() < $fallback->getNom())) {
                $fallback = $team;
                $fallbackLoad = $load;
            }
        }

        return $eligible ?? $fallback;
    }
}
