<script setup>
import { Link, usePage } from '@inertiajs/vue3';
import { computed } from 'vue';

const user = computed(() => usePage().props.auth.user);
const prefersDark = () => typeof window !== 'undefined' && window.matchMedia('(prefers-color-scheme: dark)').matches;
const isDark = computed(() => user.value?.theme_preference === 'dark' || (user.value?.theme_preference === 'system' && prefersDark()));
</script>

<template>
    <div :class="isDark ? 'dark' : ''">
        <div class="min-h-screen bg-slate-950 text-slate-100 transition-colors dark:bg-slate-950">
        <header class="border-b border-white/10 bg-slate-950/90 backdrop-blur">
            <div class="mx-auto flex max-w-7xl items-center justify-between px-4 py-4 sm:px-6 lg:px-8">
                <Link href="/" class="flex items-center gap-3 text-lg font-semibold">
                    <span class="flex h-10 w-10 items-center justify-center rounded-full bg-indigo-500 font-bold text-white">A</span>
                    <span>AutoChain Emma</span>
                </Link>
                <nav class="hidden items-center gap-6 text-sm md:flex">
                    <Link href="/about" class="transition hover:text-indigo-300">À propos</Link>
                    <Link href="/services" class="transition hover:text-indigo-300">Services</Link>
                    <Link href="/contact" class="transition hover:text-indigo-300">Contact</Link>
                    <Link href="/login" class="rounded-full bg-white px-4 py-2 font-semibold text-slate-900 transition hover:bg-indigo-100">Connexion</Link>
                </nav>
            </div>
        </header>

        <main>
            <slot />
        </main>
        </div>
    </div>
</template>
