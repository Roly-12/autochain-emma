# Étape 06 — Sauvegardes et sécurité

## Objectif

Protéger les données PostgreSQL, les documents et les secrets avant l’ouverture aux testeurs.

## Sauvegardes

- Sauvegarde automatique PostgreSQL.
- Sauvegarde des documents privés et des médias persistants.
- Rétention de plusieurs versions.
- Copie située hors du serveur principal.
- Test réel de restauration.

## Sécurité

- HTTPS obligatoire et renouvellement automatique du certificat.
- `APP_DEBUG=false`.
- Secrets uniquement dans le gestionnaire sécurisé de l’hébergeur.
- Compte PostgreSQL dédié avec privilèges minimaux.
- Restriction de l’accès au RPC et au nœud IPFS lorsque possible.
- Rotation des logs et surveillance des erreurs.
- Vérification des dépendances avec `composer audit` et `npm audit`.
- Protection et sauvegarde hors ligne du wallet administrateur Sepolia.

## Données interdites dans Git

- Clés privées et phrases de récupération.
- Mots de passe PostgreSQL ou SMTP.
- Jetons RPC, IPFS ou API.
- Fichier `.env` de production.

## Validation

- Une restauration de test de la base et des fichiers réussit.
- Aucun secret n’apparaît dans le dépôt ou les logs.
- Les fichiers privés ne sont pas téléchargeables sans autorisation.
- Les alertes d’erreur serveur sont reçues par l’administrateur.

## Statut

`À configurer avant l’ouverture publique`.
