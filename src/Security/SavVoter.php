<?php

namespace App\Security;

use App\Entity\Sav;
use App\Entity\User;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;

/**
 * Qui peut clôturer un SAV : un admin, ou le gestionnaire assigné.
 */
class SavVoter extends Voter
{
    public const CLOTURER = 'SAV_CLOTURER';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return self::CLOTURER === $attribute && $subject instanceof Sav;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool
    {
        $user = $token->getUser();
        if (!$user instanceof User) {
            return false;
        }

        if (\in_array('ROLE_ADMIN', $user->getRoles(), true)) {
            return true;
        }

        /** @var Sav $sav */
        $sav = $subject;

        return $sav->getGestionnaire() !== null && $sav->getGestionnaire()->getId() === $user->getId();
    }
}
