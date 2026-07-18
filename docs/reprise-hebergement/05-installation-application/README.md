# Étape 05 — Installer et publier l’application

## Objectif

Installer une version reproductible et optimisée de l’application sur l’hébergement.

## Ordre recommandé

1. Déployer le code sans `.env` local, clés privées ni fichiers de démonstration sensibles.
2. Installer PHP : `composer install --no-dev --optimize-autoloader`.
3. Installer le frontend : `npm ci`.
4. Construire les assets : `npm run build`.
5. Configurer l’environnement de production.
6. Exécuter `php artisan migrate --force`.
7. Créer le lien public avec `php artisan storage:link`.
8. Exécuter `php artisan optimize`.
9. Redémarrer les workers.

## Précautions

- Sauvegarder la base avant toute migration ultérieure.
- Ne pas lancer les seeders de démonstration en production.
- Ne jamais publier `blockchain/.env`.
- Vérifier les permissions des fichiers sans rendre tout le projet accessible en écriture.

## Validation

- Accueil, connexion, inscription et MFA fonctionnent.
- Le logo global et les fichiers publics sont visibles.
- Les routes protégées respectent les rôles.
- Aucun fichier `.env`, stockage privé ou journal Laravel n’est accessible depuis le Web.
- Le build Vite est servi sans serveur `npm run dev`.

## Statut

`À exécuter après les étapes 01 à 04`.
