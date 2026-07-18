# Étape 04 — Configurer les workers et le scheduler

## Objectif

Garantir le traitement des transactions blockchain, notifications, alertes et tâches planifiées après la mise en ligne.

## Processus indispensables

- Worker Laravel : `php artisan queue:work`
- Scheduler Laravel : exécution de `php artisan schedule:run` chaque minute, ou `php artisan schedule:work`
- Réconciliation : la commande `blockchain:reconcile` est planifiée chaque minute dans `bootstrap/app.php`

## Mise en place

- Utiliser Supervisor, systemd ou les services natifs de l’hébergeur.
- Redémarrer les workers après chaque déploiement.
- Configurer les tentatives, délais et limites mémoire.
- Centraliser les logs des workers.
- Surveiller la table ou la commande des jobs échoués.

## Validation

1. Envoyer une notification de test.
2. Créer une transaction blockchain et vérifier son passage de `submitted` à `confirmed`.
3. Déclencher le moteur d’alertes.
4. Vérifier qu’un worker redémarre automatiquement après un arrêt.
5. Vérifier que le scheduler s’exécute chaque minute.

## Statut

`À adapter à l’hébergeur choisi`.
