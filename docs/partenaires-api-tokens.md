# Tokens API partenaires — sites d'injection de prospects

Chaque site externe (partenaire) est **dédié à un seul produit** — le token
détermine à lui seul le `product_id` du prospect créé, il n'est jamais
transmis dans le payload JSON (voir `Compartenaire::product`, ManyToOne).

Header à envoyer sur chaque appel :

```
POST /api/prospects
X-API-TOKEN: <token du site>
Content-Type: application/json
```

---

## Véhicule

| Site | Token |
|---|---|
| Partenaire Démo Véhicule | `demo-token-f9123509a9afe083` |
| Assurance Véhicule Pro | `site-vehicule-pro-11943ffe67932637c6bbc969da6bc2b2` |

## Prévoyance

| Site | Token |
|---|---|
| Site Prévoyance | `site-prevoyance-284c0b48751627bec58327d2c6a10bfd` |

## Construction

| Site | Token |
|---|---|
| Site Construction | `site-construction-3e285ee8f2326351b94e5442d9563003` |

## RC

| Site | Token |
|---|---|
| Site RC | `site-rc-ed0d5cd44d57a995f3230b99a190ad0c` |

> ⚠️ **Multirisque** existe comme produit distinct en base (`product.id = 7`)
> mais n'a pas encore de `Compartenaire`/token — un `Compartenaire` ne peut
> porter qu'un seul produit à la fois. Si un site externe présente
> réellement ce produit, créer un token dédié (voir procédure ci-dessous)
> plutôt que de réutiliser un token existant.

---

## Créer un nouveau token pour un nouveau site partenaire

```bash
# 1. Générer un token aléatoire
ddev exec php -r 'echo "site-<nom-site>-" . bin2hex(random_bytes(16)) . "\n";'

# 2. L'insérer en base, rattaché au bon product_id
ddev exec php bin/console dbal:run-sql "INSERT INTO compartenaire (nom, api_token, api_actif, product_id) VALUES ('Nom du site', '<token généré>', 1, <product_id>)"
```

`product_id` de référence :

| Produit | code | id |
|---|---|---|
| Véhicule | `vehicule` | 4 |
| Prévoyance | `prevoyance` | 5 |
| Construction | `construction` | 6 |
| Multirisque | `multirisque` | 7 |
| RC | `rc` | 8 |

Côté site partenaire, configurer en `.env.local` (jamais en dur dans le code) :

```
AKSAM_API_URL=https://aksamcrm.ddev.site/api/prospects
AKSAM_API_TOKEN=<token généré>
```

## Exemple d'appel Postman/curl

```bash
curl -X POST https://aksamcrm.ddev.site/api/prospects \
  -H "X-API-TOKEN: site-prevoyance-284c0b48751627bec58327d2c6a10bfd" \
  -H "Content-Type: application/json" \
  -d '{
    "nom": "affectAuto",
    "prenom": "AutoAff",
    "phone": "0654556677",
    "email": "affec.lefevre@example.com",
    "capitalSouhaite": "5000",
    "situationFamiliale": "Marie",
    "nombreEnfants": "5"
  }'
```
