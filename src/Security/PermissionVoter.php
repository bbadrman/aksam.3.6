<?php

namespace App\Security;

use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Vote sur les permissions applicatives fines (Permission::*). Chaque
 * permission a une valeur par défaut (Permission::defaultGranted(),
 * généralement accordée), mais reste explicitement retirable par
 * utilisateur — y compris pour un admin, via User::hasPermission(). Ne
 * pas confondre avec les rôles Symfony (ROLE_ADMIN) qui contrôlent l'accès
 * aux routes et restent, eux, absolus.
 */
class PermissionVoter extends Voter
{
    protected function supports(string $attribute, mixed $subject): bool
    {
        return null !== Permission::tryFrom($attribute);
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        //si tu veut n applique pas les permissions pour les admins tu peux décommenter le code ci dessous

        //  if (\in_array('ROLE_ADMIN', $user->getRoles(), true)) {
        //     return true;
        // }


        return $user->hasPermission(Permission::from($attribute));
    }
}
