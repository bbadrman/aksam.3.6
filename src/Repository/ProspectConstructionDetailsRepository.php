<?php

namespace App\Repository;

use App\Entity\ProspectConstructionDetails;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProspectConstructionDetails>
 */
class ProspectConstructionDetailsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProspectConstructionDetails::class);
    }
}
