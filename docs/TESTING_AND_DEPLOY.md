# Guide de test local, hébergement et vérification post-déploiement

Dernière mise à jour: 2026-07-01

Objectif
- Fournir des instructions claires pour tester l'application en local, déployer sur un serveur (ou CI) et vérifier que tout fonctionne (incluant la blockchain Sepolia et MetaMask).

Pré-requis
- PHP 8.x, Composer
- Node.js 18+ et npm
- Base de données (Postgres recommandé en production)
- Git
- Pour blockchain: compte Infura/Alchemy (Sepolia RPC) ou `npx hardhat` local

Fichiers importants
- `docs/PROJECT.md` — documentation générale
- `docs/TESTING_AND_DEPLOY.md` — ce fichier
- `blockchain/` — code Hardhat + `scripts/deploy.js`
- `blockchain/deployment.json` — adresse ABI après déploiement
- `app/Services/Blockchain/VehicleBlockchainService.php` — wrapper Web3 côté serveur
- `app/Jobs/RegisterVehicleOnBlockchain.php` — job queued
- `resources/js/Pages/Vehicles/RegisterWithMetaMask.vue` — page MetaMask
- `scripts/queue-work.ps1`, `scripts/queue-work.bat` — helpers worker Windows
- `app/Console/Commands/BlockchainHealth.php` — health check

1) Tester en local (développement)

A. Préparer l'environnement
```bash
# à la racine du projet
cp .env.example .env
composer install
npm install
npm --prefix blockchain install
php artisan key:generate
# config DB (sqlite pour tests) ou config Postgres
php artisan migrate
```

B. Lancer l'app Web + assets
```bash
# frontend dev
npm run dev
# backend
php artisan serve --host=127.0.0.1 --port=8000
```

C. Lancer la blockchain locale (Hardhat)
```bash
# depuis le dossier blockchain
cd blockchain
npx hardhat node
# dans un autre terminal, déployer sur localhost
npx hardhat run --network localhost scripts/deploy.js
```
- Vérifier `blockchain/deployment.json` mis à jour.

D. Lancer le worker de queue (traitement des jobs)
```bash
# PowerShell
.\scripts\queue-work.ps1
# ou (manuel)
php artisan queue:work database --tries=3 --sleep=3 --timeout=60
```

E. Tester les flows
- Inscription / MFA : créer un compte (email @gmail.com / @icloud.com active MFA auto), se connecter et vérifier le mail (logs ou service mail local).
- Véhicule : Ajouter un véhicule via l'UI (`/vehicles/create`) — vérifier que la ligne est créée en base et que `RegisterVehicleOnBlockchain` est dispatché et consommé par le worker ; vérifier `transaction_hash` rempli.
- MetaMask : Ouvrir `/vehicles/metamask` (Authentifié) → connecter MetaMask (sur réseau local ou Sepolia) → envoyer tx via MetaMask.

F. Tests unitaires/feature
```bash
# exécution tests
php artisan test
# lancer un test précis
php artisan test --filter=VehicleTest --env=testing
```

2) Déployer (hébergement) — checklist rapide

A. Choix d'hébergement
- Recommandé production: VPS (Ubuntu) ou plateforme cloud (DigitalOcean App Platform, AWS Elastic Beanstalk, Render, Railway).
- Exiger: PHP-FPM + Nginx, Composer, Node.js (pour build assets), Supervisor/ systemd pour worker.

B. Préparer le serveur (exemple Ubuntu)
```bash
sudo apt update && sudo apt install -y nginx php-fpm php-cli php-mbstring php-xml php-pgsql git unzip curl
# Node.js (ex: 18), Composer
```

C. Déployer code & config
- Cloner le repo, composer install, npm install & build (`npm run build`) à la racine, puis dans `blockchain` si nécessaire.
- Copier `.env` (config production), configurer `DB_*`, `QUEUE_CONNECTION=database`.
- Configurer `BLOCKCHAIN_NODE_URL` -> URL Sepolia (Infura/Alchemy) et `VEHICLE_REGISTRY_ADDRESS`.

D. Migrations & assets
```bash
php artisan migrate --force
npm run build   # si pas fait
php artisan storage:link
```

E. Services (systemd example)
- Worker `queue:work` systemd unit (exemple) :
```ini
[Unit]
Description=Laravel Queue Worker
After=network.target

[Service]
User=www-data
Group=www-data
Restart=always
ExecStart=/usr/bin/php /var/www/html/artisan queue:work database --sleep=3 --tries=3 --timeout=60

[Install]
WantedBy=multi-user.target
```
- Web: config Nginx -> PHP-FPM (standard Laravel deploy)

F. Key management & secrets
- Stocker `DEPLOYER_PRIVATE_KEY` et `BLOCKCHAIN_ADMIN_PRIVATE_KEY` dans un secret manager (AWS Secrets Manager, GCP Secret Manager, HashiCorp Vault) ou dans CI secrets.
- Ne pas committer `.env` en clair.

3) Déployer le contrat sur Sepolia (prod/test)

A. Obtenir RPC Sepolia
- Créer projet Infura/Alchemy → récupérer `SEPOLIA_RPC_URL`.
- Récupérer clé privée du deployer (compte test).

B. Déploiement local (machine dev)
```bash
cd blockchain
export SEPOLIA_RPC_URL='https://sepolia.infura.io/v3/PROJECT_ID'
export DEPLOYER_PRIVATE_KEY='0x...'
npm run deploy:sepolia
```
- Copier l'adresse dans le `.env` racine :
```
VEHICLE_REGISTRY_ADDRESS=0x...
VITE_VEHICLE_REGISTRY_ADDRESS=0x...
BLOCKCHAIN_NODE_URL=https://sepolia.infura.io/v3/PROJECT_ID
```

