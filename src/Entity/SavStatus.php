<?php

namespace App\Entity;

enum SavStatus: string
{
    case OUVERT = 'ouvert';
    case TRAITE = 'traite';
    case CLOTURE = 'cloture';

    public function label(): string
    {
        return match ($this) {
            self::OUVERT => 'Ouvert',
            self::TRAITE => 'Traité',
            self::CLOTURE => 'Clôturé',
        };
    }
}
