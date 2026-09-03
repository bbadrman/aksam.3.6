<?php

namespace App\Service;

use App\Entity\Prospect;
use App\Entity\ProspectRelanceHistory;
use App\Entity\RelanceMotif;
use App\Entity\User;
use App\Repository\ProspectRepository;
use App\Security\ProspectScope;
use App\Security\ProspectScopeResolver;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Porte la logique des cycles de traitement d'un prospect (relances du
 * jour, à venir, non traitées, injoignables...) — le contrôleur ne fait
 * que router vers ces méthodes selon l'écran demandé.
 */
class ProspectTraitementService
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ProspectRepository $prospectRepository,
        private readonly ProspectScopeResolver $scopeResolver,
    ) {
    }

    /**
     * Enregistre l'issue d'un appel : trace l'historique et met à jour
     * l'état courant du prospect (dernier motif + prochaine date de
     * relance éventuelle).
     */
    public function enregistrerRelance(
        Prospect $prospect,
        RelanceMotif $motif,
        ?string $comment,
        ?\DateTimeImmutable $prochaineRelance,
        User $auteur,
    ): void {
        $entry = (new ProspectRelanceHistory())
            ->setMotif($motif)
            ->setComment($comment)
            ->setAuteur($auteur);

        $prospect->addRelanceHistory($entry);
        $prospect->setRelance($motif);
        $prospect->setRelanceAt($prochaineRelance);

        $this->entityManager->persist($prospect);
        $this->entityManager->flush();
    }

    /**
     * @return array{items: Prospect[], total: int}
     */
    public function nouveaux(int $page = 1): array
    {
        return $this->prospectRepository->findNouveaux($this->resolveScope(), $page);
    }

    /**
     * @return array{items: Prospect[], total: int}
     */
    public function nonTraites(int $page = 1): array
    {
        $seuil = new \DateTimeImmutable('-24 hours');

        return $this->prospectRepository->findNonTraites($this->resolveScope(), $seuil, $page);
    }

    /**
     * @return array{items: Prospect[], total: int}
     */
    public function relancesDuJour(int $page = 1): array
    {
        $debut = new \DateTimeImmutable('today');
        $fin = new \DateTimeImmutable('today 23:59:59');

        return $this->prospectRepository->findRelancesDuJour($this->resolveScope(), $debut, $fin, $page);
    }

    /**
     * @return array{items: Prospect[], total: int}
     */
    public function relancesAVenir(int $page = 1): array
    {
        $finAujourdhui = new \DateTimeImmutable('today 23:59:59');

        return $this->prospectRepository->findRelancesAVenir($this->resolveScope(), $finAujourdhui, $page);
    }

    /**
     * @return array{items: Prospect[], total: int}
     */
    public function relancesNonTraitees(int $page = 1): array
    {
        $seuil = new \DateTimeImmutable('today');

        return $this->prospectRepository->findRelancesNonTraitees($this->resolveScope(), $seuil, $page);
    }

    /**
     * @return array{items: Prospect[], total: int}
     */
    public function injoignables(int $page = 1): array
    {
        return $this->prospectRepository->findInjoignables($this->resolveScope(), $page);
    }

    private function resolveScope(): ProspectScope
    {
        return $this->scopeResolver->resolve();
    }
}
