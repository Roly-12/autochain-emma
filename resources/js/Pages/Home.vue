<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head, Link } from '@inertiajs/vue3';

const features = [
  { title: 'Traçabilité immuable', text: 'Chaque entretien et kilométrage est enregistré de façon certifiée.' },
  { title: 'Confiance assurance', text: 'Fournissez à vos clients des preuves d’historique vérifiables.' },
  { title: 'Opérations simplifiées', text: 'Un tableau de bord unique pour gestion, audit et conformité.' },
];
</script>

<template>
  <Head title="Accueil" />
  <GuestLayout>
    <section class="relative overflow-hidden">
      <div class="mx-auto grid max-w-7xl gap-10 px-4 py-20 sm:px-6 lg:grid-cols-[1.1fr_0.9fr] lg:px-8 lg:py-28">
        <div class="max-w-2xl">
          <p class="mb-4 inline-flex rounded-full border border-indigo-400/30 bg-indigo-500/10 px-3 py-1 text-sm font-medium text-indigo-300">Plateforme de traçabilité véhicule</p>
          <h1 class="text-4xl font-semibold tracking-tight text-white sm:text-5xl">Sécurisez l’histoire de chaque véhicule.</h1>
          <p class="mt-6 text-lg leading-8 text-slate-300">AutoChain Emma centralise les informations de maintenance, les preuves de kilométrage et les attestations de garages certifiés grâce à la blockchain.</p>
          <div class="mt-8 flex flex-col gap-3 sm:flex-row">
            <Link href="/register" class="rounded-full bg-indigo-500 px-6 py-3 text-center font-semibold text-white transition hover:bg-indigo-400">Créer un compte</Link>
            <Link href="/dashboard" class="rounded-full border border-white/20 px-6 py-3 text-center font-semibold text-slate-100 transition hover:bg-white/10">Voir le tableau de bord</Link>
          </div>
        </div>
        <div class="rounded-3xl border border-white/10 bg-white/10 p-6 shadow-2xl backdrop-blur">
          <div class="rounded-2xl bg-slate-900/70 p-6">
            <p class="text-sm uppercase tracking-[0.3em] text-indigo-300">État du réseau</p>
            <div class="mt-5 space-y-4">
              <div class="flex items-center justify-between rounded-2xl border border-emerald-500/20 bg-emerald-500/10 px-4 py-3">
                <span class="text-sm text-emerald-200">Historique certifié</span>
                <span class="font-semibold text-emerald-300">Actif</span>
              </div>
              <div class="flex items-center justify-between rounded-2xl border border-white/10 bg-white/5 px-4 py-3">
                <span class="text-sm text-slate-300">Garages partenaires</span>
                <span class="font-semibold text-white">19</span>
              </div>
              <div class="flex items-center justify-between rounded-2xl border border-white/10 bg-white/5 px-4 py-3">
                <span class="text-sm text-slate-300">Véhicules suivis</span>
                <span class="font-semibold text-white">128</span>
              </div>
            </div>
          </div>
        </div>
      </div>
    </section>

    <section class="mx-auto max-w-7xl px-4 pb-20 sm:px-6 lg:px-8">
      <div class="grid gap-6 md:grid-cols-3">
        <div v-for="feature in features" :key="feature.title" class="rounded-3xl border border-white/10 bg-white/5 p-6 backdrop-blur">
          <h3 class="text-xl font-semibold text-white">{{ feature.title }}</h3>
          <p class="mt-3 text-sm leading-7 text-slate-300">{{ feature.text }}</p>
        </div>
      </div>
    </section>
  </GuestLayout>
</template>
