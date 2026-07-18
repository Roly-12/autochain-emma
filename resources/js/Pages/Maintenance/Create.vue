<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, useForm } from '@inertiajs/vue3';

defineProps({ vehicles: Array });

const form = useForm({
    vehicle_id: '',
    type: '',
    garage: '',
    date: new Date().toISOString().slice(0, 10),
    details: '',
    mileage: '',
    parts_changed: '',
});
</script>

<template>
  <Head title="Créer une intervention" />
  <AuthenticatedLayout>
    <template #header>
      <div>
        <p class="text-sm font-semibold uppercase tracking-[0.25em] text-indigo-600">Maintenance</p>
        <h2 class="text-2xl font-semibold text-slate-900">Nouvelle intervention</h2>
      </div>
    </template>

    <div class="mx-auto max-w-4xl px-4 py-8 sm:px-6 lg:px-8">
      <div class="rounded-3xl border border-slate-200 bg-white p-6 shadow-sm">
        <form @submit.prevent="form.post(route('maintenance.store'))" class="grid gap-4 md:grid-cols-2">
          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-slate-700">Véhicule</label>
            <select v-model="form.vehicle_id" class="mt-1 block w-full rounded-2xl border border-slate-200 px-4 py-2">
              <option value="">Sélectionner</option>
              <option v-for="v in vehicles" :key="v.id" :value="v.id">{{ v.license_plate }} — {{ v.brand }} {{ v.model }}</option>
            </select>
            <p class="text-sm text-red-600" v-if="form.errors.vehicle_id">{{ form.errors.vehicle_id }}</p>
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700">Type</label>
            <input v-model="form.type" class="mt-1 block w-full rounded-2xl border border-slate-200 px-4 py-2" placeholder="Vidange" />
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700">Garage</label>
            <input v-model="form.garage" class="mt-1 block w-full rounded-2xl border border-slate-200 px-4 py-2" />
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700">Date</label>
            <input type="date" v-model="form.date" class="mt-1 block w-full rounded-2xl border border-slate-200 px-4 py-2" />
          </div>
          <div>
            <label class="block text-sm font-medium text-slate-700">Kilométrage</label>
            <input type="number" v-model="form.mileage" class="mt-1 block w-full rounded-2xl border border-slate-200 px-4 py-2" />
          </div>
          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-slate-700">Pièces changées</label>
            <input v-model="form.parts_changed" class="mt-1 block w-full rounded-2xl border border-slate-200 px-4 py-2" />
          </div>
          <div class="md:col-span-2">
            <label class="block text-sm font-medium text-slate-700">Détails</label>
            <textarea v-model="form.details" class="mt-1 block w-full rounded-2xl border border-slate-200 px-4 py-2" rows="4" />
          </div>
          <p class="text-sm text-slate-500 md:col-span-2">La certification sera finalisée par une signature MetaMask du garagiste.</p>
          <div class="md:col-span-2 flex justify-end">
            <button :disabled="form.processing" class="rounded-full bg-indigo-600 px-6 py-2 text-white">Créer</button>
          </div>
        </form>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
