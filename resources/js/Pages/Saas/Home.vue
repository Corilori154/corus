<script setup>
import { Head } from '@inertiajs/vue3';
</script>

<template>
    <Head title="Tempo — Le logiciel des écoles de danse" />
    <div class="min-h-screen bg-[#fbfaf6] text-ink">
        <header class="border-b border-black/5 bg-white/80 backdrop-blur-xl">
            <nav class="mx-auto flex h-20 max-w-7xl items-center justify-between px-5 lg:px-8">
                <a href="/" class="flex items-center gap-3"><span class="grid h-10 w-10 place-items-center rounded-full bg-ink text-white">♪</span><span class="font-serif text-2xl font-semibold">tempo<span class="text-coral">.</span></span></a>
                <div class="flex items-center gap-3"><a href="/admin/connexion" class="hidden px-4 py-2 text-sm font-semibold sm:block">Se connecter</a><a href="/creer-mon-ecole" class="rounded-full bg-ink px-5 py-3 text-sm font-bold text-white hover:bg-coral">Créer mon école</a></div>
            </nav>
        </header>
        <main>
            <section class="relative overflow-hidden px-5 py-20 lg:py-32">
                <div class="absolute -right-20 top-0 h-96 w-96 rounded-full bg-[#f2d3d4] blur-3xl"></div>
                <div class="relative mx-auto grid max-w-7xl items-center gap-14 lg:grid-cols-[1.05fr_.95fr] lg:px-3">
                    <div><p class="mb-5 text-xs font-bold uppercase tracking-[.22em] text-coral">La gestion, au rythme de votre studio</p><h1 class="font-serif text-5xl leading-[.98] sm:text-7xl">Votre école mérite plus qu’un <em class="font-normal text-coral">tableur.</em></h1><p class="mt-7 max-w-xl text-lg leading-relaxed text-black/55">Cours, inscriptions et espace administrateur réunis dans une plateforme élégante, pensée pour toutes les écoles de danse.</p><div class="mt-9 flex flex-wrap gap-3"><a href="/creer-mon-ecole" class="rounded-full bg-ink px-7 py-4 font-bold text-white hover:bg-coral">Démarrer gratuitement →</a><a href="/ecole/tempo" class="rounded-full border border-black/10 bg-white px-7 py-4 font-bold hover:border-coral">Voir une page école</a></div><p class="mt-4 text-xs text-black/40">Sans carte bancaire · Configuration en 2 minutes</p></div>
                    <div class="relative"><div class="rotate-2 overflow-hidden rounded-[2rem] bg-white p-3 soft-shadow"><img src="https://images.unsplash.com/photo-1504609813442-a8924e83f76e?auto=format&fit=crop&w=1200&q=90" alt="Cours de danse" class="h-[480px] w-full rounded-[1.5rem] object-cover" /></div><div class="absolute -bottom-5 -left-5 rounded-2xl bg-white p-5 soft-shadow"><strong class="font-serif text-3xl">+240</strong><p class="text-xs text-black/45">élèves inscrits</p></div></div>
                </div>
            </section>
            <section class="bg-ink px-5 py-16 text-white"><div class="mx-auto grid max-w-7xl gap-8 text-center sm:grid-cols-3"><div><strong class="font-serif text-4xl">1 espace</strong><p class="mt-2 text-sm text-white/50">entièrement dédié à votre école</p></div><div><strong class="font-serif text-4xl">24 / 7</strong><p class="mt-2 text-sm text-white/50">inscriptions ouvertes en ligne</p></div><div><strong class="font-serif text-4xl">100 %</strong><p class="mt-2 text-sm text-white/50">de vos données isolées</p></div></div></section>
        </main>
    </div>
</template>