C. Déploiement via CI (GitHub Actions)
- Workflow ajouté `.github/workflows/deploy-sepolia.yml` — configure les secrets `SEPOLIA_RPC_URL` et `DEPLOYER_PRIVATE_KEY` et pousse sur `main`.

4) Vérifications post-déploiement

Checklist à exécuter après le déploiement en production :
- [ ] `php artisan migrate --force` a été exécuté.
- [ ] Assets construits (`public/build`) et le serveur web sert ces fichiers.
- [ ] Worker `queue:work` tourne (systemd/supervisor) et consomme `jobs`.
- [ ] `php artisan blockchain:health -v` retourne OK et le contrat répond.
- [ ] Créer un véhicule via l'UI → job dispatché → worker consomme → transaction hash enregistré.
- [ ] Tester la page MetaMask : connecter MetaMask au réseau Sepolia, importer un compte test avec Sepolia ETH et envoyer une tx.
- [ ] Logs (`storage/logs/laravel.log`) ne contiennent pas d'erreurs critiques liées à la blockchain.

5) Commandes utiles résumé
```bash
# tests
php artisan test
# build assets
npm run build
# worker (local)
php artisan queue:work database --tries=3 --sleep=3 --timeout=60
# health
php artisan blockchain:health -v
```

6) Dépannage rapide
- Impossible joindre nœud → vérifier `BLOCKCHAIN_NODE_URL`, DNS, firewall, ou utiliser Infura/Alchemy.
- Job pas consommé → vérifier worker, table `jobs`, et `failed_jobs`.
- Transaction timeout → augmenter `BLOCKCHAIN_TIMEOUT` dans `config/blockchain.php` ou vérifier nonce/fonds du compte.

7) Checklist sécurité avant mise en prod
- Clés privées dans un secret manager.
- TLS activé (HTTPS) pour le site et le RPC si applicable.
- Permissions fichiers correctes (www-data).
- Sauvegarde DB et plan de rollback.

8) Tests finaux (post-déploiement)
- Test end-to-end : Inscription → login → créer véhicule → worker traite → vérifier transaction sur Sepolia explorer.
- Test MetaMask : connecter, signer, observer tx sur Sepolia.

---

Analyse d'intégrité rapide et état d'avancement (résumé)

Composants implémentés (OK)
- Auth (inscription / connexion) avec MFA par email (pour certains domaines).
- Modèles et migrations : `User`, `Vehicle`, `Maintenance` et autres migrations nécessaires.
- Job: `RegisterVehicleOnBlockchain` et service `VehicleBlockchainService`.
- Pages Inertia/Vue pour CRUD véhicules et maintenance.
- Pagination basique sur listes.
- Page MetaMask pour permettre aux utilisateurs de signer des transactions.
- Commande artisan `blockchain:health`.
- Scripts d'aide pour worker sur Windows, workflow GitHub Actions pour déploiement Sepolia.
- Documentation `docs/PROJECT.md` et ce guide.

Composants partiellement implémentés / à vérifier
- Queue: configuration `database` en place; worker script fourni mais supervision systemd/supervisor pas encore déployée.
- Persistance du thème (`theme_preference`) : champ DB et layout partiels, mais UI toggle et persistance côté profil pas complètement finalisés.
- Gestion complète du profil (champs supplémentaires et UI) : controller `ProfileController` existant mais à vérifier pour tous les champs.
- Tests: quelques tests ajoutés (VehicleTest, MFA, password reset) mais suite de tests complète et couverture non mesurée.

Composants manquants / recommandations
- Intégration d'un secret manager et exemples de configuration production pour les clés privées.
- Tests end-to-end automatisés (Cypress / Playwright) pour flows critiques.
- Seeders et fixtures pour faciliter tests locaux (ex: comptes déployeur, garages certifiés).
- Améliorations UX/accessibility (labels ARIA, focus order, keyboard navigation).
- Monitoring/alerting (Sentry, Prometheus) et métriques pour jobs/transactions.

Conclusion sur "100% complet"
- Le projet est fonctionnel et couvre les fondamentaux (auth, CRUD, blockchain job, MetaMask, docs). Cependant il n'est pas "100% prêt" pour production : il manque hardening sécurité (secret management), monitoring, tests end-to-end complets, et automatisation de la gestion du worker en prod (systemd/supervisor) ainsi que quelques polish UX (theme toggle persistant, profil complet).

Proposition d'actions prioritaires pour rendre le projet production-ready
1. Intégrer Secret Manager et retirer toute clé du repo / `.env` partagé.
2. Automatiser worker en systemd/supervisor et ajouter health checks.
3. Déployer le contrat sur Sepolia via CI et mettre à jour `.env` production.
4. Ajouter tests E2E (Cypress) couvrant inscription, création véhicule, traitement job et vérif tx.
5. Finaliser theme toggle et persistance dans `ProfileController` + UI.
6. Ajouter seeders & fixtures pour dev/CI.

Si tu veux, j'implémente immédiatement la priorité 1 ou 2 (par ex. créer un sample `systemd` unit et une doc pour config Secret Manager, ou ajouter un workflow GitHub Actions complet qui effectue le build, migre la DB et déploie sur Sepolia).

---

Fait : j'ai créé ce fichier `docs/TESTING_AND_DEPLOY.md` dans le repo.
Prochaine étape : laquelle veux-tu prioriser maintenant (Secret Manager, systemd worker, tests E2E, ou je lance le déploiement Sepolia ici si tu fournis les secrets) ?
