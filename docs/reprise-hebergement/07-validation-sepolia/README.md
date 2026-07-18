# Étape 07 — Valider les scénarios sur Sepolia

## Objectif

Rejouer sur le réseau public de test tous les parcours déjà validés avec Hardhat.

## Comptes nécessaires

- Super Admin avec le wallet administrateur du contrat.
- Garagiste agréé avec wallet distinct.
- Chauffeur avec wallet distinct.
- Auditeur / Acheteur avec wallet distinct.

Chaque wallet signataire doit posséder du Sepolia ETH.

## Scénario complet

1. Lier et vérifier les quatre wallets.
2. Certifier le garage on-chain.
3. Créer et enregistrer un nouveau véhicule.
4. Affecter le chauffeur.
5. Enregistrer un kilométrage strictement croissant.
6. Enregistrer une maintenance avec le kilométrage certifié.
7. Proposer une vente avec le Super Admin.
8. Accepter la vente avec le wallet exact de l’acheteur.

## Contrôles à chaque transaction

- Bon réseau : chain ID `11155111`.
- Bon compte MetaMask.
- Bon contrat.
- Transaction confirmée.
- Hash et bloc visibles sur Sepolia Etherscan.
- Événement présent dans la timeline AutoChain.
- État Laravel modifié uniquement après confirmation.

## Résultat attendu

Le véhicule termine avec le statut vendu, les deux transactions de vente sont conservées et toutes les étapes précédentes restent auditables.

## Statut

`À faire après le déploiement applicatif et blockchain`.
