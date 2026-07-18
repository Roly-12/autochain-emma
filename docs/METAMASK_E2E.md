# Scénarios E2E MetaMask

Ces scénarios sont exécutés après `npm run node`, `npm run deploy:localhost`, migration et démarrage de Laravel/Vite. Utiliser quatre comptes Hardhat distincts importés dans MetaMask. Ne jamais utiliser de wallet réel sur le réseau local.

## Préconditions

- Super Admin lié au wallet `deployer` indiqué dans `blockchain/deployment.json`.
- Garage, Chauffeur et Acheteur liés chacun par le challenge signé de l’écran Wallet.
- Réseau MetaMask : RPC `http://127.0.0.1:8545`, chain ID `31337`.

## Parcours obligatoire

1. Le Super Admin certifie le garage. Vérifier le receipt `GarageCertificationUpdated` et le badge on-chain.
2. Le Super Admin crée un véhicule. Refuser la transaction MetaMask une première fois : le véhicule ne doit pas être certifié. Relancer et approuver : `VehicleRegistered` doit passer l’état à `confirmed`.
3. Le Super Admin affecte le Chauffeur. Vérifier `DriverAssigned` puis l’accès du chauffeur au véhicule.
4. Le Chauffeur signe un kilométrage supérieur. Une valeur égale ou inférieure doit être rejetée. Après `MileageUpdated`, la valeur certifiée doit changer.
5. Le Garage certifié enregistre une maintenance au kilométrage certifié. Un autre wallet doit être rejeté. Vérifier que le SHA-256 affiché correspond à `MaintenanceRecorded`.
6. Le Super Admin propose la vente au wallet exact de l’Acheteur. Un autre compte ne doit ni voir ni accepter la vente.
7. L’Acheteur exact accepte. Le véhicule ne passe `sold` qu’après `TransferAccepted`; le chauffeur affecté est retiré.
8. Recharger la fiche véhicule. La timeline doit contenir les événements confirmés et les hashes de transaction.

## Sepolia

Rejouer le même parcours avec chain ID `11155111`. Capturer les liens de l’explorateur pour les cinq événements principaux. Si IPFS et SMTP ne sont pas encore configurés, noter ces deux contrôles comme externes et non comme certifiés.
