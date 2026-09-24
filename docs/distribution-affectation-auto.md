# Distribution automatique des prospects — Plan d'implémentation

## Contexte

L'application reçoit des prospects depuis plusieurs sites web via l'API Platform (`POST /api/prospects`).  
Chaque prospect arrive déjà avec un **produit** identifié (ex. `'product' => '/api/products/1'`).

**Relations existantes clés :**
- `Prospect` → `comrcl` (User), `team` (Team), `product` (Product), `AffectAt`
- `User` ↔ `Product` (ManyToMany) : un commercial est assigné à un ou plusieurs produits
- `User` ↔ `Team` (ManyToMany) : un commercial appartient à une ou plusieurs équipes
- `Team` → `prospects` (OneToMany)

**Objectif :** Quand un prospect arrive via l'API avec un `product` défini, l'affecter automatiquement et équitablement (round-robin) à une équipe et à un commercial qui gèrent ce produit.

---

## Estimation

| Tâche | Complexité | Durée estimée |
|---|---|---|
| Service `ProspectDistributionService` | Moyenne | 2h |
| State Processor API Platform | Faible | 1h |
| Repository — méthode comptage par produit | Faible | 30 min |
| Tests manuels / vérification | Faible | 30 min |
| **Total** | | **~4h** |

---

## Algorithme de distribution (Round-Robin par produit)

### Règle de sélection

Pour un prospect avec `product = X` :

1. **Récupérer les équipes éligibles** : toutes les équipes qui ont au moins un utilisateur actif (`status = 1`) assigné au produit `X`.
2. **Choisir l'équipe** : parmi les équipes éligibles, prendre celle qui a le **moins de prospects actifs** (non transformés en client) pour le produit `X`.  
   > En cas d'égalité → prendre la première par ordre alphabétique du nom.
3. **Choisir le commercial** : parmi les utilisateurs actifs de cette équipe assignés au produit `X`, prendre celui qui a le **moins de prospects actifs** pour ce produit.  
   > En cas d'égalité → prendre le premier par ordre alphabétique du `username`.
4. **Affecter** : `prospect.team = équipe choisie`, `prospect.comrcl = commercial choisi`, `prospect.AffectAt = maintenant`.

### Cas limites

| Situation | Comportement |
|---|---|
| Aucune équipe/commercial pour ce produit | Prospect sauvegardé sans affectation (`comrcl = null`, `team = null`) |
| Produit non défini dans la requête | Idem — pas d'affectation automatique |
| Commercial inactif (`status = 0`) | Exclu de la sélection |

---

## Solution technique — Fichiers à créer/modifier

### 1. Nouveau : `src/Service/ProspectDistributionService.php`

Service Symfony injecté via autowiring. Contient toute la logique de sélection.

```php
// Méthodes principales :
public function distribute(Prospect $prospect): void
    // Lit prospect->getProduct()
    // Appelle selectTeam() puis selectCommercial()
    // Appelle prospect->setTeam(), setComrcl(), setAffectAt()

private function selectTeam(Product $product): ?Team
    // Requête DQL : équipes avec users actifs sur ce produit
    // Trié par COUNT(prospects actifs pour ce produit) ASC, team.name ASC

private function selectCommercial(Team $team, Product $product): ?User
    // Requête DQL : users actifs dans $team ET sur $product
    // Trié par COUNT(prospects actifs pour ce produit) ASC, user.username ASC
```

### 2. Nouveau : `src/State/ProspectStateProcessor.php`

State Processor API Platform — se déclenche après la création d'un `Prospect` via `POST /api/prospects`.

```php
namespace App\State;

use ApiPlatform\State\ProcessorInterface;
use App\Entity\Prospect;
use App\Service\ProspectDistributionService;

class ProspectStateProcessor implements ProcessorInterface
{
    public function __construct(
        private ProcessorInterface $innerProcessor,
        private ProspectDistributionService $distributionService
    ) {}

    public function process(mixed $data, Operation $operation, array $uriVariables = [], array $context = []): mixed
    {
        $result = $this->innerProcessor->process($data, $operation, $uriVariables, $context);

        if ($data instanceof Prospect && $data->getProduct() !== null) {
            $this->distributionService->distribute($data);
            // flush déjà fait par innerProcessor, donc re-flush nécessaire
        }

        return $result;
    }
}
```

