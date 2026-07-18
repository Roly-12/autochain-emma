<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

defineProps({ logs: Object, vehicles: Array, filters: Object });
const canCreate = computed(() => usePage().props.permissions?.report_mileage);
</script>

<template>
  <Head title="Carburant" />
  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <div>
          <p class="text-sm font-semibold uppercase tracking-[0.25em] text-indigo-600">Consommation</p>
          <h2 class="text-2xl font-semibold text-slate-900">Suivi carburant</h2>
        </div>
        <Link v-if="canCreate" :href="route('fuel.create')" class="rounded-full bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Nouveau plein</Link>
      </div>
    </template>

    <div class="mx-auto max-w-7xl px-4 py-8">
      <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-100 text-sm">
          <thead class="bg-slate-50 text-left text-slate-500">
            <tr>
              <th class="px-4 py-3">Date</th>
              <th class="px-4 py-3">Véhicule</th>
              <th class="px-4 py-3">Litres</th>
              <th class="px-4 py-3">Montant</th>
              <th class="px-4 py-3">Odomètre</th>
              <th class="px-4 py-3">Station</th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="log in logs.data" :key="log.id">
              <td class="px-4 py-3">{{ log.filled_at }}</td>
              <td class="px-4 py-3">{{ log.vehicle?.license_plate }}</td>
              <td class="px-4 py-3">{{ log.liters }} L</td>
              <td class="px-4 py-3">{{ log.amount ? log.amount + ' €' : '—' }}</td>
              <td class="px-4 py-3">{{ log.odometer }} km</td>
              <td class="px-4 py-3">{{ log.station || '—' }}</td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
