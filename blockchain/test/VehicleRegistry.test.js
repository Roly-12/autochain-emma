import { expect } from "chai";
import hre from "hardhat";

const { ethers } = hre;
const timestamp = (value) => value > 0n;

describe("VehicleRegistry", function () {
  let registry;
  let admin;
  let garage;
  let owner;
  let driver;
  let buyer;
  let outsider;
  let vehicleHash;

  beforeEach(async function () {
    [admin, garage, owner, driver, buyer, outsider] = await ethers.getSigners();
    const VehicleRegistry = await ethers.getContractFactory("VehicleRegistry");
    registry = await VehicleRegistry.deploy();
    await registry.waitForDeployment();

    vehicleHash = ethers.keccak256(ethers.toUtf8Bytes("technical-vehicle-id"));
    await registry.registerVehicle(vehicleHash);
  });

  async function transferTo(newOwner) {
    await registry.proposeTransfer(vehicleHash, newOwner.address);
    await registry.connect(newOwner).acceptTransfer(vehicleHash);
  }

  describe("administration et enregistrement", function () {
    it("désigne le déployeur comme administrateur", async function () {
      expect(await registry.admin()).to.equal(admin.address);
    });

    it("enregistre uniquement un hash technique non nul et émet les données indexables", async function () {
      const hash = ethers.keccak256(ethers.toUtf8Bytes("another-technical-id"));

      await expect(registry.registerVehicle(hash))
        .to.emit(registry, "VehicleRegistered")
        .withArgs(hash, admin.address, 0, timestamp);

      const vehicle = await registry.getVehicle(hash);
      expect(vehicle.vehicleHash).to.equal(hash);
      expect(vehicle.currentOwner).to.equal(admin.address);
      expect(vehicle.assignedDriver).to.equal(ethers.ZeroAddress);
      expect(vehicle.currentMileage).to.equal(0);
      expect(vehicle.status).to.equal(0);
      expect(vehicle.exists).to.equal(true);
    });

    it("refuse les non-administrateurs, le hash nul et les doublons", async function () {
      const hash = ethers.keccak256(ethers.toUtf8Bytes("unique-id"));

      await expect(registry.connect(outsider).registerVehicle(hash))
        .to.be.revertedWith("Reserve a l'administrateur");
      await expect(registry.registerVehicle(ethers.ZeroHash))
        .to.be.revertedWith("Hash vehicule invalide");
      await expect(registry.registerVehicle(vehicleHash))
        .to.be.revertedWith("Vehicule deja enregistre");
    });

    it("refuse la lecture d'un véhicule inexistant", async function () {
      await expect(registry.getVehicle(ethers.ZeroHash))
        .to.be.revertedWith("Vehicule inexistant");
    });
  });

  describe("garages certifiés", function () {
    it("permet uniquement à l'administrateur de certifier et révoquer un garage", async function () {
      await expect(registry.setGarageCertification(garage.address, true))
        .to.emit(registry, "GarageCertificationUpdated")
        .withArgs(garage.address, true, admin.address, timestamp);
      expect(await registry.certifiedGarages(garage.address)).to.equal(true);

      await expect(registry.setGarageCertification(garage.address, false))
        .to.emit(registry, "GarageCertificationUpdated")
        .withArgs(garage.address, false, admin.address, timestamp);
      expect(await registry.certifiedGarages(garage.address)).to.equal(false);
    });

    it("refuse adresse nulle, valeur inchangée et appel non administrateur", async function () {
      await expect(registry.setGarageCertification(ethers.ZeroAddress, true))
        .to.be.revertedWith("Adresse invalide");
      await expect(registry.setGarageCertification(garage.address, false))
        .to.be.revertedWith("Certification inchangee");
      await expect(registry.connect(outsider).setGarageCertification(garage.address, true))
        .to.be.revertedWith("Reserve a l'administrateur");
    });
  });

  describe("chauffeur affecté", function () {
    beforeEach(async function () {
      await transferTo(owner);
    });

    it("permet au propriétaire et à l'administrateur d'affecter ou retirer le chauffeur", async function () {
      await expect(registry.connect(owner).assignDriver(vehicleHash, driver.address))
        .to.emit(registry, "DriverAssigned")
        .withArgs(vehicleHash, driver.address, owner.address, timestamp);
      expect((await registry.getVehicle(vehicleHash)).assignedDriver).to.equal(driver.address);

      await expect(registry.assignDriver(vehicleHash, ethers.ZeroAddress))
        .to.emit(registry, "DriverAssigned")
        .withArgs(vehicleHash, ethers.ZeroAddress, admin.address, timestamp);
    });

    it("refuse un tiers et une affectation inchangée", async function () {
      await expect(registry.connect(outsider).assignDriver(vehicleHash, driver.address))
        .to.be.revertedWith("Non autorise");
      await registry.connect(owner).assignDriver(vehicleHash, driver.address);
      await expect(registry.connect(owner).assignDriver(vehicleHash, driver.address))
        .to.be.revertedWith("Chauffeur inchange");
    });
  });

  describe("kilométrage strictement croissant", function () {
    beforeEach(async function () {
      await transferTo(owner);
      await registry.connect(owner).assignDriver(vehicleHash, driver.address);
      await registry.setGarageCertification(garage.address, true);
    });

    it("autorise admin, propriétaire, chauffeur affecté et garage certifié", async function () {
      await expect(registry.updateMileage(vehicleHash, 10))
        .to.emit(registry, "MileageUpdated")
        .withArgs(vehicleHash, 0, 10, admin.address, timestamp);
      await registry.connect(owner).updateMileage(vehicleHash, 20);
      await registry.connect(driver).updateMileage(vehicleHash, 30);
      await registry.connect(garage).updateMileage(vehicleHash, 40);

      expect((await registry.getVehicle(vehicleHash)).currentMileage).to.equal(40);
    });

    it("refuse un tiers, un ancien chauffeur et un garage révoqué", async function () {
      await expect(registry.connect(outsider).updateMileage(vehicleHash, 10))
        .to.be.revertedWith("Non autorise");

      await registry.connect(owner).assignDriver(vehicleHash, ethers.ZeroAddress);
      await expect(registry.connect(driver).updateMileage(vehicleHash, 10))
        .to.be.revertedWith("Non autorise");

      await registry.setGarageCertification(garage.address, false);
      await expect(registry.connect(garage).updateMileage(vehicleHash, 10))
        .to.be.revertedWith("Non autorise");
    });

    it("refuse une valeur égale ou décroissante", async function () {
      await registry.updateMileage(vehicleHash, 100);
      await expect(registry.updateMileage(vehicleHash, 100))
        .to.be.revertedWith("Kilometrage non croissant");
      await expect(registry.updateMileage(vehicleHash, 99))
        .to.be.revertedWith("Kilometrage non croissant");
    });
  });

  describe("statuts critiques", function () {
    beforeEach(async function () {
      await transferTo(owner);
      await registry.connect(owner).assignDriver(vehicleHash, driver.address);
      await registry.setGarageCertification(garage.address, true);
    });

    it("gère tous les statuts et émet chaque transition", async function () {
      for (const [previousStatus, newStatus] of [[0, 1], [1, 2], [2, 3], [3, 4]]) {
        await expect(registry.updateStatus(vehicleHash, newStatus))
          .to.emit(registry, "StatusUpdated")
          .withArgs(vehicleHash, previousStatus, newStatus, admin.address, timestamp);
      }
      expect((await registry.getVehicle(vehicleHash)).status).to.equal(4);
    });

    it("autorise propriétaire, chauffeur affecté et garage certifié", async function () {
      await registry.connect(owner).updateStatus(vehicleHash, 1);
      await registry.connect(driver).updateStatus(vehicleHash, 2);
      await registry.connect(garage).updateStatus(vehicleHash, 3);
      expect((await registry.getVehicle(vehicleHash)).status).to.equal(3);
    });

    it("refuse un tiers et un statut inchangé", async function () {
      await expect(registry.connect(outsider).updateStatus(vehicleHash, 1))
        .to.be.revertedWith("Non autorise");
      await expect(registry.updateStatus(vehicleHash, 0))
        .to.be.revertedWith("Statut inchange");
    });
  });

  describe("maintenance", function () {
    const mileage = 25_000;
    let maintenanceHash;

    beforeEach(async function () {
      maintenanceHash = ethers.keccak256(
        ethers.toUtf8Bytes("invoice-hash-determined-off-chain")
      );
      await registry.setGarageCertification(garage.address, true);
      await registry.updateMileage(vehicleHash, mileage);
    });

    it("stocke exactement le hash déterministe fourni par le garage certifié", async function () {
      await expect(
        registry.connect(garage).recordMaintenance(vehicleHash, maintenanceHash, mileage)
      )
        .to.emit(registry, "MaintenanceRecorded")
        .withArgs(vehicleHash, maintenanceHash, garage.address, mileage, timestamp);

      const history = await registry.getMaintenanceHistory(vehicleHash);
      expect(history).to.have.length(1);
      expect(history[0].maintenanceHash).to.equal(maintenanceHash);
      expect(history[0].garage).to.equal(garage.address);
      expect(history[0].mileage).to.equal(mileage);
      expect(history[0].timestamp).to.be.greaterThan(0);
    });

    it("refuse un garage non certifié ou révoqué", async function () {
      await expect(
        registry.connect(outsider).recordMaintenance(vehicleHash, maintenanceHash, mileage)
      ).to.be.revertedWith("Garage non certifie");

      await registry.setGarageCertification(garage.address, false);
      await expect(
        registry.connect(garage).recordMaintenance(vehicleHash, maintenanceHash, mileage)
      ).to.be.revertedWith("Garage non certifie");
    });

    it("refuse un hash nul et un kilométrage incohérent", async function () {
      await expect(
        registry.connect(garage).recordMaintenance(vehicleHash, ethers.ZeroHash, mileage)
      ).to.be.revertedWith("Hash maintenance invalide");
      await expect(
        registry.connect(garage).recordMaintenance(vehicleHash, maintenanceHash, mileage - 1)
      ).to.be.revertedWith("Kilometrage incoherent");
    });

    it("conserve un historique ordonné et immuable", async function () {
      await registry.connect(garage).recordMaintenance(vehicleHash, maintenanceHash, mileage);
      await registry.connect(garage).updateMileage(vehicleHash, mileage + 100);
      const secondHash = ethers.keccak256(ethers.toUtf8Bytes("second-proof"));
      await registry.connect(garage).recordMaintenance(vehicleHash, secondHash, mileage + 100);

      const history = await registry.getMaintenanceHistory(vehicleHash);
      expect(history.map((entry) => entry.maintenanceHash)).to.deep.equal([
        maintenanceHash,
        secondHash,
      ]);
    });
  });

  describe("vente en deux transactions", function () {
    it("exige une proposition du propriétaire ou de l'administrateur", async function () {
      await expect(registry.connect(outsider).proposeTransfer(vehicleHash, buyer.address))
        .to.be.revertedWith("Non autorise");

      await expect(registry.proposeTransfer(vehicleHash, buyer.address))
        .to.emit(registry, "TransferProposed")
        .withArgs(vehicleHash, admin.address, buyer.address, admin.address, timestamp);

      const pending = await registry.pendingTransfers(vehicleHash);
      expect(pending.buyer).to.equal(buyer.address);
      expect(pending.proposer).to.equal(admin.address);
      expect(pending.proposedAt).to.be.greaterThan(0);
    });

    it("refuse adresse nulle, propriétaire courant et proposition concurrente", async function () {
      await expect(registry.proposeTransfer(vehicleHash, ethers.ZeroAddress))
        .to.be.revertedWith("Acheteur invalide");
      await expect(registry.proposeTransfer(vehicleHash, admin.address))
        .to.be.revertedWith("Acheteur invalide");

      await registry.proposeTransfer(vehicleHash, buyer.address);
      await expect(registry.proposeTransfer(vehicleHash, owner.address))
        .to.be.revertedWith("Transfert deja propose");
    });

    it("seul le wallet acheteur exact accepte et devient propriétaire", async function () {
      await registry.assignDriver(vehicleHash, driver.address);
      await registry.proposeTransfer(vehicleHash, buyer.address);

      await expect(registry.connect(outsider).acceptTransfer(vehicleHash))
        .to.be.revertedWith("Reserve a l'acheteur");
      await expect(registry.connect(buyer).acceptTransfer(vehicleHash))
        .to.emit(registry, "TransferAccepted")
        .withArgs(vehicleHash, admin.address, buyer.address, timestamp);

      const vehicle = await registry.getVehicle(vehicleHash);
      expect(vehicle.currentOwner).to.equal(buyer.address);
      expect(vehicle.assignedDriver).to.equal(ethers.ZeroAddress);
      expect((await registry.pendingTransfers(vehicleHash)).buyer).to.equal(ethers.ZeroAddress);
    });

    it("empêche une acceptation sans proposition ou une seconde acceptation", async function () {
      await expect(registry.connect(buyer).acceptTransfer(vehicleHash))
        .to.be.revertedWith("Aucun transfert en attente");
      await transferTo(buyer);
      await expect(registry.connect(buyer).acceptTransfer(vehicleHash))
        .to.be.revertedWith("Aucun transfert en attente");
    });

    it("permet au vendeur, à l'admin ou à l'acheteur d'annuler", async function () {
      await transferTo(owner);

      for (const actor of [owner, admin, buyer]) {
        await registry.connect(owner).proposeTransfer(vehicleHash, buyer.address);
        await expect(registry.connect(actor).cancelTransfer(vehicleHash))
          .to.emit(registry, "TransferCancelled")
          .withArgs(vehicleHash, buyer.address, actor.address, timestamp);
      }
    });

    it("refuse l'annulation par un tiers et sans proposition", async function () {
      await expect(registry.cancelTransfer(vehicleHash))
        .to.be.revertedWith("Aucun transfert en attente");
      await registry.proposeTransfer(vehicleHash, buyer.address);
      await expect(registry.connect(outsider).cancelTransfer(vehicleHash))
        .to.be.revertedWith("Non autorise");
    });

    it("n'expose aucune fonction de transfert direct", async function () {
      expect(registry.interface.hasFunction("transferOwnership(bytes32,address)")).to.equal(false);
    });
  });
});
