<?php

namespace App\Entity;

enum ContratStatut: string
{
    case BROUILLON = 'brouillon';
    case VALIDE = 'valide';
    case RESILIE = 'resilie';
    case SUSPENDU = 'suspendu';

    public function label(): string
    {
        return match ($this) {
            self::BROUILLON => 'Brouillon',
            self::VALIDE => 'Validé',
            self::RESILIE => 'Résilié',
            self::SUSPENDU => 'Suspendu',
        };
    }
}
