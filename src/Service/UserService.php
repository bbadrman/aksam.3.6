<?php

namespace App\Service;

use App\Dto\UserFormDTO;
use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

class UserService
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly UserPasswordHasherInterface $passwordHasher,
    ) {
    }

    public function create(UserFormDTO $dto): User
    {
        $user = new User();
        $this->applyDto($user, $dto);

        $this->em->persist($user);
        $this->em->flush();

        return $user;
    }

    public function update(User $user, UserFormDTO $dto): User
    {
        $this->applyDto($user, $dto);
        $this->em->flush();

        return $user;
    }

    private function applyDto(User $user, UserFormDTO $dto): void
    {
        $user->setUsername($dto->username);
        $user->setNom($dto->nom);
        $user->setPrenom($dto->prenom);
        $user->setActif($dto->actif);
        $user->setRoles($dto->roles);

        foreach ($user->getTeams()->toArray() as $team) {
            if (!$dto->teams->contains($team)) {
                $user->removeTeam($team);
            }
        }
        foreach ($dto->teams as $team) {
            $user->addTeam($team);
        }

        if ($dto->plainPassword) {
            $user->setPassword($this->passwordHasher->hashPassword($user, $dto->plainPassword));
        }
    }
}
