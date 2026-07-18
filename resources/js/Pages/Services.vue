<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head } from '@inertiajs/vue3';

const services = [
  'Suivi immuable des maintenances',
  'Validation de kilométrage certifiée',
  'Historique consultable par véhicule',
  'Intégration avec partenaires garages et assureurs',
];
</script>

<template>
  <Head title="Services" />
  <GuestLayout>
    <section class="mx-auto max-w-6xl px-4 py-20 sm:px-6 lg:px-8">
      <div class="grid gap-6 lg:grid-cols-[0.9fr_1.1fr]">
        <div class="rounded-3xl border border-white/10 bg-white/10 p-8 backdrop-blur">
          <p class="text-sm font-semibold uppercase tracking-[0.3em] text-indigo-300">Nos services</p>
          <h1 class="mt-4 text-3xl font-semibold text-white">Un produit prêt à l’exploitation.</h1>
          <p class="mt-6 text-lg leading-8 text-slate-300">Chaque service est pensé pour réduire les frictions entre propriétaires, garages et structures de contrôle.</p>
        </div>
        <div class="grid gap-4">
          <div v-for="service in services" :key="service" class="rounded-3xl border border-white/10 bg-slate-900/70 p-6 text-slate-200">
            {{ service }}
          </div>
        </div>
      </div>
    </section>
  </GuestLayout>
</template>
