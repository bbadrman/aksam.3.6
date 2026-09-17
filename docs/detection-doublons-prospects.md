# Détection des doublons email/téléphone

## Contexte / tâche

L'ancienne version (`aksamv2test`) exposait une méthode
`TraitementController::detectDuplicatesOptimized()` : pour chaque liste de
prospects affichée, deux requêtes groupées (une pour les emails, une pour
les téléphones) identifiaient les doublons en une seule fois plutôt qu'une
requête par prospect. AksamCrm reprend le même principe, en le sortant du
contrôleur pour respecter la règle **Controller → Service → Repository**
du projet.

## Principe

Sur chaque écran qui affiche une liste de prospects (paniers de
traitement + recherche), on veut repérer visuellement les prospects dont
l'email ou le téléphone existe déjà ailleurs en base — signe possible
d'un doublon de saisie ou d'un prospect déjà traité sous une autre fiche.

La comparaison des téléphones est normalisée : `0611112224`,
`+33611112224` et `0033611112224` doivent être reconnus comme le même
numéro.

## Comment c'est implémenté

### 1. `ProspectRepository` (`src/Repository/ProspectRepository.php`)

Deux requêtes groupées, chacune en un seul aller-retour SQL quel que soit
le nombre de prospects affichés :

```php
findDuplicateEmails(array $emails): array;        // GROUP BY email HAVING COUNT > 1
findPhonesMatchingVariants(array $phoneVariants): array; // WHERE phone IN (...)
```

`findDuplicateEmails` fait le regroupement directement en SQL (comparaison
stricte). `findPhonesMatchingVariants` se contente de récupérer les
numéros bruts correspondant à une variante d'écriture donnée — le
regroupement par forme normalisée est fait côté service, SQL ne sachant
pas normaliser un numéro de téléphone.

### 2. `ProspectDuplicateService` (`src/Service/ProspectDuplicateService.php`)

Porte toute la logique métier :

- `normalizePhone()` / `phoneVariants()` : convertissent un numéro vers sa
  forme locale `0XXXXXXXXX` et génèrent ses variantes d'écriture
  (`0X..`, `+33X..`, `0033X..`, `33X..`).
- `detect(iterable $prospects): array{emails: array<string,bool>, phones: array<string,bool>}` :
  collecte les emails et variantes de téléphones de la liste affichée,
  interroge le repository, puis regroupe les téléphones bruts par forme
  normalisée pour ne marquer "doublon" que ceux réellement partagés par au
  moins deux prospects.

C'est un service dédié plutôt qu'une méthode de `ProspectTraitementService` :
la détection de doublons est un affichage transverse à tous les cycles
(voir [hierarchie-panier-prospects.md](hierarchie-panier-prospects.md)),
pas une règle métier propre au traitement d'un cycle donné.

### 3. `ProspectController` (`src/Controller/ProspectController.php`)

Chaque action qui rend une liste (`renderCycle()` pour les 7 paniers de
traitement, `recherche()`) appelle `ProspectDuplicateService::detect()` sur
les prospects de la page courante et passe le résultat aux templates sous
la clé `duplicates`.

```php
'duplicates' => $this->duplicateService->detect($resultats['items']),
```

### 4. Templates (`prospect/cycle.html.twig`, `prospect/search.html.twig`)

Un badge "doublon" (ambre) s'affiche à côté du téléphone ou de l'email
quand la valeur est présente dans `duplicates.phones` / `duplicates.emails` :

```twig
{% if prospect.email and duplicates.emails[prospect.email] is defined %}
    <span ...>doublon</span>
{% endif %}
```

## Limites connues / évolutions possibles

- La détection porte uniquement sur les prospects de la page affichée
  (pagination) — un doublon avec un prospect d'une autre page n'est pas
  visible tant qu'on ne consulte pas cette page. C'était déjà le
  comportement de l'ancienne version.
- Aucune action de fusion/rapprochement n'est proposée pour l'instant, le
  badge est purement informatif.
