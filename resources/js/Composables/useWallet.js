import { BrowserProvider, Contract, getAddress } from 'ethers';
import { ref } from 'vue';

export const walletAddress = ref(null);
export const walletChainId = ref(null);
export const walletError = ref(null);

export function useWallet() {
    const selectMetaMaskProvider = async () => {
        const announced = [];
        const onAnnounce = (event) => {
            const info = event.detail?.info;
            if (info?.rdns === 'io.metamask' || info?.name?.toLowerCase() === 'metamask') {
                announced.push(event.detail.provider);
            }
        };

        window.addEventListener('eip6963:announceProvider', onAnnounce);
        window.dispatchEvent(new Event('eip6963:requestProvider'));
        await new Promise((resolve) => window.setTimeout(resolve, 200));
        window.removeEventListener('eip6963:announceProvider', onAnnounce);

        if (announced[0]) {
            return announced[0];
        }

        const providers = window.ethereum?.providers ?? (window.ethereum ? [window.ethereum] : []);
        const metamask = providers.find(
            (item) => item.isMetaMask && !item.isTrust && !item.isTrustWallet,
        );

        if (!metamask) {
            const trustWalletDetected = providers.some((item) => item.isTrust || item.isTrustWallet);
            throw new Error(
                trustWalletDetected
                    ? 'Trust Wallet intercepte la connexion. Désactivez temporairement Trust Wallet, puis rechargez la page pour utiliser MetaMask.'
                    : 'MetaMask n’est pas installé ou n’est pas détecté.',
            );
        }

        return metamask;
    };

    const provider = async () => {
        return new BrowserProvider(await selectMetaMaskProvider());
    };

    const connect = async (expectedChainId = null) => {
        walletError.value = null;

        try {
            const web3 = await provider();
            await web3.send('eth_requestAccounts', []);
            const signer = await web3.getSigner();
            walletAddress.value = getAddress(await signer.getAddress());
            walletChainId.value = Number((await web3.getNetwork()).chainId);

            if (expectedChainId && walletChainId.value !== Number(expectedChainId)) {
                throw new Error(`Réseau incorrect : chain ID ${walletChainId.value}, attendu ${expectedChainId}.`);
            }

            return { web3, signer, address: walletAddress.value, chainId: walletChainId.value };
        } catch (error) {
            walletError.value = error?.info?.error?.message
                || error?.error?.message
                || error?.data?.message
                || error?.shortMessage
                || error?.message
                || 'Connexion MetaMask impossible.';
            throw error;
        }
    };

    const signMessage = async (message, expectedChainId = null) => {
        const { signer, address } = await connect(expectedChainId);
        const signature = await signer.signMessage(message);

        return { address, signature };
    };

    const contract = async (address, abi, expectedChainId, expectedWalletAddress = null) => {
        const { signer, address: connectedAddress } = await connect(expectedChainId);
        if (expectedWalletAddress
            && connectedAddress.toLowerCase() !== expectedWalletAddress.toLowerCase()
        ) {
            throw new Error(
                `Mauvais compte MetaMask : sélectionnez ${expectedWalletAddress}.`,
            );
        }
        return new Contract(address, abi, signer);
    };

    return { connect, signMessage, contract, walletAddress, walletChainId, walletError };
}