**Enregistrer dans `config/services.yaml` :**
```yaml
App\State\ProspectStateProcessor:
    bind:
        $innerProcessor: '@api_platform.doctrine.orm.state.persist_processor'
```

### 3. Modifier : `src/Entity/Prospect.php`

Ajouter l'attribut `processor` sur l'opération `Post` de l'`ApiResource` :

```php
#[ApiResource(
    operations: [
        new Post(processor: ProspectStateProcessor::class),
        // ... autres opérations existantes
    ]
)]
```

### 4. Nouveau : méthodes dans `src/Repository/ProspectRepository.php`

```php
// Compter les prospects actifs d'un user pour un produit donné
public function countActiveByUserAndProduct(User $user, Product $product): int

// Compter les prospects actifs d'une team pour un produit donné  
public function countActiveByTeamAndProduct(Team $team, Product $product): int
```

> "Actif" = `relance NOT IN ('9')` (pas encore converti en client), ou adapter selon la logique métier existante.

---

## Étapes à suivre

### Étape 1 — Méthodes Repository (30 min)
- Ouvrir `src/Repository/ProspectRepository.php`
- Ajouter `countActiveByUserAndProduct(User $user, Product $product): int`
- Ajouter `countActiveByTeamAndProduct(Team $team, Product $product): int`
- Ces méthodes servent de base au service de distribution

### Étape 2 — Service de distribution (2h)
- Créer `src/Service/ProspectDistributionService.php`
- Implémenter `distribute(Prospect $prospect): void`
- Implémenter `selectTeam(Product $product): ?Team` avec DQL sur `user_product` join
- Implémenter `selectCommercial(Team $team, Product $product): ?User`
- Injecter `EntityManagerInterface` et `ProspectRepository`

### Étape 3 — State Processor API Platform (1h)
- Créer `src/State/ProspectStateProcessor.php`
- Déclarer le service dans `config/services.yaml` avec le `$innerProcessor` décorateur
- Modifier l'annotation `#[ApiResource]` dans `src/Entity/Prospect.php` pour pointer vers le processor

### Étape 4 — Test et validation (30 min)
- Simuler une requête `POST /api/prospects` avec `product: "/api/products/1"`
- Vérifier que `comrcl` et `team` sont bien renseignés après création
- Vérifier la répartition égale en créant plusieurs prospects successifs
- Vérifier le cas sans produit → pas d'affectation (pas d'erreur)

---

## Schéma de flux

```
Site externe
    │
    │ POST /api/prospects
    │ { product: "/api/products/1", nom, prenom, phone, ... }
    ▼
API Platform (ProspectStateProcessor)
    │
    │ innerProcessor.process() → Prospect persisté en base
    │
    ▼
ProspectDistributionService.distribute(prospect)
    │
    ├─ product = null ? → sortir sans affecter
    │
    ├─ selectTeam(product)
    │       └─ Teams avec users actifs sur ce product
    │          triées par nb prospects actifs ASC
    │          → team_choisie
    │
    ├─ selectCommercial(team_choisie, product)
    │       └─ Users actifs dans team ET sur product
    │          triés par nb prospects actifs ASC
    │          → commercial_choisi
    │
    └─ prospect.setTeam(team_choisie)
       prospect.setComrcl(commercial_choisi)
       prospect.setAffectAt(now)
       entityManager.flush()
```

---

## Notes importantes

- Le champ `activites` dans les prospects correspond au type d'activité (`"1"` = TPM, `"2"` = VTC, etc.) — distinct du `product`. La distribution se base sur **product**, pas sur `activites`.
- La méthode `countProspectsByUserAndActivityToday()` existante dans `ProspectRepository` (ligne 1324) peut servir d'inspiration pour les nouvelles méthodes de comptage.
- L'affectation manuelle existante (`app_affect_edit`) reste inchangée — elle permet de réaffecter manuellement après coup.
- Si un commercial est en congé ou inactif, le mettre à `status = 0` suffit pour l'exclure de la distribution automatique.
