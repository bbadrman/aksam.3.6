<?php

namespace App\Repository;

use App\Entity\Prospect;
use App\Entity\RelanceMotif;
use App\Security\ProspectScope;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Prospect>
 */
class ProspectRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Prospect::class);
    }

    /**
     * Liste paginée avec filtres, JOIN FETCH ciblé sur team/commercial
     * (jamais EAGER par défaut sur l'entité — cf. leçon de l'ancien projet).
     *
     * @param array{statut?: string, productId?: int, teamId?: int, commercialId?: int} $filters
     * @return array{items: Prospect[], total: int}
     */
    public function search(array $filters, int $page = 1, int $limit = 20): array
    {
        $qb = $this->createSearchQueryBuilder($filters)
            ->addSelect('team', 'commercial')
            ->leftJoin('p.team', 'team')
            ->leftJoin('p.commercial', 'commercial')
            ->orderBy('p.createdAt', 'DESC');

        return $this->paginate($qb, $page, $limit);
    }

    /**
     * Cycle "Nouveaux prospects" : jamais affectés à une équipe ni un
     * commercial, jamais traités.
     *
     * @return array{items: Prospect[], total: int}
     */
    public function findNouveaux(ProspectScope $scope, int $page = 1, int $limit = 20): array
    {
        $qb = $this->baseQueryBuilder($scope)
            ->andWhere('p.team IS NULL')
            ->andWhere('p.commercial IS NULL')
            ->andWhere('p.relance IS NULL')
            ->orderBy('p.createdAt', 'DESC');

        return $this->paginate($qb, $page, $limit);
    }

    /**
     * Cycle "Non traités" : affectés à une équipe depuis plus de 24h mais
     * jamais encore relancés.
     *
     * @return array{items: Prospect[], total: int}
     */
    public function findNonTraites(ProspectScope $scope, \DateTimeImmutable $avant, int $page = 1, int $limit = 20): array
    {
        $qb = $this->baseQueryBuilder($scope)
            ->andWhere('p.team IS NOT NULL')
            ->andWhere('p.relance IS NULL')
            ->andWhere('p.affectAt IS NOT NULL')
            ->andWhere('p.affectAt <= :avant')
            ->setParameter('avant', $avant)
            ->orderBy('p.affectAt', 'ASC');

        return $this->paginate($qb, $page, $limit);
    }

    /**
     * Cycle "Relances du jour" : une relance est planifiée aujourd'hui,
     * fiche pas encore clôturée.
     *
     * @return array{items: Prospect[], total: int}
     */
    public function findRelancesDuJour(ProspectScope $scope, \DateTimeImmutable $debutJour, \DateTimeImmutable $finJour, int $page = 1, int $limit = 20): array
    {
        $qb = $this->baseQueryBuilder($scope)
            ->andWhere('p.relanceAt BETWEEN :debut AND :fin')
            ->andWhere($this->excludeCloturesExpr())
            ->setParameter('debut', $debutJour)
            ->setParameter('fin', $finJour)
            ->setParameter('clotures', RelanceMotif::valeursCloturees())
            ->orderBy('p.relanceAt', 'ASC');

        return $this->paginate($qb, $page, $limit);
    }

    /**
     * Cycle "Relances à venir" : planifiées après aujourd'hui, fiche pas
     * clôturée.
     *
     * @return array{items: Prospect[], total: int}
     */
    public function findRelancesAVenir(ProspectScope $scope, \DateTimeImmutable $apres, int $page = 1, int $limit = 20): array
    {
        $qb = $this->baseQueryBuilder($scope)
            ->andWhere('p.relanceAt > :apres')
            ->andWhere($this->excludeCloturesExpr())
            ->setParameter('apres', $apres)
            ->setParameter('clotures', RelanceMotif::valeursCloturees())
            ->orderBy('p.relanceAt', 'ASC');

        return $this->paginate($qb, $page, $limit);
    }

    /**
     * Cycle "Relances non traitées" : la date de relance planifiée est
     * dépassée sans qu'un nouvel appel n'ait été enregistré, fiche pas
     * clôturée.
     *
     * @return array{items: Prospect[], total: int}
     */
    public function findRelancesNonTraitees(ProspectScope $scope, \DateTimeImmutable $avant, int $page = 1, int $limit = 20): array
    {
        $qb = $this->baseQueryBuilder($scope)
            ->andWhere('p.relanceAt IS NOT NULL')
            ->andWhere('p.relanceAt <= :avant')
            ->andWhere($this->excludeCloturesExpr())
            ->setParameter('avant', $avant)
            ->setParameter('clotures', RelanceMotif::valeursCloturees())
            ->orderBy('p.relanceAt', 'ASC');

        return $this->paginate($qb, $page, $limit);
    }

    /**
     * Cycle "Injoignables" : motif "Toujours injoignable" (distinct du
     * premier échec "Injoignable", qui reste dans le circuit normal).
     *
     * @return array{items: Prospect[], total: int}
     */
    public function findInjoignables(ProspectScope $scope, int $page = 1, int $limit = 20): array
    {
        $qb = $this->baseQueryBuilder($scope)
            ->andWhere('p.relance = :motif')
            ->setParameter('motif', RelanceMotif::TOUJOURS_INJOIGNABLE)
            ->orderBy('p.relanceAt', 'DESC');

        return $this->paginate($qb, $page, $limit);
    }

    private function excludeCloturesExpr(): string
    {
        return '(p.relance IS NULL OR p.relance NOT IN (:clotures))';
    }

    private function baseQueryBuilder(ProspectScope $scope): QueryBuilder
    {
        $qb = $this->createQueryBuilder('p')
            ->addSelect('team', 'commercial')
            ->leftJoin('p.team', 'team')
            ->leftJoin('p.commercial', 'commercial');

        return $this->applyScope($qb, $scope);
    }

    private function applyScope(QueryBuilder $qb, ProspectScope $scope): QueryBuilder
    {
        if ($scope->unrestricted) {
            return $qb;
        }

        if ($scope->teamIds !== null) {
            return $qb->andWhere('p.team IN (:teamIds)')->setParameter('teamIds', $scope->teamIds);
        }

        return $qb->andWhere('p.commercial = :commercial')->setParameter('commercial', $scope->commercial);
    }

    /**
     * @return array{items: Prospect[], total: int}
     */
    private function paginate(QueryBuilder $qb, int $page, int $limit): array
    {
        $qb->setFirstResult(($page - 1) * $limit)->setMaxResults($limit);
        $paginator = new Paginator($qb, fetchJoinCollection: false);

        return [
            'items' => iterator_to_array($paginator->getIterator()),
            'total' => count($paginator),
        ];
    }

    /**
     * @param array{statut?: string, productId?: int, teamId?: int, commercialId?: int} $filters
     */
    private function createSearchQueryBuilder(array $filters): QueryBuilder
    {
        $qb = $this->createQueryBuilder('p');

        if (!empty($filters['statut'])) {
            $qb->andWhere('p.statut = :statut')->setParameter('statut', $filters['statut']);
        }

        if (!empty($filters['productId'])) {
            $qb->andWhere('p.product = :productId')->setParameter('productId', $filters['productId']);
        }

        if (!empty($filters['teamId'])) {
            $qb->andWhere('p.team = :teamId')->setParameter('teamId', $filters['teamId']);
        }

        if (!empty($filters['commercialId'])) {
            $qb->andWhere('p.commercial = :commercialId')->setParameter('commercialId', $filters['commercialId']);
        }

        return $qb;
    }
}
