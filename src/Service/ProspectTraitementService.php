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
     * Panier "Nouveaux prospects" : prospects qui n'ont pas encore été
     * attribués à un commercial précis, mais qui sont dans le scope de
     * l'utilisateur courant (soit parce qu'il est chef d'équipe, soit
     * parce qu'il est admin et que le scope est "tous les prospects").
     * @return array{items: Prospect[], total: int}
     */
    public function nouveaux(int $page = 1): array
    {
        return $this->prospectRepository->findNouveaux($this->resolveScope(), $page);
    }

    /**
     * Panier "Mes nouveaux prospects" : prospects que l'utilisateur
     * courant s'est attribués (ou qui lui ont été attribués directement),
     * pas encore traités. Distinct de nouveaux(), qui pour un chef ne
     * montre que les prospects de son équipe pas encore attribués à un
     * commercial précis.
     *
     * @return array{items: Prospect[], total: int}
     */
    public function mesNouveaux(int $page = 1): array
    {
        return $this->prospectRepository->findNouveaux($this->scopeResolver->resolveOwn(), $page);
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

    /**
     * Recherche globale sur tous les prospects du scope courant, tous
     * cycles confondus.
     *
     * @return array{items: Prospect[], total: int}
     */
    public function search(?string $q, int $page = 1): array
    {
        return $this->prospectRepository->search($this->resolveScope(), $q, [], $page);
    }

    public function totalCount(): int
    {
        return $this->prospectRepository->countByScope($this->resolveScope());
    }

    private function resolveScope(): ProspectScope
    {
        return $this->scopeResolver->resolve();
    }
}
