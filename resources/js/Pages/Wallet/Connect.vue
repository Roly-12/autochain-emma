<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useWallet } from '@/Composables/useWallet';
import { Head, router } from '@inertiajs/vue3';
import axios from 'axios';
import { ref } from 'vue';

const props = defineProps({
    wallet: Object,
    chain: Object,
});

const { signMessage, walletError } = useWallet();
const busy = ref(false);
const message = ref('');

const linkWallet = async () => {
    busy.value = true;
    message.value = '';

    try {
        const challenge = await axios.post(route('wallet.challenge'));
        const signed = await signMessage(challenge.data.message, props.chain.id);
        const response = await axios.post(route('wallet.verify'), signed);
        message.value = response.data.message;
        router.reload({ only: ['wallet', 'auth'] });
    } catch (error) {
        message.value = error.response?.data?.message || walletError.value || error.message;
    } finally {
        busy.value = false;
    }
};

const disconnect = async () => {
    busy.value = true;
    try {
        await axios.delete(route('wallet.disconnect'));
        router.reload({ only: ['wallet', 'auth'] });
    } finally {
        busy.value = false;
    }
};
</script>

<template>
  <Head title="Wallet MetaMask" />
  <AuthenticatedLayout>
    <template #header>
      <div>
        <p class="text-sm font-semibold uppercase tracking-[0.25em] text-indigo-600">Identité Web3</p>
        <h2 class="text-2xl font-semibold text-slate-900">Wallet MetaMask</h2>
      </div>
    </template>

    <div class="mx-auto max-w-2xl px-4 py-10">
      <div class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
        <p class="text-sm text-slate-600">
          La liaison est validée par une signature unique, valable cinq minutes. Aucune clé privée n’est transmise.
        </p>

        <div class="mt-6 rounded-2xl bg-slate-50 p-4">
          <p class="text-xs uppercase tracking-wider text-slate-500">Wallet lié</p>
          <p class="mt-1 break-all font-mono text-sm text-slate-900">{{ wallet.address || 'Aucun' }}</p>
          <p v-if="wallet.verified_at" class="mt-1 text-xs text-emerald-600">Vérifié le {{ new Date(wallet.verified_at).toLocaleString('fr-FR') }}</p>
        </div>

        <p class="mt-4 text-xs text-slate-500">
          Réseau attendu : {{ chain.name }} (chain ID {{ chain.id }})
        </p>
        <p v-if="message" class="mt-4 text-sm text-indigo-700">{{ message }}</p>

        <div class="mt-6 flex gap-3">
          <button :disabled="busy" @click="linkWallet" class="rounded-full bg-indigo-600 px-5 py-2.5 text-sm font-semibold text-white disabled:opacity-50">
            {{ busy ? 'Signature…' : wallet.address ? 'Remplacer le wallet' : 'Lier MetaMask' }}
          </button>
          <button v-if="wallet.address" :disabled="busy" @click="disconnect" class="rounded-full border border-rose-200 px-5 py-2.5 text-sm font-semibold text-rose-700">
            Dissocier
          </button>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
