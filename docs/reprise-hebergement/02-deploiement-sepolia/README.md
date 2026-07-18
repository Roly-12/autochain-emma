# Étape 02 — Déployer le contrat sur Sepolia

## Objectif

Déployer `VehicleRegistry.sol` sur le réseau public de test Sepolia.

## Prérequis

- Étape 01 terminée.
- Nouveau wallet administrateur dédié à Sepolia.
- Sepolia ETH disponibles sur ce wallet.
- URL RPC Sepolia fonctionnelle.
- Dépendances du dossier `blockchain/` installées.

## Informations sensibles

La clé privée du déployeur doit être configurée directement dans l’environnement sécurisé de déploiement. Ne jamais la transmettre dans le chat, la documentation ou Git.

## Procédure

1. Configurer `SEPOLIA_RPC_URL` et `DEPLOYER_PRIVATE_KEY` dans `blockchain/.env`.
2. Vérifier que le wallet possède du Sepolia ETH.
3. Exécuter les tests du contrat.
4. Déployer sur Sepolia avec le script Hardhat prévu.
5. Conserver l’adresse du contrat, le hash de déploiement, le bloc, le chain ID et l’adresse du déployeur.
6. Vérifier le contrat sur Sepolia Etherscan si possible.

## Validation

- Chain ID : `11155111`.
- Contrat visible sur `https://sepolia.etherscan.io/`.
- L’administrateur du contrat correspond au wallet déployeur.
- L’ABI et l’adresse sont reportées dans la configuration applicative.
- Une lecture RPC du contrat réussit.

## Statut

`Déployé le 18/07/2026`.

- Tests Solidity : `25 réussis`.
- Réseau : `sepolia`.
- Chain ID : `11155111`.
- Contrat : `0xB04b51e7B65684c409ff45d360342f0a82E18ea0`.
- Administrateur : `0xd8Ff9440467acA014840D75473e54BE880761603`.
- Explorateur : `https://sepolia.etherscan.io/address/0xB04b51e7B65684c409ff45d360342f0a82E18ea0`.
- Bytecode confirmé sur Sepolia Etherscan.
- La publication du code source sur Etherscan reste facultative et nécessite une clé API Etherscan.

Le script de déploiement enregistre désormais aussi le hash de transaction et le numéro de bloc pour les prochains déploiements.

Prochaine étape : [configurer l’hébergement](../03-configuration-hebergement/README.md).
