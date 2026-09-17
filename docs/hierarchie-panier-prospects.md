# Hiérarchie des paniers de traitement des prospects

## Contexte / tâche

L'ancienne version (`aksamv2test`) dupliquait chaque requête de liste en
3 méthodes par rôle (ex: `findByAdminNewProsp`, `findByChefNewProsp`,
`findByCmrclNewProsp`) — une source de duplication et de bugs (une règle
métier changée dans une version et oubliée dans les deux autres).

Dans AksamCrm, la même hiérarchie à 3 niveaux (**admin → chef d'équipe →
commercial**) est appliquée via un objet `ProspectScope` unique, calculé
une seule fois par requête et injecté dans une méthode de repository
partagée. Un seul endroit à faire évoluer par cycle, pas trois.

## Les 3 niveaux

| Rôle | Ce qu'il voit | Ce qu'il peut affecter |
|---|---|---|
| **Admin** (`ROLE_ADMIN`) | Tous les prospects, toutes équipes confondues | À n'importe quelle équipe et/ou n'importe quel commercial |
| **Chef d'équipe** (`ROLE_CHEF_EQUIPE`) | Les prospects de son ou ses équipes (`User::$teams`) | Idem — vers un commercial de son équipe, ou vers lui-même |
| **Commercial** (aucun rôle particulier, `ROLE_USER` de base) | Uniquement les prospects qui lui sont attribués personnellement (`p.commercial = lui`) | N'a pas accès à l'affectation (bouton masqué + accès serveur refusé) |

## Comment c'est implémenté

### 1. `ProspectScope` (`src/Security/ProspectScope.php`)

Value object immuable à 3 formes, une par niveau :

```php
ProspectScope::all();                 // admin — pas de filtre
ProspectScope::forTeams($teamIds);    // chef — filtre p.team IN (...)
ProspectScope::forCommercial($user);  // commercial — filtre p.commercial = ...
```

### 2. `ProspectScopeResolver` (`src/Security/ProspectScopeResolver.php`)

Détermine automatiquement le bon scope à partir de l'utilisateur connecté :

```php
resolve(): ProspectScope
```
- `ROLE_ADMIN` → `ProspectScope::all()`
- `ROLE_CHEF_EQUIPE` + au moins une équipe → `ProspectScope::forTeams(...)`
- sinon → `ProspectScope::forCommercial($user)`

Une deuxième méthode existe pour un besoin particulier (voir panier "Mes
nouveaux prospects" ci-dessous) :

```php
resolveOwn(): ProspectScope   // toujours forCommercial($user), quel que soit le rôle
```

### 3. `ProspectRepository::applyScope()` (`src/Repository/ProspectRepository.php`)

Chaque méthode de panier (`findNouveaux`, `findNonTraites`,
`findRelancesDuJour`, etc.) construit sa requête puis appelle
`applyScope($qb, $scope)`, qui ajoute la clause `WHERE` adaptée. C'est le
remplacement direct des 3 méthodes dupliquées de l'ancien projet : **une
seule requête métier par cycle**, le filtrage par rôle est un aspect
orthogonal appliqué en une ligne.

### 4. `ProspectTraitementService` (`src/Service/ProspectTraitementService.php`)

Couche appelée par le contrôleur, une méthode par panier
(`nouveaux()`, `mesNouveaux()`, `nonTraites()`, `relancesDuJour()`,
`relancesAVenir()`, `relancesNonTraitees()`, `injoignables()`,
`search()`). Chacune résout le scope (`resolveScope()` ou
`resolveOwn()` pour le panier personnel) et appelle la méthode de
repository correspondante.

## Le panier "Nouveaux prospects" — cas particulier

C'est le panier le plus sensible car sa définition change selon le rôle :

- **Admin** : prospect jamais affecté du tout (`team` ET `commercial`
  nuls) — c'est lui qui distribue en premier.
- **Chef** : prospect affecté à son équipe (via `applyScope`), mais
  **pas encore attribué à un commercial précis** (`commercial IS NULL`).
  Dès qu'un commercial est choisi (par l'admin ou par le chef qui se
  l'attribue), le prospect **sort** de ce panier.
- **Commercial** : n'a pas de version "brute" de ce panier — un prospect
  qui lui est attribué directement est déjà dans son panier personnel
  ("Mes nouveaux prospects", voir plus bas), pas dans un panier d'équipe
  non réparti.

Voir `ProspectRepository::findNouveaux()` pour l'implémentation exacte
de cette bascule (`if ($scope->unrestricted) ... elseif ($scope->teamIds
!== null) ...`).

## Le panier "Mes nouveaux prospects"

Ajouté pour reproduire l'équivalent de la route `/newprospectchef` de
l'ancien projet : montre les prospects **attribués personnellement** à
l'utilisateur connecté (`ProspectScope::forCommercial($user)`, via
`resolveOwn()`), quel que soit son rôle. Utile en particulier pour un
chef d'équipe qui s'attribue un prospect à lui-même : celui-ci quitte le
panier d'équipe "Nouveaux prospects" et apparaît dans son panier
personnel.

