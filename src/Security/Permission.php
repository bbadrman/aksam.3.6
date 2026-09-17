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
    case MES_NOUVEAUX_PROSPECTS = 'PERM_MES_NOUVEAUX_PROSPECTS';
    case PROSPECTS_NON_TRAITES = 'PERM_PROSPECTS_NON_TRAITES';
    case RELANCES_DU_JOUR = 'PERM_RELANCES_DU_JOUR';
    case RELANCES_A_VENIR = 'PERM_RELANCES_A_VENIR';
    case INJOIGNABLES = 'PERM_INJOIGNABLES';
    case RELANCES_NON_TRAITEES = 'PERM_RELANCES_NON_TRAITEES';
    case RECHERCHE_PROSPECTS = 'PERM_RECHERCHE_PROSPECTS';
    case SAV_OUVERTS = 'PERM_SAV_OUVERTS';

    public function label(): string
    {
        return match ($this) {
            self::CREER_PROSPECT => 'Créer un nouveau prospect',
            self::EXPORT_DONNEES => 'Exporter les données',
            self::SUPPRIMER_CONTRAT => 'Supprimer un contrat',
            self::VOIR_STATISTIQUES => 'Voir le panneau statistiques',
            //Traitement
            self::NOUVEAU_PROSPECT => 'Afficher les nouveaux prospects',
            self::MES_NOUVEAUX_PROSPECTS => 'Afficher mes nouveaux prospects',
            self::PROSPECTS_NON_TRAITES => 'Afficher les prospects non traités',
            self::RELANCES_DU_JOUR => 'Afficher les relances du jour',
            self::RELANCES_A_VENIR => 'Afficher les relances à venir',
            self::INJOIGNABLES => 'Afficher les injoignables',
            self::RELANCES_NON_TRAITEES => 'Afficher les relances non traitées',
            self::RECHERCHE_PROSPECTS => 'Afficher la recherche prospects',
            self::SAV_OUVERTS => 'Afficher les SAV ouverts',
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
            self::CREER_PROSPECT, self::NOUVEAU_PROSPECT, self::MES_NOUVEAUX_PROSPECTS, self::PROSPECTS_NON_TRAITES,
            self::RELANCES_DU_JOUR, self::RELANCES_A_VENIR, self::INJOIGNABLES,
            self::RELANCES_NON_TRAITEES, self::RECHERCHE_PROSPECTS, self::SAV_OUVERTS => true,
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
