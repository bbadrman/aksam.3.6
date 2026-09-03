<?php

namespace App\Repository;

use App\Entity\Prospect;
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

        $qb->setFirstResult(($page - 1) * $limit)->setMaxResults($limit);

        $paginator = new Paginator($qb, fetchJoinCollection: false);

        return [
            'items' => iterator_to_array($paginator->getIterator()),
            'total' => count($paginator),
        ];
    }

    /**
     * Prospects à relancer aujourd'hui ou en retard, non convertis/perdus.
     *
     * @return Prospect[]
     */
    public function findARelancer(\DateTimeImmutable $before): array
    {
        return $this->createQueryBuilder('p')
            ->andWhere('p.relanceAt IS NOT NULL')
            ->andWhere('p.relanceAt <= :before')
            ->andWhere('p.statut NOT IN (:statutsExclus)')
            ->setParameter('before', $before)
            ->setParameter('statutsExclus', [Prospect::STATUT_CONVERTI, Prospect::STATUT_PERDU])
            ->orderBy('p.relanceAt', 'ASC')
            ->getQuery()
            ->getResult();
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
