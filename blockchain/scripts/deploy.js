import hre from "hardhat";
import fs from "fs";

async function main() {
  const [deployer] = await hre.ethers.getSigners();
  const VehicleRegistry = await hre.ethers.getContractFactory("VehicleRegistry");
  const vehicleRegistry = await VehicleRegistry.deploy();

  const deploymentTransaction = vehicleRegistry.deploymentTransaction();
  if (!deploymentTransaction) {
    throw new Error("Transaction de déploiement introuvable.");
  }

  const receipt = await deploymentTransaction.wait();

  const address = await vehicleRegistry.getAddress();
  console.log("✅ VehicleRegistry déployé à l'adresse :", address);

  const deploymentInfo = {
    address: address,
    network: hre.network.name,
    chainId: Number((await hre.ethers.provider.getNetwork()).chainId),
    deployer: deployer.address,
    transactionHash: deploymentTransaction.hash,
    blockNumber: receipt.blockNumber,
    timestamp: new Date().toISOString(),
    abi: JSON.parse(
      fs.readFileSync(
        "./artifacts/contracts/VehicleRegistry.sol/VehicleRegistry.json",
        "utf8"
      )
    ).abi
  };

  fs.writeFileSync(
    "./deployment.json",
    JSON.stringify(deploymentInfo, null, 2)
  );

  console.log("🔗 Transaction :", deploymentTransaction.hash);
  console.log("🧱 Bloc :", receipt.blockNumber);
  console.log("📄 Informations de déploiement sauvegardées dans deployment.json");
}

main().catch((error) => {
  console.error(error);
  process.exitCode = 1;
});