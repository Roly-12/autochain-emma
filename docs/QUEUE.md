# Lancer le worker de files (Windows / Laragon)

Ce projet utilise la connexion `database` pour la queue. Pour traiter les jobs (ex: `RegisterVehicleOnBlockchain`) en local sur Windows/Laragon, suivez ces étapes :

1. Vérifiez votre `.env` :

```bash
# Assurez-vous que la connexion queue est database
QUEUE_CONNECTION=database

# Configurez votre nœud blockchain réel
BLOCKCHAIN_NODE_URL=http://127.0.0.1:8545
VEHICLE_REGISTRY_ADDRESS=0x...
BLOCKCHAIN_ADMIN_ADDRESS=0x...
BLOCKCHAIN_ADMIN_PRIVATE_KEY=your_private_key
```

2. Créez la table `jobs` / `failed_jobs` si ce n'est pas déjà fait :

```bash
php artisan migrate
php artisan queue:table
php artisan queue:failed-table
php artisan migrate
```

3. Démarrer le worker (PowerShell recommandé) :

```powershell
# PowerShell
.\scripts\queue-work.ps1
```

Ou avec le batch Windows :

```powershell
# cmd.exe
scripts\queue-work.bat
```

Le script redémarrera automatiquement le worker si celui-ci se termine.

4. Supervision / production

- Sur Linux, utilisez `supervisord` ou `systemd` pour gérer `php artisan queue:work`.
- Assurez-vous d'avoir un nœud blockchain fonctionnel (Hardhat, Geth, Infura, Alchemy) et que `VEHICLE_REGISTRY_ADDRESS` et la clé admin sont configurés.

5. Vérifier les logs

Les erreurs de transaction blockchain sont loggées via `logger()`.

---

Si vous veux, j'ajoute un `health` command pour tester la connectivité au nœud et au contrat.