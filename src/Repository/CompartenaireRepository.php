<?php

namespace App\Repository;

use App\Entity\Compartenaire;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Compartenaire>
 */
class CompartenaireRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Compartenaire::class);
    }

    public function findOneByApiToken(string $token): ?Compartenaire
    {
        return $this->createQueryBuilder('c')
            ->andWhere('c.apiToken = :token')
            ->andWhere('c.apiActif = true')
            ->setParameter('token', $token)
            ->getQuery()
            ->getOneOrNullResult();
    }
}
