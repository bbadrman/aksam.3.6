<?php

namespace App\Repository;

use App\Entity\Product;
use App\Entity\Team;
use App\Entity\TeamProductLimit;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<TeamProductLimit>
 */
class TeamProductLimitRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, TeamProductLimit::class);
    }

    public function findByTeamAndProduct(Team $team, Product $product): ?TeamProductLimit
    {
        return $this->findOneBy(['team' => $team, 'product' => $product]);
    }

    /**
     * Map indexée [team_id][product_id] => TeamProductLimit, pour l'affichage
     * en grille sur l'écran de configuration des limites.
     *
     * @return array<int, array<int, TeamProductLimit>>
     */
    public function findAllAsGrid(): array
    {
        $grid = [];
        foreach ($this->findAll() as $limit) {
            $grid[$limit->getTeam()->getId()][$limit->getProduct()->getId()] = $limit;
        }

        return $grid;
    }
}
