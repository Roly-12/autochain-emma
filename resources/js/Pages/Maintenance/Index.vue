<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

defineProps({
    records: Object,
    canCreate: Boolean,
});
</script>

<template>
  <Head title="Maintenance" />
  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <div>
          <p class="text-sm font-semibold uppercase tracking-[0.25em] text-indigo-600">Maintenance</p>
          <h2 class="text-2xl font-semibold text-slate-900">Historique des interventions</h2>
        </div>
        <Link v-if="canCreate" :href="route('maintenance.create')" class="rounded-full bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Nouvelle intervention</Link>
      </div>
    </template>

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
      <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-200">
          <thead class="bg-slate-50">
            <tr>
              <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Véhicule</th>
              <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Type</th>
              <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Date</th>
              <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Garage</th>
              <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">Statut</th>
              <th class="px-6 py-3 text-left text-xs font-semibold uppercase tracking-wider text-slate-500">On-chain</th>
              <th class="px-6 py-3"></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-200">
            <tr v-for="record in records.data" :key="record.id">
              <td class="px-6 py-4 text-sm font-medium text-slate-900">{{ record.vehicle?.license_plate ?? record.vehicle_id }}</td>
              <td class="px-6 py-4 text-sm text-slate-700">{{ record.type }}</td>
              <td class="px-6 py-4 text-sm text-slate-700">{{ record.date }}</td>
              <td class="px-6 py-4 text-sm text-slate-700">{{ record.garage }}</td>
              <td class="px-6 py-4 text-sm text-emerald-600">{{ record.status }}</td>
              <td class="px-6 py-4 text-xs font-mono text-slate-500">{{ record.transaction_hash ? 'oui' : '—' }}</td>
              <td class="px-6 py-4 text-sm text-right">
                <Link :href="route('maintenance.edit', record.id)" class="mr-2 text-indigo-600">Modifier</Link>
                <Link :href="route('maintenance.destroy', record.id)" method="delete" as="button" class="text-red-600">Supprimer</Link>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div v-if="records.links?.length" class="mt-8 flex flex-wrap items-center justify-center gap-2">
        <template v-for="link in records.links" :key="link.label">
          <Link
            v-if="link.url"
            :href="link.url"
            class="rounded-full border border-slate-200 px-4 py-2 text-sm font-medium text-slate-700 transition hover:bg-slate-50"
            :class="{'bg-indigo-600 text-white': link.active}"
            v-html="link.label"
          />
          <span v-else class="px-4 py-2 text-sm text-slate-400" v-html="link.label" />
        </template>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
