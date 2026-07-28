<script setup>
import { computed, ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';

const props = defineProps({ students: Array });
const search = ref('');
const editing = ref(null);
const form = useForm({ first_name: '', last_name: '', email: '', phone: '' });
const filtered = computed(() => {
    const query = search.value.trim().toLowerCase();
    return query ? props.students.filter(student => `${student.name} ${student.email} ${student.phone || ''} ${student.courses.join(' ')}`.toLowerCase().includes(query)) : props.students;
});
const money = value => Number(value || 0).toLocaleString('fr-CH', { minimumFractionDigits: 2 });

function edit(student) {
    editing.value = student;
    form.defaults({
        first_name: student.first_name || student.name.split(' ')[0] || '',
        last_name: student.last_name || student.name.split(' ').slice(1).join(' '),
        email: student.email,
        phone: student.phone || '',
    });
    form.reset();
    form.clearErrors();
}

function closeEdit() {
    editing.value = null;
    form.reset();
    form.clearErrors();
}

function submit() {
    form.put(`/admin/eleves/${editing.value.id}`, { preserveScroll: true, onSuccess: closeEdit });
}

function remove(student) {
    if (!confirm(`Supprimer définitivement ${student.name} ? Son compte, ses inscriptions, ses factures et ses demandes d’essai seront supprimés.`)) return;
    router.delete(`/admin/eleves/${student.id}`, { preserveScroll: true });
}
</script>

<template>
    <section class="overflow-hidden rounded-3xl bg-white">
        <div class="flex flex-col gap-4 border-b border-black/5 px-6 py-5 sm:flex-row sm:items-center sm:justify-between"><div><h2 class="font-serif text-2xl">Élèves</h2><p class="mt-1 text-xs text-black/45">{{ students.length }} fiches élèves</p></div><input v-model="search" type="search" placeholder="Rechercher un nom, e-mail ou cours…" class="rounded-full border border-black/10 bg-[#faf9f6] px-4 py-2.5 text-sm sm:w-80" /></div>
        <div v-if="filtered.length" class="overflow-x-auto">
            <table class="w-full min-w-[1000px] text-left">
                <thead class="bg-[#f7f5f0] text-[11px] font-bold uppercase tracking-wider text-black/45"><tr><th class="px-6 py-4">Élève</th><th class="px-5 py-4">Coordonnées</th><th class="px-5 py-4">Cours suivis</th><th class="px-5 py-4">Inscriptions</th><th class="px-5 py-4 text-right">Total facturable</th><th class="px-6 py-4 text-right">Actions</th></tr></thead>
                <tbody class="divide-y divide-black/5">
                    <tr v-for="student in filtered" :key="student.id" class="hover:bg-[#faf9f6]" :class="student.has_account && 'cursor-pointer'" @click="student.has_account && router.visit(`/admin/eleves/${student.id}`)">
                        <td class="px-6 py-5"><div class="flex items-center gap-3"><span class="grid h-11 w-11 place-items-center rounded-full bg-[#f1dfdc] font-serif font-bold text-coral">{{ student.name.split(' ').map(part => part[0]).slice(0, 2).join('').toUpperCase() }}</span><div><strong>{{ student.name }}</strong><p class="mt-1 text-[10px] font-bold uppercase" :class="student.has_account ? 'text-green-700' : 'text-black/35'">{{ student.has_account ? 'Compte actif' : 'Sans compte' }}</p></div></div></td>
                        <td class="px-5 py-5"><a @click.stop :href="`mailto:${student.email}`" class="block text-sm">{{ student.email }}</a><a v-if="student.phone" @click.stop :href="`tel:${student.phone}`" class="mt-1 block text-xs text-black/40">{{ student.phone }}</a></td>
                        <td class="px-5 py-5"><div class="flex flex-wrap gap-1"><span v-for="course in student.courses" :key="course" class="rounded-full bg-black/5 px-2 py-1 text-[11px] font-semibold">{{ course }}</span></div></td>
                        <td class="px-5 py-5"><strong>{{ student.accepted_count }} acceptée{{ student.accepted_count > 1 ? 's' : '' }}</strong><p class="text-xs text-black/40">{{ student.waitlist_count }} en attente</p></td>
                        <td class="px-5 py-5 text-right font-bold">{{ money(student.total_amount) }} CHF</td>
                        <td class="px-6 py-5"><div v-if="student.has_account" class="flex justify-end gap-2"><button @click.stop="edit(student)" class="grid h-9 w-9 place-items-center rounded-full bg-black/5" title="Modifier">✎</button><button @click.stop="remove(student)" class="grid h-9 w-9 place-items-center rounded-full bg-red-50 text-red-500" title="Supprimer">×</button></div></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div v-else class="px-6 py-16 text-center text-black/40">Aucun élève trouvé.</div>
    </section>

    <div v-if="editing" class="fixed inset-0 z-50 grid place-items-center bg-black/45 p-4" @click.self="closeEdit">
        <section class="w-full max-w-lg rounded-[2rem] bg-[#fbfaf6] p-7 shadow-2xl sm:p-9">
            <div class="flex items-start justify-between"><div><p class="text-xs font-bold uppercase tracking-[.2em] text-coral">Modification</p><h2 class="mt-1 font-serif text-3xl">Modifier l’élève</h2></div><button @click="closeEdit" class="grid h-10 w-10 place-items-center rounded-full bg-black/5 text-xl">×</button></div>
            <form @submit.prevent="submit" class="mt-7 space-y-4">
                <div class="grid gap-4 sm:grid-cols-2"><label class="text-sm font-semibold">Prénom<input v-model="form.first_name" class="mt-2 w-full rounded-xl border border-black/10 bg-white px-4 py-3 font-normal" /></label><label class="text-sm font-semibold">Nom<input v-model="form.last_name" class="mt-2 w-full rounded-xl border border-black/10 bg-white px-4 py-3 font-normal" /></label></div>
                <label class="block text-sm font-semibold">Adresse e-mail<input v-model="form.email" type="email" class="mt-2 w-full rounded-xl border border-black/10 bg-white px-4 py-3 font-normal" /></label>
                <label class="block text-sm font-semibold">Téléphone<input v-model="form.phone" type="tel" class="mt-2 w-full rounded-xl border border-black/10 bg-white px-4 py-3 font-normal" /></label>
                <p v-if="Object.keys(form.errors).length" class="rounded-xl bg-red-50 p-3 text-sm text-red-600">{{ Object.values(form.errors)[0] }}</p>
                <div class="flex gap-3 pt-3"><button type="button" @click="closeEdit" class="flex-1 rounded-full border border-black/10 py-3.5 font-bold">Annuler</button><button :disabled="form.processing" class="flex-1 rounded-full bg-ink py-3.5 font-bold text-white hover:bg-coral disabled:opacity-50">Enregistrer</button></div>
            </form>
        </section>
    </div>
</template>
