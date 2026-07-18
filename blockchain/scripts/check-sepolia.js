import dotenv from "dotenv";
import { formatEther, JsonRpcProvider, Wallet } from "ethers";

dotenv.config();

const EXPECTED_CHAIN_ID = 11155111n;
const timeout = setTimeout(() => {
  console.error("Délai RPC Sepolia dépassé.");
  process.exit(4);
}, 15_000);

try {
  if (!process.env.SEPOLIA_RPC_URL || !process.env.DEPLOYER_PRIVATE_KEY) {
    throw new Error("SEPOLIA_RPC_URL ou DEPLOYER_PRIVATE_KEY manque dans blockchain/.env.");
  }

  const provider = new JsonRpcProvider(process.env.SEPOLIA_RPC_URL);
  const wallet = new Wallet(process.env.DEPLOYER_PRIVATE_KEY, provider);
  const [network, balance] = await Promise.all([
    provider.getNetwork(),
    provider.getBalance(wallet.address),
  ]);

  if (network.chainId !== EXPECTED_CHAIN_ID) {
    throw new Error(`Chain ID ${network.chainId} reçu au lieu de ${EXPECTED_CHAIN_ID}.`);
  }

  console.log(`Adresse: ${wallet.address}`);
  console.log(`Chain ID: ${network.chainId}`);
  console.log(`Solde: ${formatEther(balance)} SepoliaETH`);

  await provider.destroy();
  clearTimeout(timeout);
  process.exit(0);
} catch (error) {
  clearTimeout(timeout);
  console.error(error instanceof Error ? error.message : String(error));
  process.exit(1);
}
