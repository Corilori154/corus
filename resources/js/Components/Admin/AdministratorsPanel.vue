<script setup>
import { router, useForm } from '@inertiajs/vue3';

const props = defineProps({ administrators: Array, currentUser: Object });

const createForm = useForm({ name: '', email: '', password: '', password_confirmation: '' });
const profileForm = useForm({ name: props.currentUser.name, email: props.currentUser.email });
const passwordForm = useForm({ current_password: '', password: '', password_confirmation: '' });

const createAdministrator = () => createForm.post('/admin/administrateurs', { preserveScroll: true, onSuccess: () => createForm.reset() });
const updateProfile = () => profileForm.put('/admin/mon-compte', { preserveScroll: true });
const updatePassword = () => passwordForm.put('/admin/mon-compte/mot-de-passe', { preserveScroll: true, onSuccess: () => passwordForm.reset() });
const remove = administrator => confirm(`Supprimer l’accès administrateur de ${administrator.name} ?`) && router.delete(`/admin/administrateurs/${administrator.id}`, { preserveScroll: true });
</script>

<template>
    <div class="grid gap-6 xl:grid-cols-2">
        <section class="rounded-3xl bg-white p-6 sm:p-8">
            <h2 class="font-serif text-3xl">Mon compte</h2>
            <form class="mt-6 space-y-4" @submit.prevent="updateProfile">
                <label class="block text-sm font-semibold">Nom<input v-model="profileForm.name" class="mt-2 w-full rounded-xl border border-black/10 px-4 py-3 font-normal" /><span v-if="profileForm.errors.name" class="mt-1 block text-xs text-coral">{{ profileForm.errors.name }}</span></label>
                <label class="block text-sm font-semibold">E-mail<input v-model="profileForm.email" type="email" class="mt-2 w-full rounded-xl border border-black/10 px-4 py-3 font-normal" /><span v-if="profileForm.errors.email" class="mt-1 block text-xs text-coral">{{ profileForm.errors.email }}</span></label>
                <button :disabled="profileForm.processing" class="rounded-full bg-ink px-5 py-3 text-sm font-bold text-white">Enregistrer mes informations</button>
            </form>
            <form class="mt-8 space-y-4 border-t border-black/5 pt-7" @submit.prevent="updatePassword">
                <h3 class="font-serif text-2xl">Changer le mot de passe</h3>
                <label class="block text-sm font-semibold">Mot de passe actuel<input v-model="passwordForm.current_password" type="password" autocomplete="current-password" class="mt-2 w-full rounded-xl border border-black/10 px-4 py-3 font-normal" /><span v-if="passwordForm.errors.current_password" class="mt-1 block text-xs text-coral">{{ passwordForm.errors.current_password }}</span></label>
                <label class="block text-sm font-semibold">Nouveau mot de passe<input v-model="passwordForm.password" type="password" autocomplete="new-password" class="mt-2 w-full rounded-xl border border-black/10 px-4 py-3 font-normal" /><span v-if="passwordForm.errors.password" class="mt-1 block text-xs text-coral">{{ passwordForm.errors.password }}</span></label>
                <label class="block text-sm font-semibold">Confirmer<input v-model="passwordForm.password_confirmation" type="password" autocomplete="new-password" class="mt-2 w-full rounded-xl border border-black/10 px-4 py-3 font-normal" /></label>
                <button :disabled="passwordForm.processing" class="rounded-full border border-black/10 px-5 py-3 text-sm font-bold">Modifier le mot de passe</button>
            </form>
        </section>
        <div class="space-y-6">
            <section class="rounded-3xl bg-white p-6 sm:p-8">
                <h2 class="font-serif text-3xl">Ajouter un administrateur</h2>
                <form class="mt-6 space-y-4" @submit.prevent="createAdministrator">
                    <label class="block text-sm font-semibold">Nom<input v-model="createForm.name" class="mt-2 w-full rounded-xl border border-black/10 px-4 py-3 font-normal" /><span v-if="createForm.errors.name" class="mt-1 block text-xs text-coral">{{ createForm.errors.name }}</span></label>
                    <label class="block text-sm font-semibold">E-mail<input v-model="createForm.email" type="email" class="mt-2 w-full rounded-xl border border-black/10 px-4 py-3 font-normal" /><span v-if="createForm.errors.email" class="mt-1 block text-xs text-coral">{{ createForm.errors.email }}</span></label>
                    <div class="grid gap-4 sm:grid-cols-2"><label class="text-sm font-semibold">Mot de passe<input v-model="createForm.password" type="password" class="mt-2 w-full rounded-xl border border-black/10 px-4 py-3 font-normal" /></label><label class="text-sm font-semibold">Confirmation<input v-model="createForm.password_confirmation" type="password" class="mt-2 w-full rounded-xl border border-black/10 px-4 py-3 font-normal" /></label></div>
                    <span v-if="createForm.errors.password" class="block text-xs text-coral">{{ createForm.errors.password }}</span>
                    <button :disabled="createForm.processing" class="rounded-full bg-coral px-5 py-3 text-sm font-bold text-white">Créer le compte</button>
                </form>
            </section>
            <section class="overflow-hidden rounded-3xl bg-white"><div class="border-b border-black/5 px-6 py-5"><h2 class="font-serif text-2xl">Administrateurs de l’école</h2></div><div class="divide-y divide-black/5"><div v-for="administrator in administrators" :key="administrator.id" class="flex items-center justify-between gap-4 px-6 py-4"><div><strong>{{ administrator.name }}</strong><p class="text-sm text-black/45">{{ administrator.email }}</p></div><span v-if="administrator.id === currentUser.id" class="text-xs font-bold text-black/35">Vous</span><button v-else @click="remove(administrator)" class="rounded-full bg-red-50 px-4 py-2 text-xs font-bold text-red-600">Supprimer</button></div></div></section>
        </div>
    </div>
</template>
