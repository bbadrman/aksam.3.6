# Pré-client : fiche client et décision (valider / rejeter / annuler)

## Contexte / tâche

Quand un prospect passe client (`ClientService::convertFromProspect()`), il
arrive dans le panier **Clients**. On ne veut pas encore le traiter comme un
client définitif : il doit d'abord apparaître comme **pré-client**, et
quelqu'un doit prendre une décision sur sa fiche.

Il fallait donc :

1. ajouter une propriété `status` sur `Client`, avec trois choix (Valider = 1,
   Rejeter = 2, Annulé = 3) ;
2. afficher dans la liste Clients le libellé **Pré-client** tant qu'aucune
   décision n'a été prise ;
3. créer une page fiche (`show`) qui affiche toutes les données du client et
   propose en bas trois boutons : **Valider**, **Rejeter**, **Annulé**.

## Règles métier

| `status` | Libellé    | Sens                                   |
|----------|------------|----------------------------------------|
| `null`   | Pré-client | En attente de décision (valeur par défaut) |
| `1`      | Validé     | Client confirmé                        |
| `2`      | Rejeté     | Client refusé                          |
| `3`      | Annulé     | Dossier annulé                         |

- **Une seule décision possible** : une fois le statut fixé, il ne peut plus
  changer. Les boutons disparaissent de la fiche, et un nouvel envoi du
  formulaire est refusé avec un message flash (même principe que la clôture
  d'un SAV).
- La date de la décision est enregistrée dans `statusAt`.
- Une valeur autre que 1/2/3 est refusée par l'entité
  (`InvalidArgumentException`).

## Comment c'est implémenté

### 1. Entité `Client` (`src/Entity/Client.php`)

- Colonnes `status` (`SMALLINT NULL`, index `idx_client_status`) et
  `statusAt` (`DATETIME NULL`).
- Constantes `STATUS_VALIDE`, `STATUS_REJETE`, `STATUS_ANNULE` et
  `STATUS_CHOICES` (libellé => valeur), utilisées par le formulaire.
- `setStatus()` vérifie la valeur et renseigne `statusAt`.
- Méthodes d'affichage : `isPreClient()` et `getStatusLabel()`.

Migration : `migrations/Version20261001154937.php`.

### 2. Formulaire `ClientStatusType` (`src/Form/ClientStatusType.php`)

Il reprend le `ChoiceType` demandé (`expanded: true`, `multiple: false`,
classe `status-choice`). Le formulaire n'est pas lié à l'entité : la
transition passe par le service, selon la règle
Controller → Service → Entity du projet.

Dans le template, les choix ne sont pas affichés en boutons radio : chaque
choix devient un `<button type="submit">` qui porte le `name` du champ
(`client_status[status]`) et sa valeur. Un seul clic suffit, et on garde la
validation et le jeton CSRF du formulaire Symfony.

### 3. Service `ClientService::decider()` (`src/Service/ClientService.php`)

```php
public function decider(Client $client, int $status): void
```

Lance une `RuntimeException` si le client n'est plus un pré-client. Sinon,
applique le statut et appelle `flush()`.

### 4. Contrôleur `ClientController::show()` (`src/Controller/ClientController.php`)

Route `GET|POST /clients/{id}` (`app_client_show`). La page affiche la fiche
et traite le formulaire de décision, puis redirige vers la même fiche avec
un message flash.

### 5. Templates

- `templates/client/index.html.twig` : nouvelle colonne **Statut** (badge) et
  lien **Voir** vers la fiche.
- `templates/client/_status_badge.html.twig` : badge de couleur partagé
  (orange = pré-client, vert = validé, rouge = rejeté, gris = annulé).
- `templates/client/show.html.twig` : identité, coordonnées et suivi
  (équipe, commercial, lien vers le prospect d'origine), liste des contrats,
  puis les trois boutons (affichés seulement pour un pré-client).

## Tests

- `tests/Controller/ClientControllerTest.php::testShowDisplaysPreClientAndValidates`
  : la fiche affiche « Pré-client » et les 3 boutons. Valider redirige,
  affiche « Validé », masque les boutons et enregistre le statut.
- `tests/Service/ClientServiceTest.php::testDeciderAppliesStatusOnlyOnce`
  : une deuxième décision est refusée.
- `tests/Service/ClientServiceTest.php::testInvalidStatusIsRefused` : une
  valeur hors 1/2/3 est refusée.

## Pistes / à décider

- Renommer l'entrée de menu « Clients » en « Pré-clients », ou filtrer la
  liste sur les clients en attente seulement.
- Limiter la décision à certains utilisateurs, par exemple avec une
  permission `PERM_DECIDER_CLIENT` dans `App\Security\Permission`. Pour
  l'instant, tout utilisateur connecté peut décider.
- Brancher la suite du processus sur la décision (par exemple, ne permettre
  la création d'un contrat que pour un client validé).
