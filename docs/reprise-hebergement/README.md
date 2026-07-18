# Reprise de l’hébergement AutoChain Emma+

Ce dossier décrit, dans l’ordre, les étapes restantes avant une mise en ligne de démonstration sur Sepolia.

## Point de reprise

Commencer par **l’étape 03**. La configuration a été durcie et le contrat est maintenant déployé sur Sepolia.

## Ordre des étapes

1. [Corriger la configuration blockchain](01-configuration-blockchain/README.md)
2. [Déployer le contrat sur Sepolia](02-deploiement-sepolia/README.md)
3. [Configurer l’hébergement](03-configuration-hebergement/README.md)
4. [Configurer les workers et le scheduler](04-workers-scheduler/README.md)
5. [Installer et publier l’application](05-installation-application/README.md)
6. [Configurer sauvegardes et sécurité](06-sauvegardes-securite/README.md)
7. [Valider les scénarios sur Sepolia](07-validation-sepolia/README.md)
8. [Valider les services externes](08-services-externes/README.md)

## Démarrer le projet en local

### Avant d’ouvrir les terminaux

1. Démarrer Laragon et PostgreSQL.
2. Vérifier que la base `autochain_emma` est disponible.
3. Vérifier que le fichier `.env` utilise PostgreSQL et Hardhat local.

Au premier démarrage seulement, exécuter depuis PowerShell :

```powershell
Set-Location "C:\laragon\www\autochain-emma"
composer install
npm install
Push-Location "blockchain"
npm install
Pop-Location
php artisan migrate
php artisan storage:link
```

### Terminal 1 — Blockchain Hardhat

Ce terminal doit rester ouvert :

```powershell
Set-Location "C:\laragon\www\autochain-emma\blockchain"
npm run node
```

Réseau MetaMask local :

- Nom : `Hardhat Local`
- RPC : `http://127.0.0.1:8545`
- Chain ID : `31337`
- Symbole : `ETH`

### Terminal 2 — Déploiement puis serveur Laravel

Après le démarrage de Hardhat, redéployer le contrat. Cette opération est nécessaire chaque fois que le nœud Hardhat est arrêté puis redémarré.

```powershell
Set-Location "C:\laragon\www\autochain-emma\blockchain"
npm run deploy:localhost
Set-Location ".."
php artisan serve
```

Ce terminal doit ensuite rester ouvert pour Laravel.

Application : `http://127.0.0.1:8000`

### Terminal 3 — Frontend Vite

```powershell
Set-Location "C:\laragon\www\autochain-emma"
npm run dev
```

Ce terminal doit rester ouvert pendant le développement.

### Terminal 4 — File d’attente Laravel

```powershell
Set-Location "C:\laragon\www\autochain-emma"
php artisan queue:work
```

Ce terminal traite les notifications et les tâches blockchain asynchrones.

### Terminal 5 — Scheduler Laravel

```powershell
Set-Location "C:\laragon\www\autochain-emma"
php artisan schedule:work
```

Ce terminal déclenche notamment `blockchain:reconcile` chaque minute.

### Ordre d’ouverture

1. Hardhat.
2. Déploiement du contrat puis Laravel.
3. Vite.
4. Queue worker.
5. Scheduler.

Pour arrêter proprement un service, utiliser `Ctrl+C` dans son terminal.

Si `php artisan` indique que le pilote PostgreSQL est absent, sélectionner dans Laragon la version PHP ayant `pdo_pgsql` activé, puis rouvrir les terminaux.

## État actuel

- Application Laravel/PostgreSQL : fonctionnelle en local.
- Frontend Vue/Inertia : fonctionnel.
- Contrat Solidity : testé avec Hardhat.
- Wallets MetaMask : liaison par challenge et signature validée.
- Parcours véhicule, affectation, kilométrage, maintenance et vente : validés localement.
- Documentation utilisateur : disponible dans `Utilisateur/`.
- Déploiement Sepolia : terminé, contrat `0xB04b51e7B65684c409ff45d360342f0a82E18ea0`.
- Hébergement : à configurer.

## Informations à préparer au retour

- Nom de l’hébergeur et type d’offre.
- Nom de domaine ou sous-domaine.
- Accès PostgreSQL.
- URL RPC Sepolia.
- Wallet administrateur Sepolia et son solde public.
- Fournisseur SMTP.
- Fournisseur IPFS ou nœud Kubo.

Ne jamais inscrire une clé privée, une phrase de récupération ou un mot de passe dans ces fichiers.
