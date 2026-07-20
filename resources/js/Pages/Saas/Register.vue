<script setup>
import { Head, useForm } from '@inertiajs/vue3';

const form = useForm({ school_name: '', name: '', email: '', city: '', password: '', password_confirmation: '' });
const submit = () => form.post('/creer-mon-ecole');
</script>

<template>
    <Head title="Créer mon école" />
    <main class="min-h-screen bg-[#f5f3ee] px-5 py-10">
        <div class="mx-auto max-w-2xl">
            <a href="/" class="mb-10 inline-flex items-center gap-3"><span class="grid h-10 w-10 place-items-center rounded-full bg-ink text-white">♪</span><span class="font-serif text-2xl font-semibold">tempo<span class="text-coral">.</span></span></a>
            <div class="rounded-[2rem] bg-white p-6 soft-shadow sm:p-10">
                <p class="text-xs font-bold uppercase tracking-[.2em] text-coral">Essai gratuit</p><h1 class="mt-2 font-serif text-4xl sm:text-5xl">Créez votre école.</h1><p class="mt-3 text-black/50">Votre espace administrateur et votre page publique seront prêts immédiatement.</p>
                <form @submit.prevent="submit" class="mt-9 space-y-5">
                    <label class="block text-sm font-semibold">Nom de l’école<input v-model="form.school_name" autofocus class="mt-2 w-full rounded-xl border border-black/10 px-4 py-3 font-normal focus:border-coral" placeholder="Studio Étoile" /><span v-if="form.errors.school_name" class="mt-1 block text-xs text-coral">{{ form.errors.school_name }}</span></label>
                    <div class="grid gap-4 sm:grid-cols-2"><label class="text-sm font-semibold">Votre nom<input v-model="form.name" autocomplete="name" class="mt-2 w-full rounded-xl border border-black/10 px-4 py-3 font-normal" placeholder="Marie Dupont" /></label><label class="text-sm font-semibold">Ville<input v-model="form.city" class="mt-2 w-full rounded-xl border border-black/10 px-4 py-3 font-normal" placeholder="Lausanne" /></label></div>
                    <label class="block text-sm font-semibold">E-mail professionnel<input v-model="form.email" type="email" autocomplete="email" class="mt-2 w-full rounded-xl border border-black/10 px-4 py-3 font-normal" placeholder="bonjour@studio.fr" /><span v-if="form.errors.email" class="mt-1 block text-xs text-coral">{{ form.errors.email }}</span></label>
                    <div class="grid gap-4 sm:grid-cols-2"><label class="text-sm font-semibold">Mot de passe<input v-model="form.password" type="password" autocomplete="new-password" class="mt-2 w-full rounded-xl border border-black/10 px-4 py-3 font-normal" /></label><label class="text-sm font-semibold">Confirmation<input v-model="form.password_confirmation" type="password" autocomplete="new-password" class="mt-2 w-full rounded-xl border border-black/10 px-4 py-3 font-normal" /></label></div>
                    <p v-if="form.errors.password" class="text-xs text-coral">{{ form.errors.password }}</p><button :disabled="form.processing" class="w-full rounded-full bg-ink py-4 font-bold text-white hover:bg-coral disabled:opacity-50">{{ form.processing ? 'Création…' : 'Créer mon espace école →' }}</button>
                </form>
                <p class="mt-6 text-center text-sm text-black/45">Déjà inscrit ? <a href="/admin/connexion" class="font-bold text-ink">Se connecter</a></p>
            </div>
        </div>
    </main>
</template>
