<script setup>
import GuestLayout from '@/Layouts/GuestLayout.vue';
import { Head } from '@inertiajs/vue3';
</script>

<template>
  <Head title="Contact" />
  <GuestLayout>
    <section class="mx-auto max-w-5xl px-4 py-20 sm:px-6 lg:px-8">
      <div class="rounded-3xl border border-white/10 bg-white/10 p-8 shadow-2xl backdrop-blur">
        <h1 class="text-3xl font-semibold text-white">Contactez-nous</h1>
        <p class="mt-4 text-lg leading-8 text-slate-300">Pour une démo, un accompagnement ou un déploiement sur mesure, nous sommes à votre disposition.</p>
        <div class="mt-8 space-y-3 text-slate-200">
          <p><span class="font-semibold text-white">Email :</span> contact@autochain-emma.com</p>
          <p><span class="font-semibold text-white">Téléphone :</span> <a href="tel:+242068781844" class="hover:text-indigo-300">+242 06 878 18 44</a></p>
        </div>
      </div>
    </section>
  </GuestLayout>
</template>
