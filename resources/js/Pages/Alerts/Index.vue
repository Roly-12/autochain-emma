<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps({ alerts: Object, filters: Object });
const canResolve = computed(() => usePage().props.permissions?.manage_fleet);

const resolve = (id) => router.post(route('alerts.resolve', id));
</script>

<template>
  <Head title="Alertes" />
  <AuthenticatedLayout>
    <template #header>
      <div>
        <p class="text-sm font-semibold uppercase tracking-[0.25em] text-indigo-600">Moteur d'alertes</p>
        <h2 class="text-2xl font-semibold text-slate-900">CT · Assurance · Entretien</h2>
      </div>
    </template>

    <div class="mx-auto max-w-5xl px-4 py-8 space-y-4">
      <div v-for="alert in alerts.data" :key="alert.id" class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-3">
          <div>
            <p class="font-semibold text-slate-900">{{ alert.title }}</p>
            <p class="mt-1 text-sm text-slate-600">{{ alert.message }}</p>
            <p class="mt-2 text-xs text-slate-500">{{ alert.vehicle?.license_plate }} · échéance {{ alert.due_date || '—' }}</p>
          </div>
          <div class="flex items-center gap-2">
            <span class="rounded-full px-3 py-1 text-xs font-medium"
              :class="{
                'bg-red-50 text-red-700': alert.severity === 'critical',
                'bg-amber-50 text-amber-700': alert.severity === 'warning',
                'bg-slate-100 text-slate-600': alert.severity === 'info',
              }"
            >{{ alert.severity }}</span>
            <button v-if="canResolve && !alert.resolved_at" @click="resolve(alert.id)" class="rounded-full border border-slate-200 px-3 py-1 text-xs font-semibold">Résoudre</button>
          </div>
        </div>
      </div>
      <p v-if="!alerts.data?.length" class="text-sm text-slate-500">Aucune alerte. Lancez <code>php artisan fleet:generate-alerts</code>.</p>
    </div>
  </AuthenticatedLayout>
</template>
