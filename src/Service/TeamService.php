<?php

namespace App\Service;

use App\Entity\Team;
use Doctrine\ORM\EntityManagerInterface;

class TeamService
{
    public function __construct(private readonly EntityManagerInterface $em)
    {
    }

    public function create(Team $team): Team
    {
        $this->em->persist($team);
        $this->em->flush();

        return $team;
    }

    public function update(Team $team): Team
    {
        $this->em->flush();

        return $team;
    }
}
