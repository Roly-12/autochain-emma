# Guide pas‑à‑pas pour tester l'application en local

Dernière mise à jour: 2026-07-01
But: tester localement toutes les fonctionnalités (web, queue, blockchain local, MetaMask).

Prérequis
- PHP 8.x et Composer
- Node.js 18+ et npm
- Git
- (Optionnel) WSL2 si Hardhat pose problème sous Windows

Étapes rapides (une à une)

1) Cloner et préparer le projet
```bash
cd C:\laragon\www\
# si pas déjà cloné
git clone <repo-url> autochain-emma
cd autochain-emma
```

2) Installer dépendances back & front
```bash
composer install --no-interaction --prefer-dist
npm install
# installer dépendances blockchain
npm --prefix blockchain install
```

3) Configurer `.env` local
- Copier le modèle et adapter:
```bash
cp .env.example .env
php artisan key:generate
```
- Configurer la DB locale (sqlite ou Postgres). Exemple sqlite rapide:
```
DB_CONNECTION=sqlite
DB_DATABASE=database/database.sqlite
```
- Créer le fichier sqlite vide si nécessaire:
```bash
mkdir -p database
type nul > database\database.sqlite    # PowerShell
# ou sur linux: touch database/database.sqlite
```

4) Migrations & tables queue
```bash
php artisan migrate
php artisan queue:table
php artisan queue:failed-table
php artisan migrate
```

5) Installer / builder frontend
```bash
# development
npm run dev
# ou build pour production
npm run build
# installer ethers si tu veux utiliser MetaMask (le front l'attend)
npm install ethers
```

6) Lancer la blockchain locale (Hardhat) et déployer local
- Ouvrir un terminal séparé:
```bash
cd blockchain
npx hardhat node
```
- Dans un autre terminal (toujours `blockchain/`):
```bash
npx hardhat run --network localhost scripts/deploy.js
# ou: npm run deploy:localhost
```
- Vérifier que `blockchain/deployment.json` contient l'`address` et `abi`.

7) Mettre à jour `.env` (local) si besoin
- Si le script a écrit `blockchain/deployment.json`, tu peux mettre :
```
BLOCKCHAIN_NODE_URL=http://127.0.0.1:8545
VEHICLE_REGISTRY_ADDRESS=<adresse_extrait_de_deployment.json>
VITE_VEHICLE_REGISTRY_ADDRESS=<meme_adresse>
```
- Redémarrer le dev server si nécessaire.

8) Démarrer l'app Laravel et le worker
```bash
# lancer backend
php artisan serve --host=127.0.0.1 --port=8000
# lancer worker (dans un autre terminal)
php artisan queue:work database --tries=3 --sleep=3 --timeout=60
# ou utiliser scripts\queue-work.ps1 (Windows PowerShell)
```

9) Tests rapides fonctionnels
- Ouvrir le navigateur à `http://127.0.0.1:8000`
- S'inscrire (email). Pour forcer MFA, utiliser une adresse `@gmail.com` ou `@icloud.com`.
- Créer un véhicule via `/vehicles/create`.
- Observer que le job est ajouté à `jobs` et consommé par le worker ; vérifier que `vehicles.transaction_hash` est rempli.
- Pour MetaMask : ajouter le réseau `http://127.0.0.1:8545` dans MetaMask, importer une clé fournie par `npx hardhat node`, puis accéder à `/vehicles/metamask`, connecter MetaMask et envoyer la tx.

10) Exécuter tests PHPUnit
```bash
php artisan test
# ex: tester Vehicle uniquement
php artisan test --filter=VehicleTest --env=testing
```

Vérifications rapides / Debug
- Hardhat ne démarre pas ou échoue sous Windows → exécuter dans WSL2 ou utiliser `SEPOLIA_RPC_URL` distant.
- `php artisan blockchain:health -v` doit indiquer que le nœud répond et que l'appel de lecture du contrat fonctionne.
- Si jobs restent dans `jobs` : vérifier le worker (logs), `php artisan queue:failed`.
- Si transaction timeout : augmenter `BLOCKCHAIN_TIMEOUT` dans `config/blockchain.php`.

Checklist "prêt pour test local" (à valider)
- [ ] `composer install` exécuté sans erreur
- [x] `npm --prefix blockchain install` exécuté (vérifié)
- [ ] `.env` correctement configuré (DB + blockchain variables si test local)
- [ ] `php artisan migrate` exécuté et tables créées
- [ ] `npx hardhat node` en cours ou `BLOCKCHAIN_NODE_URL` pointant vers un provider
- [ ] `npx hardhat run --network localhost scripts/deploy.js` exécuté (ou `blockchain/deployment.json` présent)
- [ ] `php artisan queue:work` en cours
- [ ] Frontend (`npm run dev`) en cours
- [ ] Tests PHPUnit passent (`php artisan test`)

Si tu veux, je peux :
- exécuter des vérifications supplémentaires ici (lancer migrations/tests) ;
- t'accompagner pas-à‑pas en direct pendant que tu runs les commandes et corriger les erreurs éventuelles.

---

Fichier créé: `docs/LOCAL_STEP_BY_STEP.md`.

Statut actuel pour tests locaux (basé sur vérifications rapides) :
- `blockchain/node_modules` installé OK.
- `npm install` dans `blockchain/` a été exécuté avec succès.

Reste à faire localement (si tu veux tester maintenant) :
- lancer `npx hardhat node` et déployer local, mettre à jour `.env`, lancer le worker et démarrer l'app.

Dis si tu veux que j'exécute une vérification/commande précise maintenant (ex: `php artisan migrate` ou `php artisan test`).