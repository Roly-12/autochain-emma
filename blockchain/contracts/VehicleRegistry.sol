// SPDX-License-Identifier: MIT
pragma solidity ^0.8.20;

/**
 * @title VehicleRegistry
 * @notice Registre technique sans donnée personnelle en clair.
 * @dev Les identifiants et justificatifs sont uniquement stockés sous forme de bytes32.
 */
contract VehicleRegistry {
    enum VehicleStatus {
        Active,
        Maintenance,
        Immobilized,
        Stolen,
        Retired
    }

    struct Vehicle {
        bytes32 vehicleHash;
        uint256 currentMileage;
        uint256 mileageTimestamp;
        VehicleStatus status;
        address currentOwner;
        address assignedDriver;
        bool exists;
    }

    struct MaintenanceRecord {
        bytes32 maintenanceHash;
        address garage;
        uint256 timestamp;
        uint256 mileage;
    }

    struct PendingTransfer {
        address buyer;
        address proposer;
        uint256 proposedAt;
    }

    address public immutable admin;
    mapping(address => bool) public certifiedGarages;
    mapping(bytes32 => Vehicle) private vehicles;
    mapping(bytes32 => MaintenanceRecord[]) private maintenanceHistory;
    mapping(bytes32 => PendingTransfer) public pendingTransfers;

    event VehicleRegistered(
        bytes32 indexed vehicleHash,
        address indexed owner,
        VehicleStatus status,
        uint256 timestamp
    );
    event DriverAssigned(
        bytes32 indexed vehicleHash,
        address indexed driver,
        address indexed actor,
        uint256 timestamp
    );
    event MileageUpdated(
        bytes32 indexed vehicleHash,
        uint256 previousMileage,
        uint256 newMileage,
        address indexed actor,
        uint256 timestamp
    );
    event StatusUpdated(
        bytes32 indexed vehicleHash,
        VehicleStatus previousStatus,
        VehicleStatus newStatus,
        address indexed actor,
        uint256 timestamp
    );
    event MaintenanceRecorded(
        bytes32 indexed vehicleHash,
        bytes32 indexed maintenanceHash,
        address indexed garage,
        uint256 mileage,
        uint256 timestamp
    );
    event GarageCertificationUpdated(
        address indexed garage,
        bool certified,
        address indexed actor,
        uint256 timestamp
    );
    event TransferProposed(
        bytes32 indexed vehicleHash,
        address indexed seller,
        address indexed buyer,
        address proposer,
        uint256 timestamp
    );
    event TransferAccepted(
        bytes32 indexed vehicleHash,
        address indexed previousOwner,
        address indexed newOwner,
        uint256 timestamp
    );
    event TransferCancelled(
        bytes32 indexed vehicleHash,
        address indexed buyer,
        address indexed actor,
        uint256 timestamp
    );

    modifier onlyAdmin() {
        require(msg.sender == admin, "Reserve a l'administrateur");
        _;
    }

    modifier onlyCertifiedGarage() {
        require(certifiedGarages[msg.sender], "Garage non certifie");
        _;
    }

    modifier vehicleExists(bytes32 vehicleHash) {
        require(vehicles[vehicleHash].exists, "Vehicule inexistant");
        _;
    }

    constructor() {
        admin = msg.sender;
    }

    function registerVehicle(bytes32 vehicleHash) external onlyAdmin {
        require(vehicleHash != bytes32(0), "Hash vehicule invalide");
        require(!vehicles[vehicleHash].exists, "Vehicule deja enregistre");

        vehicles[vehicleHash] = Vehicle({
            vehicleHash: vehicleHash,
            currentMileage: 0,
            mileageTimestamp: block.timestamp,
            status: VehicleStatus.Active,
            currentOwner: msg.sender,
            assignedDriver: address(0),
            exists: true
        });

        emit VehicleRegistered(vehicleHash, msg.sender, VehicleStatus.Active, block.timestamp);
    }

    function setGarageCertification(address garage, bool certified) external onlyAdmin {
        require(garage != address(0), "Adresse invalide");
        require(certifiedGarages[garage] != certified, "Certification inchangee");
        certifiedGarages[garage] = certified;
        emit GarageCertificationUpdated(garage, certified, msg.sender, block.timestamp);
    }

    function assignDriver(
        bytes32 vehicleHash,
        address driver
    ) external vehicleExists(vehicleHash) {
        Vehicle storage vehicle = vehicles[vehicleHash];
        require(msg.sender == admin || msg.sender == vehicle.currentOwner, "Non autorise");
        require(vehicle.assignedDriver != driver, "Chauffeur inchange");

        vehicle.assignedDriver = driver;
        emit DriverAssigned(vehicleHash, driver, msg.sender, block.timestamp);
    }

    function updateMileage(
        bytes32 vehicleHash,
        uint256 newMileage
    ) external vehicleExists(vehicleHash) {
        Vehicle storage vehicle = vehicles[vehicleHash];
        require(
            msg.sender == admin ||
                msg.sender == vehicle.currentOwner ||
                msg.sender == vehicle.assignedDriver ||
                certifiedGarages[msg.sender],
            "Non autorise"
        );
        require(newMileage > vehicle.currentMileage, "Kilometrage non croissant");

        uint256 previousMileage = vehicle.currentMileage;
        vehicle.currentMileage = newMileage;
        vehicle.mileageTimestamp = block.timestamp;
        emit MileageUpdated(
            vehicleHash,
            previousMileage,
            newMileage,
            msg.sender,
            block.timestamp
        );
    }

    function updateStatus(
        bytes32 vehicleHash,
        VehicleStatus newStatus
    ) external vehicleExists(vehicleHash) {
        Vehicle storage vehicle = vehicles[vehicleHash];
        require(
            msg.sender == admin ||
                msg.sender == vehicle.currentOwner ||
                msg.sender == vehicle.assignedDriver ||
                certifiedGarages[msg.sender],
            "Non autorise"
        );
        require(vehicle.status != newStatus, "Statut inchange");

        VehicleStatus previousStatus = vehicle.status;
        vehicle.status = newStatus;
        emit StatusUpdated(vehicleHash, previousStatus, newStatus, msg.sender, block.timestamp);
    }

    function recordMaintenance(
        bytes32 vehicleHash,
        bytes32 maintenanceHash,
        uint256 mileage
    ) external onlyCertifiedGarage vehicleExists(vehicleHash) {
        require(maintenanceHash != bytes32(0), "Hash maintenance invalide");
        require(mileage == vehicles[vehicleHash].currentMileage, "Kilometrage incoherent");

        maintenanceHistory[vehicleHash].push(
            MaintenanceRecord({
                maintenanceHash: maintenanceHash,
                garage: msg.sender,
                timestamp: block.timestamp,
                mileage: mileage
            })
        );
        emit MaintenanceRecorded(
            vehicleHash,
            maintenanceHash,
            msg.sender,
            mileage,
            block.timestamp
        );
    }

    function proposeTransfer(
        bytes32 vehicleHash,
        address buyer
    ) external vehicleExists(vehicleHash) {
        Vehicle storage vehicle = vehicles[vehicleHash];
        require(msg.sender == admin || msg.sender == vehicle.currentOwner, "Non autorise");
        require(buyer != address(0) && buyer != vehicle.currentOwner, "Acheteur invalide");
        require(pendingTransfers[vehicleHash].buyer == address(0), "Transfert deja propose");

        pendingTransfers[vehicleHash] = PendingTransfer({
            buyer: buyer,
            proposer: msg.sender,
            proposedAt: block.timestamp
        });
        emit TransferProposed(
            vehicleHash,
            vehicle.currentOwner,
            buyer,
            msg.sender,
            block.timestamp
        );
    }

    function acceptTransfer(bytes32 vehicleHash) external vehicleExists(vehicleHash) {
        PendingTransfer memory pending = pendingTransfers[vehicleHash];
        require(pending.buyer != address(0), "Aucun transfert en attente");
        require(msg.sender == pending.buyer, "Reserve a l'acheteur");

        Vehicle storage vehicle = vehicles[vehicleHash];
        address previousOwner = vehicle.currentOwner;
        vehicle.currentOwner = pending.buyer;
        delete pendingTransfers[vehicleHash];

        if (vehicle.assignedDriver != address(0)) {
            vehicle.assignedDriver = address(0);
            emit DriverAssigned(vehicleHash, address(0), msg.sender, block.timestamp);
        }
        emit TransferAccepted(vehicleHash, previousOwner, pending.buyer, block.timestamp);
    }

    function cancelTransfer(bytes32 vehicleHash) external vehicleExists(vehicleHash) {
        PendingTransfer memory pending = pendingTransfers[vehicleHash];
        require(pending.buyer != address(0), "Aucun transfert en attente");
        require(
            msg.sender == admin ||
                msg.sender == vehicles[vehicleHash].currentOwner ||
                msg.sender == pending.buyer,
            "Non autorise"
        );

        delete pendingTransfers[vehicleHash];
        emit TransferCancelled(vehicleHash, pending.buyer, msg.sender, block.timestamp);
    }

    function getVehicle(
        bytes32 vehicleHash
    ) external view vehicleExists(vehicleHash) returns (Vehicle memory) {
        return vehicles[vehicleHash];
    }

    function getMaintenanceHistory(
        bytes32 vehicleHash
    ) external view vehicleExists(vehicleHash) returns (MaintenanceRecord[] memory) {
        return maintenanceHistory[vehicleHash];
    }
}