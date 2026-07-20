# Étape 08 — Valider les services externes

## Objectif

Vérifier que les fonctions dépendant de services tiers fonctionnent réellement depuis l’hébergement.

## E-mail transactionnel

- Render Free bloque les ports SMTP 25, 465 et 587.
- Utiliser `MAIL_MAILER=brevo` avec une clé API Brevo v3.
- Vérifier l’adresse d’expéditeur configurée dans `MAIL_FROM_ADDRESS`.
- Tester les codes MFA obligatoires et les erreurs de livraison.

## IPFS

- Choisir un nœud Kubo accessible ou adapter le service à un fournisseur authentifié.
- Configurer l’API et la passerelle.
- Tester l’ajout, le CID, le pinning et la lecture publique.
- Vérifier qu’un échec IPFS ne marque jamais un document comme public.

## RPC Sepolia

- Vérifier les quotas et délais du fournisseur.
- Configurer un endpoint HTTPS.
- Tester `eth_chainId`, les transactions, les receipts et les logs.
- Prévoir un fournisseur de secours si la démonstration est critique.

## Supervision

- Tester `/up`.
- Centraliser les logs Laravel et workers.
- Définir des alertes pour erreurs HTTP, jobs échoués, RPC indisponible et espace disque.

## Validation finale

- E-mail réel reçu.
- Document IPFS consultable par son CID.
- Transaction Sepolia réconciliée.
- Sauvegarde restaurable.
- Guide PowerPoint et manuel PDF disponibles dans `Utilisateur/`.

## Statut

`Services essentiels validés le 19/07/2026`.

- OTP Brevo reçu.
- Médias publics Supabase téléversés et servis.
- RPC et transaction véhicule Sepolia validés.
- IPFS reste optionnel et désactivé tant qu’aucun fournisseur n’est configuré.
