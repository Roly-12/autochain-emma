<script setup>
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout.vue';
import { Head, Link, router, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

defineProps({ sales: Object });
const permissions = computed(() => usePage().props.permissions || {});
const userId = computed(() => usePage().props.auth.user?.id);

const signBuyer = (id) => router.post(route('sales.sign-buyer', id));
const cancel = (id) => router.post(route('sales.cancel', id));
</script>

<template>
  <Head title="Ventes" />
  <AuthenticatedLayout>
    <template #header>
      <div class="flex items-center justify-between">
        <div>
          <p class="text-sm font-semibold uppercase tracking-[0.25em] text-indigo-600">Double validation</p>
          <h2 class="text-2xl font-semibold text-slate-900">Ventes de véhicules</h2>
        </div>
        <Link v-if="permissions.manage_users" :href="route('sales.create')" class="rounded-full bg-indigo-600 px-4 py-2 text-sm font-semibold text-white">Initier une vente</Link>
      </div>
    </template>

    <div class="mx-auto max-w-5xl px-4 py-8 space-y-4">
      <div v-for="sale in sales.data" :key="sale.id" class="rounded-3xl border border-slate-200 bg-white p-5 shadow-sm">
        <div class="flex flex-wrap items-start justify-between gap-3">
          <div>
            <p class="font-semibold text-slate-900">{{ sale.vehicle?.license_plate }} — {{ sale.vehicle?.brand }} {{ sale.vehicle?.model }}</p>
            <p class="mt-1 text-sm text-slate-600">Acheteur : {{ sale.buyer?.name }} · Statut : {{ sale.status }}</p>
            <p class="mt-1 text-xs text-slate-500">Admin signé : {{ sale.admin_signed_at || '—' }} · Acheteur : {{ sale.buyer_signed_at || '—' }}</p>
          </div>
          <div class="flex gap-2">
            <button
              v-if="['admin_signed', 'pending'].includes(sale.status) && (sale.buyer_id === userId || permissions.role === 'auditeur')"
              @click="signBuyer(sale.id)"
              class="rounded-full bg-emerald-600 px-4 py-2 text-xs font-semibold text-white"
            >Signer (acheteur)</button>
            <button v-if="permissions.manage_users && sale.status !== 'completed' && sale.status !== 'cancelled'" @click="cancel(sale.id)" class="rounded-full border border-slate-200 px-4 py-2 text-xs font-semibold">Annuler</button>
          </div>
        </div>
      </div>
    </div>
  </AuthenticatedLayout>
</template>
