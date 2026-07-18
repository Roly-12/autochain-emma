<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
  <Head title="À propos" />
  <GuestLayout>
    <section class="mx-auto max-w-5xl px-4 py-20 sm:px-6 lg:px-8">
      <div class="rounded-3xl border border-white/10 bg-white/10 p-8 shadow-2xl backdrop-blur">
        <p class="text-sm font-semibold uppercase tracking-[0.3em] text-indigo-300">À propos d’AutoChain Emma</p>
        <h1 class="mt-4 text-3xl font-semibold text-white sm:text-4xl">Une plateforme pensée pour la confiance et la conformité.</h1>
        <p class="mt-6 text-lg leading-8 text-slate-300">AutoChain Emma permet aux acteurs du secteur automobile de valoriser chaque intervention avec une preuve vérifiable. Les données sont sécurisées, traçables et accessibles de façon claire pour les équipes internes comme les partenaires.</p>
      </div>
    </section>
  </GuestLayout>
</template>
