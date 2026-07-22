<script setup>
import { Head, useForm } from '@inertiajs/vue3';

const props = defineProps({ school: Object });
const form = useForm({ email: '', password: '', remember: false });
const submit = () => form.post(`/ecole/${props.school.slug}/connexion`);
</script>

<template>
    <Head :title="`Connexion élève — ${school.name}`" />
    <main class="grid min-h-screen bg-[#fbfaf6] lg:grid-cols-2">
        <section class="relative hidden overflow-hidden bg-ink lg:block">
            <img src="https://images.unsplash.com/photo-1504609813442-a8924e83f76e?auto=format&fit=crop&w=1200&q=90" alt="Cours de danse" class="absolute inset-0 h-full w-full object-cover opacity-55" />
            <div class="absolute inset-0 bg-gradient-to-t from-black/85 via-black/20 to-transparent"></div>
            <div class="absolute bottom-14 left-14 max-w-lg text-white">
                <p class="mb-4 text-xs font-bold uppercase tracking-[.24em] text-coral">Espace élève</p>
                <h2 class="font-serif text-5xl leading-tight">Retrouvez vos cours et vos factures au même endroit.</h2>
            </div>
        </section>
        <section class="flex items-center justify-center p-6 sm:p-12">
            <div class="w-full max-w-md">
                <a :href="`/ecole/${school.slug}`" class="mb-14 inline-flex items-center gap-3">
                    <span class="grid h-10 w-10 place-items-center rounded-full bg-ink text-xl text-white">♪</span>
                    <span class="font-serif text-2xl font-semibold">{{ school.name }}</span>
                </a>
                <p class="text-xs font-bold uppercase tracking-[.2em] text-coral">Espace élève</p>
                <h1 class="mt-2 font-serif text-4xl">Bienvenue.</h1>
                <p class="mt-3 text-black/50">Utilisez les identifiants reçus par e-mail lors de votre inscription.</p>
                <form @submit.prevent="submit" class="mt-9 space-y-5">
                    <label class="block text-sm font-semibold">Adresse e-mail<input v-model="form.email" type="email" autofocus autocomplete="email" class="mt-2 w-full rounded-2xl border border-black/10 bg-white px-4 py-3.5 font-normal focus:border-coral" /></label>
                    <label class="block text-sm font-semibold">Mot de passe<input v-model="form.password" type="password" autocomplete="current-password" class="mt-2 w-full rounded-2xl border border-black/10 bg-white px-4 py-3.5 font-normal focus:border-coral" /></label>
                    <p v-if="form.errors.email" class="rounded-xl bg-red-50 p-3 text-sm text-red-600">{{ form.errors.email }}</p>
                    <label class="flex items-center gap-3 text-sm text-black/60"><input v-model="form.remember" type="checkbox" class="h-4 w-4 accent-coral" /> Rester connecté</label>
                    <button :disabled="form.processing" class="w-full rounded-full bg-ink py-4 font-bold text-white transition hover:bg-coral disabled:opacity-50">{{ form.processing ? 'Connexion…' : 'Se connecter' }}</button>
                </form>
                <a href="/mot-de-passe-oublie" class="mt-5 block text-center text-sm font-semibold text-coral">Mot de passe oublié ?</a>
                <a :href="`/ecole/${school.slug}`" class="mt-7 block text-center text-sm font-semibold text-black/45">← Retour aux cours</a>
            </div>
        </section>
    </main>
</template>
