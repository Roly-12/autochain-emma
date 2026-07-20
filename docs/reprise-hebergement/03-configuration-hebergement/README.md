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
- Paramètres Brevo HTTPS et IPFS
- Canal de logs adapté à l’hébergeur

## Validation

- La page `/up` répond correctement en HTTPS.
- Les erreurs détaillées Laravel ne sont pas visibles publiquement.
- Les cookies de session sont marqués Secure.
- L’application se connecte à PostgreSQL et au RPC Sepolia.

## Statut

`Hébergement Render gratuit opérationnel le 19/07/2026`.

- Plateforme : Render Web Service gratuit avec Docker.
- Base PostgreSQL : Supabase Emma+ en `eu-west-1`.
- Stockage : buckets Supabase `autochain-public` et `autochain-private`.
- Queue : mode `sync`, car les workers Render sont payants.
- Scheduler : best effort pendant que le service Web est éveillé.
- Blockchain : contrat Sepolia `0xB04b51e7B65684c409ff45d360342f0a82E18ea0`.
- Fichiers créés : `Dockerfile`, `docker/entrypoint.sh`, `.dockerignore` et `render.yaml`.
- Déploiement : image publique `ghcr.io/roly-12/autochain-emma:latest`, utilisée pour contourner le clonage GitHub refusé par Render.
- URL publique : https://autochain-emma.onrender.com.
- E-mail : API HTTPS Brevo, les ports SMTP étant bloqués sur Render Free.
- Validation : migrations, Super Admin, médias Supabase, OTP Brevo et transaction véhicule Sepolia réussis.

Les secrets restent saisis uniquement dans Render et ne doivent jamais être recopiés dans Git.

Le service gratuit se met en veille après quinze minutes et ne doit pas être présenté comme une production permanente.
