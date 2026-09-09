# AksamCrm — contexte projet

Reconstruction v2 de l'application de gestion assurance Aksam (l'ancienne version vit dans
`/home/hp/Server/aksamv2test`), repartie de zéro pour corriger les problèmes structurels
identifiés dans l'ancienne base : entités god-object, contrôleurs gras, duplication de code,
~50 rôles Symfony granulaires, absence d'index.

Plan complet (contexte, décisions, étapes détaillées) : `/home/hp/.claude/plans/valiant-floating-dewdrop.md`.

## Stack

- Symfony 7.4, PHP 8.3, MariaDB 11.8 (DDEV — `ddev start`, projet déjà démarré la plupart du temps)
- Twig + Symfony UX (Stimulus/Turbo), Tailwind v4 (`symfonycasts/tailwind-bundle`) — design system
  repris à l'identique du projet `Mes Projects/gestionStock` (sidebar, header, mode sombre, tokens
  OKLCH). Fichier clé : `assets/styles/admin.css`. Après toute modification CSS :
  `ddev exec php bin/console tailwind:build`.
- Authentification par `username` (pas email), voir `security.yaml`.
- DoctrineFixturesBundle + `dama/doctrine-test-bundle` (isolation transactionnelle des tests —
  indispensable, sans ça les tests se polluent entre eux).

## Commandes courantes

```bash
cd /home/hp/Server/Aksam/AksamCrm
ddev exec php bin/phpunit                          # suite de tests (53 tests actuellement)
ddev exec php bin/console make:migration --no-interaction
ddev exec php bin/console doctrine:migrations:migrate --no-interaction
ddev exec php bin/console --env=test doctrine:migrations:migrate --no-interaction  # à répéter en test !
ddev exec php bin/console tailwind:build           # recompiler le CSS après édition
```

URL dev : https://aksamcrm.ddev.site — compte de test : `admin` / `TestPass123!`

## Architecture (règles à respecter)

- **Controller → Service → Repository/Entity**, jamais `EntityManagerInterface` injecté dans un
  Controller pour de la logique métier.
- **Pas de duplication d'identité** : `Contrat`/`Client` ne stockent jamais nom/prénom en double —
  toujours `$contrat->getClient()->getNom()`.
- **Extensions par produit (option C)**, appliquée deux fois : `Prospect` et `Contrat` ont chacun
  un socle commun + une entité d'extension `OneToOne` par produit (`ProspectVehiculeDetails`,
  `ContratVehiculeDetails`, etc.) plutôt qu'une entité par produit — évite de dupliquer la logique
  de traitement transverse (relance, recherche, stats) à chaque nouveau produit.
- **Duplication d'entité** (ex: `Contrat::duplicate()`) : toujours une méthode sur l'entité, jamais
  une liste de `set...()` recopiée dans un contrôleur (c'était le bug historique de l'ancien projet
  — il partageait même le même `Payment` entre deux contrats).
- **DTO + FormType pour les créations/formulaires** (`ContratFormDTO`+`ContratType`,
  `ProspectFormDTO`+`ProspectType`) plutôt que de binder directement sur l'entité.
- **Scope/rôles** : 4-5 rôles Symfony simples (`ROLE_ADMIN`, `ROLE_USER`, ...) + `ProspectScope`/
  `ProspectScopeResolver` pour les listes filtrées par équipe/commercial, + `Voter` dédié pour les
  permissions ponctuelles (`SavVoter::CLOTURER`) — pas les ~50 `ROLE_X` granulaires de l'ancien
  projet.
- **Index dès la création** sur les colonnes filtrées/triées récurrentes (`statut`, `relance_at`,
  `affect_at`, `created_at`) et relations `LAZY` par défaut (jamais `EAGER` comme dans l'ancien
  `Prospect.php`, identifié comme cause principale de lenteur).
- **Secrets d'intégration** (tokens API partenaires) : jamais en dur dans le code, toujours via
  `.env.local` (non commité) + paramètres Symfony injectés (`#[Autowire(param: ...)]`).

## Domaine (dans l'ordre de dépendance)

1. **`User`** — auth par `username`, `ManyToMany` vers `Team` (un commercial/chef peut appartenir à
   plusieurs équipes).
2. **Référentiels** : `Team`, `Product` (avec `code` technique stable : `vehicule`/`prevoyance`/
   `construction`, utilisé pour router les extensions), `Compartenaire` (partenaire API : `nom`,
   `apiToken`, `apiActif`, `product` — chaque site partenaire est dédié à un seul produit).
3. **`Prospect`** — socle commun (nom, prénom, phone, email, statut, product, team, commercial,
   dates) + extensions `ProspectVehiculeDetails`/`ProspectPrevoyanceDetails`/
   `ProspectConstructionDetails`. Relance : `RelanceMotif` (enum, codes repris de l'ancien projet
   pour compat future) + `ProspectRelanceHistory`. Cycles de traitement gérés par
   `ProspectTraitementService` + `ProspectScope`/`ProspectScopeResolver` (`/traitement/...`,
   6 écrans : nouveaux, non-traités, relances du jour/à venir/non-traitées, injoignables).
   Ingestion API : `POST /api/prospects` (header `X-API-TOKEN`), voir `ProspectApiController` +
   `ProspectService::createFromApiPayload()`. Création manuelle : `/traitement/nouveau` via
   `ProspectService::createManually()`.
4. **`Client`** — identité (nom/prénom ou raisonSociale), peut naître d'une conversion `Prospect`
   → `Client` (`ClientService::convertFromProspect()`, marque le prospect `converti`).
5. **`Contrat`** — champs communs + `ContratVehiculeDetails` (même pattern que Prospect), `Payment`/
   `Document` (`OneToOne`, singulier — l'ancien projet les nommait au pluriel par erreur), `Frais`
   (`OneToMany`). `ContratService` : `create()`, `duplicate()`, `valider()` (statut
   brouillon→validé, numéro de police généré).
6. **`Sav`** — rattaché à `Contrat`, statut typé (`SavStatus`: ouvert/traité/clôturé),
   `SavRelanceHistory`. `SavService` : `ouvrir()`, `ajouterRelance()`, `cloturer()` (transitions
   protégées : pas de relance/clôture sur un SAV déjà clôturé). `SavVoter::CLOTURER` : admin ou
   gestionnaire assigné uniquement.

## Workflow Git suivi jusqu'ici

Une branche par étape (`etape-N-xxx`), mergée dans `main` avec `--no-ff` une fois validée
(tests + vérification manuelle en navigateur réel via Playwright), puis poussée sur `origin/main`
(dépôt `bbadrman/aksam.3.6`, remote HTTPS — **le push doit être fait manuellement par
l'utilisateur**, pas d'auth Git configurée dans l'environnement agent).

## Ce qui reste à faire (pas encore dans le plan initial)

- Payment/Document : entités posées mais aucune action de contrôleur pour les attacher/éditer
  (paiement, upload de document) — à faire quand le besoin se précisera.
- Intégration de sites partenaires supplémentaires : suivre le pattern déjà validé avec
  `assurance-vehicule-pro` / `site-test-post` (créer un `Compartenaire` avec token + produit,
  `.env.local` côté site avec `AKSAM_API_URL`/`AKSAM_API_TOKEN`, jamais en dur dans le code).
- Rôles fins : seul `SavVoter` existe pour l'instant ; envisager `ContratVoter`/`ProspectVoter` si
  un besoin de permission ponctuelle apparaît.
