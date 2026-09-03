<?php

namespace App\Repository;

use App\Entity\ContratVehiculeDetails;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ContratVehiculeDetails>
 */
class ContratVehiculeDetailsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ContratVehiculeDetails::class);
    }
}
