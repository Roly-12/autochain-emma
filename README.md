# AutoChain Emma+

Gestion de flotte avec Laravel, PostgreSQL, Vue/Inertia et un registre Ethereum. Les données administratives restent hors chaîne. Seuls les identifiants et empreintes nécessaires à la preuve sont inscrits dans le contrat.

## Règle de certification

Une saisie Laravel est d’abord `pending`. Elle devient `submitted` après transmission du hash MetaMask, puis `confirmed` uniquement si le serveur vérifie :

- le chain ID, le contrat destinataire et le wallet signataire ;
- le calldata exact demandé par Laravel ;
- un receipt réussi et l’événement attendu dans l’ABI.

Un kilométrage, une maintenance ou une vente sans receipt valide ne doit jamais être présenté comme certifié.

## Installation locale

Prérequis : PHP 8.3 avec `pdo_pgsql`, `bcmath` et `gd`, Composer, Node.js, PostgreSQL et MetaMask.

```bash
composer install
npm install
copy .env.example .env
php artisan key:generate
php artisan migrate --seed
php artisan storage:link
```

Configurer PostgreSQL et le mailer dans `.env`, puis lancer :

```bash
php artisan serve
npm run dev
php artisan queue:work
php artisan schedule:work
```

## Blockchain Hardhat locale

Dans `blockchain/` :

```bash
npm install
npm test
npm run node
npm run deploy:localhost
```

Le déploiement régénère `blockchain/deployment.json`, utilisé par Laravel pour l’adresse, l’ABI, le chain ID et le wallet administrateur. Dans MetaMask, ajouter le réseau `http://127.0.0.1:8545` (chain ID `31337`) et importer uniquement les comptes de test affichés par Hardhat.

Lier ensuite, depuis l’écran « Wallet MetaMask », des comptes distincts pour :

1. le Super Admin, qui doit être le déployeur du contrat ;
2. le garage, ensuite certifié on-chain par le Super Admin ;
3. le chauffeur affecté ;
4. l’acheteur désigné.

Ne jamais copier une phrase de récupération ou une clé privée dans Laravel. Les anciennes clés applicatives éventuellement utilisées doivent être considérées compromises et remplacées.

## Sepolia

Après validation complète en local :

```bash
cd blockchain
npm run deploy:sepolia
```

Configurer le RPC et le compte de déploiement uniquement dans l’environnement de déploiement du dossier blockchain. Sélectionner Sepolia dans MetaMask, obtenir du Sepolia ETH via un faucet, puis refaire les scénarios véhicule, garage, kilométrage, maintenance et vente. Le chain ID attendu est `11155111`.

## IPFS et e-mail

Un document n’est rendu public qu’après validation et pinning de son CID. Renseigner `IPFS_API_URL`, `IPFS_GATEWAY_URL` et `IPFS_ENABLED=true` pour le fournisseur choisi.

Sur Render Free, les ports SMTP sont bloqués. La production utilise donc l’API HTTPS Brevo avec `MAIL_MAILER=brevo`, `BREVO_API_KEY` et une valeur `MAIL_FROM_ADDRESS` vérifiée. Vérifier le scheduler avec :

```bash
php artisan schedule:list
php artisan fleet:generate-alerts
```

## Vérification

```bash
php artisan test
npm run build
cd blockchain
npm test
```

La commande `php artisan blockchain:reconcile` reprend les receipts encore soumis. Les événements confirmés sont indexés dans la timeline.

## Environnement hébergé

- Application : https://autochain-emma.onrender.com
- Conteneur : `ghcr.io/roly-12/autochain-emma:latest`
- Base et stockage : Supabase PostgreSQL et buckets S3-compatible
- Blockchain : Sepolia, chain ID `11155111`
- Contrat : `0xB04b51e7B65684c409ff45d360342f0a82E18ea0`
- E-mail : API HTTPS Brevo

L’offre Render Free peut mettre le service en veille. La queue est synchrone et le scheduler fonctionne en best effort lorsque le service est actif.
