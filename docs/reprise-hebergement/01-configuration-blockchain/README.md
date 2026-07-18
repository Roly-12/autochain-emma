# Étape 01 — Corriger la configuration blockchain

## Objectif

Empêcher le fichier local `blockchain/deployment.json` de remplacer la configuration Sepolia fournie par l’environnement d’hébergement.

## Problème actuel

`app/Providers/BlockchainServiceProvider.php` charge automatiquement `blockchain/deployment.json`. Ce fichier contient actuellement le déploiement Hardhat local avec le chain ID `31337`.

## Travail à réaliser

- Donner la priorité aux variables d’environnement en production.
- Charger `deployment.json` seulement en environnement local, ou générer un artefact propre à chaque réseau.
- Vérifier que l’adresse du contrat, l’ABI, le chain ID, le réseau et l’administrateur proviennent tous de la même configuration.
- Ajouter un contrôle au démarrage qui refuse une combinaison incohérente.

## Valeurs attendues pour Sepolia

- Réseau : `sepolia`
- Chain ID : `11155111`
- RPC : fourni par le prestataire retenu
- Adresse du contrat : obtenue à l’étape 02
- Administrateur : adresse publique du wallet ayant déployé le contrat

## Validation

- L’application affiche Sepolia comme réseau attendu.
- Une adresse Hardhat ne peut plus être chargée en production.
- `eth_chainId` retourne `11155111`.
- Le bytecode est présent à l’adresse du contrat configurée.

## Statut

`Implémenté le 18/07/2026`.

- Le fichier `deployment.json` est maintenant réservé à `APP_ENV=local`.
- Hors environnement local, toutes les valeurs blockchain proviennent de l’environnement.
- Les adresses, l’ABI, l’URL RPC, le réseau et le chain ID sont contrôlés au démarrage.
- Hardhat et les RPC locaux sont refusés hors environnement local.
- Trois tests automatisés ont été ajoutés dans `tests/Unit/BlockchainConfigurationTest.php`.
- L’exécution de ces tests reste à relancer : l’exécuteur de terminal Cursor ne renvoyait aucun code de sortie lors de cette reprise.

Prochaine étape : [déployer le contrat sur Sepolia](../02-deploiement-sepolia/README.md).
