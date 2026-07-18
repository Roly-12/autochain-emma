# AutoChain Emma — Documentation du projet

Dernière mise à jour: 2026-07-01

Objectif
- Projet Laravel + Inertia + Vue3 + Tailwind pour gérer flotte de véhicules avec enregistrement certifié sur blockchain (contrat `VehicleRegistry`).

Table des matières
- Vue d'ensemble
- Prérequis locaux
- Variables d'environnement essentielles
- Base de données & migrations
- Queue (worker) — configuration et démarrage
- Blockchain — options d'hébergement et déploiement
- Intégration MetaMask (frontend)
- Sécurité & bonnes pratiques
- Déploiement (exemples systemd)
- Commandes utiles
- Arborescence et fichiers clés
- Tests et CI
- Dépannage rapide

---

Vue d'ensemble
- Backend: Laravel (PHP 8.x), Inertia, controllers dans `app/Http/Controllers`, services de blockchain dans `app/Services/Blockchain`.
- Frontend: Vue 3 + Inertia + Tailwind, pages dans `resources/js/Pages`.
- Blockchain: contrat Solidity `VehicleRegistry.sol` (dans `blockchain/contracts`), déploiement via Hardhat (`blockchain/scripts/deploy.js`).
- Queue: driver `database` (table `jobs`) — jobs: `App\Jobs\RegisterVehicleOnBlockchain`.

Prérequis locaux
- PHP 8.x, Composer
- Node.js 18+ et npm
- PostgreSQL (production) ou sqlite pour tests
- `npx hardhat` pour le développement blockchain

Variables d'environnement essentielles (extrait .env)
- `APP_ENV`, `APP_KEY`, `APP_URL`
- Base de données: `DB_CONNECTION`, `DB_HOST`, `DB_PORT`, `DB_DATABASE`, `DB_USERNAME`, `DB_PASSWORD`
- Queue: `QUEUE_CONNECTION=database`
- Blockchain:
  - `BLOCKCHAIN_NODE_URL` (ex: `http://127.0.0.1:8545` ou `https://eth-goerli.alchemyapi.io/v2/...`)
  - `VEHICLE_REGISTRY_ADDRESS` (adresse du contrat déployé)
  - `BLOCKCHAIN_ADMIN_ADDRESS` (compte serveur, optionnel)
  - `BLOCKCHAIN_ADMIN_PRIVATE_KEY` (si le serveur doit signer les txs)
  - `BLOCKCHAIN_GAS_LIMIT` (optionnel)

Base de données & migrations
- Migrations se situent dans `database/migrations`.
- Tables principales: `users`, `vehicles`, `maintenances`, `jobs`, `failed_jobs`, `password_reset_tokens`.
- Pour préparer la DB locale :

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
```

Queue (worker) — configuration et démarrage
- Driver recommandé local: `database`.
- Créer les tables de queue si nécessaire :

```bash
php artisan queue:table
php artisan queue:failed-table
php artisan migrate
```

- Scripts fournis pour Windows/Laragon :
  - `scripts/queue-work.ps1` (PowerShell, redémarre automatiquement)
  - `scripts/queue-work.bat` (CMD)

- Démarrer manuellement :

```bash
php artisan queue:work database --tries=3 --sleep=3 --timeout=60
```

Blockchain — options d'hébergement et déploiement
- Trois approches :
  1. Noeud local (Hardhat) — contrôle total, idéal dev. Exécuter `npx hardhat node` depuis `blockchain/`.
  2. Provider hébergé (Infura, Alchemy, QuickNode...) — recommandé en prod pour fiabilité.
  3. Noeud self-hosted (Geth, Nethermind) — plus lourd à maintenir.

Déployer le contrat (Hardhat) :

```bash
# depuis le dossier blockchain
npx hardhat run --network <network> scripts/deploy.js
# ex pour local
npx hardhat run --network localhost scripts/deploy.js
```

Sepolia (testnet) — déploiement

1. Obtenir un endpoint RPC Sepolia depuis un provider (Infura, Alchemy, QuickNode). Exemple : `https://sepolia.infura.io/v3/<PROJECT_ID>`.
2. Exporter la clé privée du compte qui déploiera le contrat (compte test). NE PAS partager cette clé.
3. Définir les variables d'environnement au niveau du terminal (ou dans un fichier `.env` à la racine du dossier `blockchain`):

```bash
export SEPOLIA_RPC_URL="https://sepolia.infura.io/v3/<PROJECT_ID>"
export DEPLOYER_PRIVATE_KEY="0x..."
```

Sur Windows (PowerShell) :

```powershell
$Env:SEPOLIA_RPC_URL='https://sepolia.infura.io/v3/<PROJECT_ID>'
$Env:DEPLOYER_PRIVATE_KEY='0x...'
```

4. Lancer le déploiement vers Sepolia (depuis le dossier `blockchain`):

```bash
npm run deploy:sepolia
```

5. Après le déploiement, le fichier `blockchain/deployment.json` sera mis à jour avec l'adresse et l'ABI. Copie l'adresse dans ton `.env` racine :

