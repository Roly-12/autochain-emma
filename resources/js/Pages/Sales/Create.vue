<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({ vehicles: Array, buyers: Array });

const form = useForm({
    vehicle_id: '',
    buyer_id: '',
    buyer_wallet: '',
    notes: '',
});
</script>

<template>
  <Head title="Nouvelle vente" />
  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <h2 class="text-2xl font-semibold text-slate-900">Initier une vente (signature admin)</h2>
        <Link :href="route('sales.index')" class="rounded-full border border-slate-200 px-4 py-2 text-sm font-semibold">Retour</Link>
      </div>
    </template>

    <div class="mx-auto max-w-3xl px-4 py-8">
      <form class="space-y-4 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm" @submit.prevent="form.post(route('sales.store'))">
        <div>
          <label class="mb-1 block text-sm font-medium">Véhicule</label>
          <select v-model="form.vehicle_id" class="w-full rounded-2xl border border-slate-200 px-4 py-2">
            <option value="">Sélectionner</option>
            <option v-for="v in vehicles" :key="v.id" :value="v.id">{{ v.license_plate }}</option>
          </select>
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium">Acheteur</label>
          <select v-model="form.buyer_id" class="w-full rounded-2xl border border-slate-200 px-4 py-2">
            <option value="">Sélectionner</option>
            <option v-for="b in buyers" :key="b.id" :value="b.id">{{ b.name }} ({{ b.email }})</option>
          </select>
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium">Wallet acheteur</label>
          <input v-model="form.buyer_wallet" class="w-full rounded-2xl border border-slate-200 px-4 py-2" placeholder="0x..." />
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium">Notes</label>
          <textarea v-model="form.notes" rows="3" class="w-full rounded-2xl border border-slate-200 px-4 py-2" />
        </div>
        <button class="rounded-full bg-indigo-600 px-6 py-2 font-semibold text-white">Proposer la vente</button>
      </form>
    </div>
  </AuthenticatedLayout>
</template>
