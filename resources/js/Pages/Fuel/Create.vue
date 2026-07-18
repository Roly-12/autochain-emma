<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, useForm } from '@inertiajs/vue3';

defineProps({ vehicles: Array });

const form = useForm({
    vehicle_id: '',
    filled_at: new Date().toISOString().slice(0, 10),
    liters: '',
    amount: '',
    odometer: '',
    station: '',
    notes: '',
});
</script>

<template>
  <Head title="Nouveau plein" />
  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <h2 class="text-2xl font-semibold text-slate-900">Saisir un plein</h2>
        <Link :href="route('fuel.index')" class="rounded-full border border-slate-200 px-4 py-2 text-sm font-semibold">Retour</Link>
      </div>
    </template>

    <div class="mx-auto max-w-3xl px-4 py-8">
      <form class="grid gap-4 rounded-3xl border border-slate-200 bg-white p-6 shadow-sm md:grid-cols-2" @submit.prevent="form.post(route('fuel.store'))">
        <div class="md:col-span-2">
          <label class="mb-1 block text-sm font-medium">Véhicule</label>
          <select v-model="form.vehicle_id" class="w-full rounded-2xl border border-slate-200 px-4 py-2">
            <option value="">Sélectionner</option>
            <option v-for="v in vehicles" :key="v.id" :value="v.id">{{ v.license_plate }} ({{ v.last_certified_mileage ?? 0 }} km)</option>
          </select>
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium">Date</label>
          <input v-model="form.filled_at" type="date" class="w-full rounded-2xl border border-slate-200 px-4 py-2" />
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium">Litres</label>
          <input v-model="form.liters" type="number" step="0.01" class="w-full rounded-2xl border border-slate-200 px-4 py-2" />
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium">Montant (€)</label>
          <input v-model="form.amount" type="number" step="0.01" class="w-full rounded-2xl border border-slate-200 px-4 py-2" />
        </div>
        <div>
          <label class="mb-1 block text-sm font-medium">Odomètre</label>
          <input v-model="form.odometer" type="number" class="w-full rounded-2xl border border-slate-200 px-4 py-2" />
        </div>
        <div class="md:col-span-2">
          <label class="mb-1 block text-sm font-medium">Station</label>
          <input v-model="form.station" class="w-full rounded-2xl border border-slate-200 px-4 py-2" />
        </div>
        <div class="md:col-span-2">
          <button class="rounded-full bg-indigo-600 px-6 py-2 font-semibold text-white">Enregistrer</button>
        </div>
      </form>
    </div>
  </AuthenticatedLayout>
</template>
