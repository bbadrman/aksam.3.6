<?php

namespace App\Repository;

use App\Entity\ProspectVehiculeDetails;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ProspectVehiculeDetails>
 */
class ProspectVehiculeDetailsRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ProspectVehiculeDetails::class);
    }
}
