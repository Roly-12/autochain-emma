# Étape 03 — Configurer l’hébergement

## Objectif

Préparer un environnement Laravel de production sécurisé et compatible avec PostgreSQL et Sepolia.

## Informations à fournir au retour

- Hébergeur et type d’offre : VPS, PaaS ou mutualisé.
- Domaine ou sous-domaine.
- Version de PHP disponible.
- Accès PostgreSQL.
- Possibilité d’exécuter des workers permanents et des tâches planifiées.
- Type de stockage persistant disponible.

## Exigences techniques

- PHP 8.3 avec `pdo_pgsql`, `bcmath` et `gd`.
- Racine web pointant uniquement vers `public/`.
- PostgreSQL avec TLS si la base est distante.
- HTTPS obligatoire.
- Répertoires `storage/` et `bootstrap/cache/` accessibles en écriture.

## Configuration de production

- `APP_ENV=production`
- `APP_DEBUG=false`
- `APP_URL=https://...`
- Nouvelle valeur `APP_KEY`
- Paramètres PostgreSQL
- `SESSION_SECURE_COOKIE=true`
- Paramètres RPC, contrat et chain ID Sepolia
- Paramètres SMTP et IPFS
- Canal de logs adapté à l’hébergeur

## Validation

- La page `/up` répond correctement en HTTPS.
- Les erreurs détaillées Laravel ne sont pas visibles publiquement.
- Les cookies de session sont marqués Secure.
- L’application se connecte à PostgreSQL et au RPC Sepolia.

## Statut

`En attente du choix de l’hébergeur et du domaine`.
