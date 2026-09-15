<?php

namespace App\Security;

/**
 * Permissions applicatives fines (afficher/masquer un bouton, un panneau...),
 * distinctes des rôles Symfony (ROLE_USER, ROLE_ADMIN) qui contrôlent l'accès
 * aux routes. Vérifiées via PermissionVoter et is_granted() en Twig/PHP.
 *
 * Ajouter une permission = ajouter un cas ici, elle apparaît automatiquement
 * dans le formulaire utilisateur.
 */
enum Permission: string
{
    case CREER_PROSPECT = 'PERM_CREER_PROSPECT';
    case EXPORT_DONNEES = 'PERM_EXPORT_DONNEES';
    case SUPPRIMER_CONTRAT = 'PERM_SUPPRIMER_CONTRAT';
    case VOIR_STATISTIQUES = 'PERM_VOIR_STATISTIQUES';
    //Traitement
    case NOUVEAU_PROSPECT = 'PERM_NOUVEAU_PROSPECT';

    public function label(): string
    {
        return match ($this) {
            self::CREER_PROSPECT => 'Créer un nouveau prospect',
            self::EXPORT_DONNEES => 'Exporter les données',
            self::SUPPRIMER_CONTRAT => 'Supprimer un contrat',
            self::VOIR_STATISTIQUES => 'Voir le panneau statistiques',
            //Traitement
            self::NOUVEAU_PROSPECT => 'Afficher les nouveaux prospects',

        };
    }

    /**
     * Valeur appliquée à un user qui n'a pas d'override explicite en base
     * (voir User::hasPermission()). Permet des permissions "accordées par
     * défaut, retirables" (ex: CREER_PROSPECT) plutôt que toutes en opt-in.
     */
    public function defaultGranted(): bool
    {
        return match ($this) {
            self::CREER_PROSPECT => true,
            self::EXPORT_DONNEES, self::SUPPRIMER_CONTRAT, self::VOIR_STATISTIQUES => false,
        };
    }

    /**
     * @return array<string, string> libellé => valeur, pour les ChoiceType
     */
    public static function choices(): array
    {
        $choices = [];
        foreach (self::cases() as $permission) {
            $choices[$permission->label()] = $permission->value;
        }

        return $choices;
    }
}
