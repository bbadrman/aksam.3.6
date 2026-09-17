<?php

namespace App\Service;

use App\Dto\UserFormDTO;
use App\Entity\User;
use App\Security\Permission;
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

    public function toggleActif(User $user): User
    {
        $user->setActif(!$user->isActif());
        $this->em->flush();

        return $user;
    }

    private function applyDto(User $user, UserFormDTO $dto): void
    {
        $user->setUsername($dto->username);
        $user->setNom($dto->nom);
        $user->setPrenom($dto->prenom);
        $user->setActif($dto->actif);
        $user->setFonction($dto->fonction);
        $user->setRoles($dto->roles);
        $user->setPermissions($this->buildPermissionOverrides($dto->permissions));

        foreach ($user->getTeams()->toArray() as $team) {
            if (!$dto->teams->contains($team)) {
                $user->removeTeam($team);
            }
        }
        foreach ($dto->teams as $team) {
            $user->addTeam($team);
        }

        foreach ($user->getProducts()->toArray() as $product) {
            if (!$dto->products->contains($product)) {
                $user->removeProduct($product);
            }
        }
        foreach ($dto->products as $product) {
            $user->addProduct($product);
        }

        if ($dto->plainPassword) {
            $user->setPassword($this->passwordHasher->hashPassword($user, $dto->plainPassword));
        }
    }

    /**
     * Ne garde que les permissions dont l'état coché diffère de leur valeur
     * par défaut, pour que le tableau stocké reste un simple diff (voir
     * User::hasPermission()).
     *
     * @param list<string> $checkedPermissions
     *
     * @return array<string, bool>
     */
    private function buildPermissionOverrides(array $checkedPermissions): array
    {
        $overrides = [];
        foreach (Permission::cases() as $permission) {
            $checked = \in_array($permission->value, $checkedPermissions, true);
            if ($checked !== $permission->defaultGranted()) {
                $overrides[$permission->value] = $checked;
            }
        }

        return $overrides;
    }
}
