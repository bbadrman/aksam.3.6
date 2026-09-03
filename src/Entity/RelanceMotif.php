<?php

namespace App\Entity;

/**
 * Motif de l'issue d'un appel/relance. Codes repris de l'ancienne application
 * (templates/prospect/show.html.twig) pour rester compatible avec une
 * éventuelle reprise de données historiques.
 */
enum RelanceMotif: int
{
    case RENDEZ_VOUS = 1;
    case INJOIGNABLE = 2;
    case ATTENTE_DOC = 3;
    case TARIFICATION = 4;
    case PRISE_DE_DECISION = 5;
    case FAUX_FICHE = 6;
    case DOUBLON = 7;
    case PASSAGE_CONCURRENT = 8;
    case PASSAGE_CLIENT = 9;
    case DEJA_SOUSCRIT = 10;
    case TOUJOURS_INJOIGNABLE = 11;
    case TEST = 13;
    case DOUBLON_PAYE = 14;

    public function label(): string
    {
        return match ($this) {
            self::RENDEZ_VOUS => 'Rendez-vous',
            self::INJOIGNABLE => 'Injoignable',
            self::ATTENTE_DOC => 'Attente DOC',
            self::TARIFICATION => 'Tarification',
            self::PRISE_DE_DECISION => 'Prise de décision',
            self::FAUX_FICHE => 'Faux fiche',
            self::DOUBLON => 'Doublon',
            self::PASSAGE_CONCURRENT => 'Passage concurrent',
            self::PASSAGE_CLIENT => 'Passage client',
            self::DEJA_SOUSCRIT => 'Déjà souscrit',
            self::TOUJOURS_INJOIGNABLE => 'Toujours injoignable',
            self::TEST => 'Test',
            self::DOUBLON_PAYE => 'Doublon payé',
        };
    }

    /**
     * Motifs qui clôturent définitivement la fiche : elle ne doit plus
     * réapparaître dans les files de relance (jour, à venir, non traitées).
     */
    public function isCloture(): bool
    {
        return match ($this) {
            self::FAUX_FICHE,
            self::DOUBLON,
            self::PASSAGE_CONCURRENT,
            self::PASSAGE_CLIENT,
            self::DEJA_SOUSCRIT,
            self::TOUJOURS_INJOIGNABLE,
            self::TEST,
            self::DOUBLON_PAYE => true,
            default => false,
        };
    }

    /**
     * @return int[] Valeurs des motifs de clôture, pour les requêtes DQL "NOT IN".
     */
    public static function valeursCloturees(): array
    {
        return array_map(
            fn (self $motif) => $motif->value,
            array_filter(self::cases(), fn (self $motif) => $motif->isCloture())
        );
    }
}
