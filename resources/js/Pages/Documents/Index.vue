<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router } from '@inertiajs/vue3';
import { computed } from 'vue';
import { usePage } from '@inertiajs/vue3';

defineProps({
    documents: Object,
    vehicles: Array,
    filters: Object,
});

const canUpload = computed(() => usePage().props.permissions?.upload_documents);
</script>

<template>
  <Head title="Documents" />
  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <div>
          <p class="text-sm font-semibold uppercase tracking-[0.25em] text-indigo-600">Documents</p>
          <h2 class="text-2xl font-semibold text-slate-900">Gestion documentaire</h2>
        </div>
        <Link v-if="canUpload" :href="route('documents.create')" class="rounded-full bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Ajouter</Link>
      </div>
    </template>

    <div class="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
      <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm">
        <table class="min-w-full divide-y divide-slate-100 text-sm">
          <thead class="bg-slate-50 text-left text-slate-500">
            <tr>
              <th class="px-4 py-3">Titre</th>
              <th class="px-4 py-3">Type</th>
              <th class="px-4 py-3">Véhicule</th>
              <th class="px-4 py-3">Hash</th>
              <th class="px-4 py-3">IPFS</th>
              <th class="px-4 py-3"></th>
            </tr>
          </thead>
          <tbody class="divide-y divide-slate-100">
            <tr v-for="doc in documents.data" :key="doc.id">
              <td class="px-4 py-3 font-medium text-slate-900">{{ doc.title }}</td>
              <td class="px-4 py-3">{{ doc.type }}</td>
              <td class="px-4 py-3">{{ doc.vehicle?.license_plate }}</td>
              <td class="px-4 py-3 font-mono text-xs">{{ doc.content_hash?.slice(0, 14) }}…</td>
              <td class="px-4 py-3 text-xs">
                <a v-if="doc.gateway_url" :href="doc.gateway_url" target="_blank" rel="noopener noreferrer" class="text-indigo-600">
                  {{ doc.ipfs_cid.slice(0, 12) }}…
                </a>
                <span v-else>—</span>
              </td>
              <td class="px-4 py-3 text-right">
                <div class="flex justify-end gap-3">
                  <a :href="route('documents.verify', doc.id)" target="_blank" rel="noopener noreferrer" class="text-emerald-600">Vérifier</a>
                  <Link :href="route('documents.download', doc.id)" class="text-indigo-600">Télécharger</Link>
                </div>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
