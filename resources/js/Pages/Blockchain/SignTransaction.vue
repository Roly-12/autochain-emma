<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { useWallet } from '@/Composables/useWallet';
import { Head } from '@inertiajs/vue3';
import axios from 'axios';
import { ref } from 'vue';

const props = defineProps({
    transaction: Object,
    contract: Object,
    return_url: String,
});

const { contract: getContract, walletError } = useWallet();
const busy = ref(false);
const state = ref(props.transaction.status);
const message = ref('');

const sign = async () => {
    busy.value = true;
    message.value = 'Confirmation MetaMask en attente…';

    try {
        const instance = await getContract(
            props.contract.address,
            props.contract.abi,
            props.contract.chain_id,
            props.transaction.wallet_address,
        );
        const method = props.transaction.payload.method;
        const tx = await instance[method](...props.transaction.payload.arguments);
        state.value = 'submitted';
        message.value = `Transaction ${tx.hash} soumise. Attente du receipt…`;
        await tx.wait(1);

        const response = await axios.post(
            route('blockchain.transactions.submit', props.transaction.uuid),
            { transaction_hash: tx.hash },
        );
        state.value = response.data.status;
        message.value = response.data.message;

        if (state.value === 'confirmed') {
            window.setTimeout(() => window.location.assign(response.data.return_url), 900);
        }
    } catch (error) {
        state.value = error.response?.data?.status === 'failed' ? 'failed' : 'pending';
        message.value = error.response?.data?.message
            || error.response?.data?.errors?.transaction_hash?.[0]
            || walletError.value
            || error.shortMessage
            || error.message;
    } finally {
        busy.value = false;
    }
};

const refreshStatus = async () => {
    busy.value = true;
    try {
        const response = await axios.get(route('blockchain.transactions.status', props.transaction.uuid));
        state.value = response.data.status;
        message.value = response.data.message || 'Confirmation toujours en attente.';
        if (state.value === 'confirmed') {
            window.location.assign(response.data.return_url);
        }
    } finally {
        busy.value = false;
    }
};

const retry = async () => {
    busy.value = true;
    try {
        const response = await axios.post(route('blockchain.transactions.retry', props.transaction.uuid));
        window.location.assign(response.data.url);
    } finally {
        busy.value = false;
    }
};
</script>

<template>
  <Head title="Signature blockchain" />
  <AuthenticatedLayout>
    <template #header>
      <div>
        <p class="text-sm font-semibold uppercase tracking-[0.25em] text-indigo-600">Certification</p>
        <h2 class="text-2xl font-semibold text-slate-900">Signature MetaMask</h2>
      </div>
    </template>

    <div class="mx-auto max-w-2xl px-4 py-10">
      <div class="rounded-3xl border border-slate-200 bg-white p-8 shadow-sm">
        <div class="grid gap-4 sm:grid-cols-2">
          <div>
            <p class="text-xs uppercase tracking-wider text-slate-500">Action</p>
            <p class="mt-1 font-semibold text-slate-900">{{ transaction.action }}</p>
          </div>
          <div>
            <p class="text-xs uppercase tracking-wider text-slate-500">Réseau</p>
            <p class="mt-1 font-semibold text-slate-900">{{ contract.network }} · {{ contract.chain_id }}</p>
          </div>
          <div class="sm:col-span-2">
            <p class="text-xs uppercase tracking-wider text-slate-500">Wallet obligatoire</p>
            <p class="mt-1 break-all font-mono text-sm">{{ transaction.wallet_address }}</p>
          </div>
        </div>

        <p v-if="message" class="mt-6 rounded-2xl bg-slate-50 p-4 text-sm text-slate-700">{{ message }}</p>

        <div class="mt-6 flex gap-3">
          <button v-if="state === 'pending'" :disabled="busy" @click="sign" class="rounded-full bg-indigo-600 px-6 py-2.5 text-sm font-semibold text-white disabled:opacity-50">
            {{ busy ? 'Traitement…' : 'Signer dans MetaMask' }}
          </button>
          <button v-if="state === 'submitted'" :disabled="busy" @click="refreshStatus" class="rounded-full bg-indigo-600 px-6 py-2.5 text-sm font-semibold text-white disabled:opacity-50">
            Vérifier la confirmation
          </button>
          <button v-if="state === 'failed'" :disabled="busy" @click="retry" class="rounded-full bg-rose-600 px-6 py-2.5 text-sm font-semibold text-white disabled:opacity-50">
            Nouvelle tentative
          </button>
          <a :href="return_url" class="rounded-full border border-slate-200 px-6 py-2.5 text-sm font-semibold text-slate-700">Retour</a>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