- Route : `GET /traitement/mes-nouveaux` (`app_traitement_mes_nouveaux`)
- Permission : `PERM_MES_NOUVEAUX_PROSPECTS`
- Contrôleur : `ProspectController::mesNouveaux()`
- Service : `ProspectTraitementService::mesNouveaux()`

## Affectation d'un prospect (équipe / commercial)

L'affectation (`POST /traitement/{id}/affecter` et
`GET /traitement/api/equipes-commerciaux`, dans
`ProspectController`) est **réservée à l'admin et au chef d'équipe** :

```php
private function denyAccessUnlessGrantedAffectation(): void
{
    if (!$this->isGranted('ROLE_ADMIN') && !$this->isGranted('ROLE_CHEF_EQUIPE')) {
        throw $this->createAccessDeniedException(...);
    }
}
```

Le bouton "Affecter" est également masqué côté template
(`templates/prospect/_affect_modal.html.twig`) pour un commercial.

> ⚠️ **Limite connue** : la liste des équipes/commerciaux proposée dans
> la modale d'affectation n'est aujourd'hui pas restreinte aux équipes
> du chef connecté (`ProspectController::teamsCommerciaux()` renvoie
> toutes les équipes). Un chef peut donc voir apparaître dans le
> formulaire des équipes qui ne sont pas les siennes, même si son usage
> réel reste limité à ce qu'il gère. À corriger si besoin (filtrer
> `teamRepository` par `resolve()`/`$user->getTeams()` quand l'utilisateur
> n'est pas admin).

## Comment gérer les rôles/permissions d'un panier

Deux mécanismes séparés, à ne pas confondre :

1. **Rôles Symfony** (`ROLE_ADMIN`, `ROLE_CHEF_EQUIPE`, `ROLE_USER`) —
   assignés dans le formulaire utilisateur (`src/Form/UserType.php`,
   champ "Rôles"). Déterminent le `ProspectScope` (quels prospects sont
   visibles) et l'accès à l'affectation. Contrôle "dur", jamais
   contournable.

2. **Permissions applicatives fines** (`Permission::*`, ex:
   `PERM_NOUVEAU_PROSPECT`, `PERM_MES_NOUVEAUX_PROSPECTS`) — cases à
   cocher par utilisateur dans le même formulaire, section
   "Permissions". Contrôlent uniquement l'**affichage** d'un panier
   donné (menu + accès à la route), pas son contenu. Une permission a
   une valeur par défaut (`Permission::defaultGranted()`, généralement
   accordée) mais reste **retirable pour n'importe quel utilisateur, y
   compris un admin** (`PermissionVoter` ne fait plus d'exception pour
   `ROLE_ADMIN` — voir commentaire dans
   `src/Security/PermissionVoter.php` si ce comportement doit être
   réactivé un jour).

Pour ajouter un nouveau panier :
1. Ajouter la méthode de requête dans `ProspectRepository` en réutilisant
   `applyScope()`.
2. Exposer une méthode dans `ProspectTraitementService`.
3. Ajouter un cas dans `Permission` (label + `defaultGranted()`).
4. Ajouter la route + l'action dans `ProspectController`
   (`#[IsGranted(Permission::XXX->value)]`).
5. Ajouter l'entrée dans `templates/admin/_sidebar.html.twig` et, si
   pertinent, un compteur dans `TraitementCountsExtension`.