```
VEHICLE_REGISTRY_ADDRESS=0x....
VITE_VEHICLE_REGISTRY_ADDRESS=0x....
BLOCKCHAIN_NODE_URL=https://sepolia.infura.io/v3/<PROJECT_ID>
```

6. Pour obtenir du Sepolia ETH (test) : utilise un faucet Sepolia (recherches web "Sepolia faucet") ou via services comme Alchemy qui proposent des méthodes pour créditer des comptes de test.

Remarques :
- Sepolia a pour chainId `11155111` ; la configuration `hardhat.config.js` l'inclut.
- Ne commite jamais `DEPLOYER_PRIVATE_KEY` dans le repository. En production utilise un secret manager.

- Le fichier `blockchain/deployment.json` est chargé automatiquement par le `BlockchainServiceProvider` si présent.
- La clé privée admin ne doit jamais être commitée. En prod, stocker dans un Secret Manager.

Intégration MetaMask (frontend)
- Usages possibles :
  - Signer les transactions côté client (MetaMask) → l'utilisateur paie le gas.
  - Signer côté serveur (clé admin) → serveur paye le gas.

- Installer `ethers` côté client :

```bash
npm install ethers
```

- Exemple minimal Vue (Composition API) :

```js
import { ethers } from 'ethers'
import abi from '@/../blockchain/deployment.json'

async function connectAndRegister(vehicleId) {
  if (!window.ethereum) throw new Error('MetaMask non détecté')
  await window.ethereum.request({ method: 'eth_requestAccounts' })
  const provider = new ethers.providers.Web3Provider(window.ethereum)
  const signer = provider.getSigner()
  const contract = new ethers.Contract(process.env.VITE_VEHICLE_REGISTRY_ADDRESS, abi.abi, signer)
  const tx = await contract.registerVehicle(ethers.utils.keccak256(ethers.utils.toUtf8Bytes(vehicleId)))
  await tx.wait()
  return tx.hash
}
```

- Pour MetaMask & Hardhat local : importer la clé privée fournie par `npx hardhat node` dans MetaMask et ajouter le RPC `http://127.0.0.1:8545`.

Sécurité & bonnes pratiques
- Ne jamais stocker de clés privées dans le repo. Utiliser un secret manager (AWS Secrets Manager, Vault, etc.).
- Restreindre les permissions du compte admin (eviter usage quotidien). Utiliser des comptes spécifiques pour les opérations critiques.
- En prod, surveiller `failed_jobs` et logs (`storage/logs/laravel.log`).

Déploiement (exemple systemd pour worker sous Linux)

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

Commandes utiles
- Lancer tests : `php artisan test`
- Lancer build front : `npm run build`
- Lancer dev front : `npm run dev`
- Vérifier la blockchain : `php artisan blockchain:health` (commande ajoutée)

Arborescence et fichiers clés
- `app/Services/Blockchain/VehicleBlockchainService.php` : wrapper Web3 pour appels au contrat.
- `app/Jobs/RegisterVehicleOnBlockchain.php` : job queued qui appelle le service blockchain.
- `app/Notifications/MfaCodeNotification.php` : notification email MFA.
- `resources/js/Pages` : pages Inertia/Vue (ex: `Vehicles/Create.vue`).
- `blockchain/deployment.json` : adresse + ABI du contrat déployé (chargé automatiquement).
- `scripts/queue-work.ps1` et `scripts/queue-work.bat` : helpers pour worker Windows.
- `app/Console/Commands/BlockchainHealth.php` : commande artisan pour vérifier la connectivité.

Tests et CI
- Tests PHPUnit dans `tests/Feature` et `tests/Unit`.
- Exemple : `tests/Feature/VehicleTest.php` vérifie la création de véhicule et le dispatch du job.
- Les tests s'exécutent en sqlite memory par défaut dans l'environnement `testing`.

Dépannage rapide
- `blockchain:health` renvoie "Impossible de joindre le nœud" → vérifier `BLOCKCHAIN_NODE_URL`, démarrer `npx hardhat node` ou changer pour provider distant.
- Jobs non consommés → vérifier `php artisan queue:work` ou logs, vérifier table `jobs`.
- Transactions timeout → augmenter `BLOCKCHAIN_TIMEOUT` dans `config/blockchain.php` et s'assurer que le nœud est accessible.

Prochaines actions recommandées
- Déployer le contrat sur testnet (Goerli/Sepolia selon disponibilité) via Hardhat, mettre à jour `.env`.
- Choisir le modèle de signature (MetaMask côté client vs serveur signé) et implémenter l'UX correspondante.
- En production, configurer un service (systemd) pour le worker et un Secret Manager pour la clé admin.

---

Si tu veux, je peux :
- automatiser le déploiement Hardhat (script + mise à jour `blockchain/deployment.json`),
- ajouter la page Vue pour connexion MetaMask et enregistrement côté client,
- ajouter des instructions spécifiques pour hébergeurs (Heroku, DigitalOcean App Platform, etc.).

Dis‑moi quelle action prioritaire tu veux que j'exécute maintenant.
