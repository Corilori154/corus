<script setup>
import { computed, ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';

const props = defineProps({ enrollments: Array, courses: Array });
const editing = ref(null);
const search = ref('');
const courseFilter = ref('all');
const statusFilter = ref('all');
const filtered = computed(() => props.enrollments.filter(item => {
    const query = search.value.trim().toLocaleLowerCase('fr');
    if (query && !`${item.first_name} ${item.last_name} ${item.email} ${item.phone || ''}`.toLocaleLowerCase('fr').includes(query)) return false;
    if (courseFilter.value !== 'all' && String(item.course?.id) !== String(courseFilter.value)) return false;
    return statusFilter.value === 'all' || item.status === statusFilter.value;
}));
const form = useForm({ first_name: '', last_name: '', email: '', phone: '', dance_course_id: null, start_date: '', dance_role: '', comment: '' });
const money = value => Number(value || 0).toLocaleString('fr-CH', { minimumFractionDigits: 2 });
const date = value => new Date(`${String(value).slice(0, 10)}T12:00:00`).toLocaleDateString('fr-CH');
const label = value => ({ waitlist: 'Liste d’attente', invited: 'Invitation envoyée', expired: 'Invitation expirée', accepted: 'Inscrit', pending: 'À traiter' }[value] || value);
const badge = value => ({ accepted: 'bg-green-50 text-green-700', pending: 'bg-amber-50 text-amber-700', waitlist: 'bg-purple-50 text-purple-700', invited: 'bg-blue-50 text-blue-700', expired: 'bg-red-50 text-red-700' }[value] || 'bg-black/5 text-black/50');

function edit(item) {
    editing.value = item;
    form.defaults({ first_name: item.first_name, last_name: item.last_name, email: item.email, phone: item.phone || '', dance_course_id: item.dance_course_id, start_date: String(item.start_date).slice(0, 10), dance_role: item.dance_role || '', comment: item.comment || '' });
    form.reset();
    form.clearErrors();
}
function close() { editing.value = null; form.reset(); }
function submit() { form.put(`/admin/inscriptions/${editing.value.id}`, { preserveScroll: true, onSuccess: close }); }
function cancel(item) {
    const warning = item.invoice ? ' Sa facture et ses paiements seront également supprimés.' : '';
    if (confirm(`Annuler l’inscription de ${item.first_name} ${item.last_name} ?${warning}`)) router.delete(`/admin/inscriptions/${item.id}`, { preserveScroll: true });
}
</script>

<template>
    <section class="overflow-hidden rounded-3xl bg-white">
        <div class="px-6 pt-5"><h2 class="font-serif text-2xl">Inscriptions reçues</h2><p class="mt-1 text-xs text-black/40">{{ filtered.length }} inscription{{ filtered.length > 1 ? 's' : '' }} affichée{{ filtered.length > 1 ? 's' : '' }}</p></div>
        <div class="grid gap-3 px-6 py-5 md:grid-cols-[1.5fr_1fr_1fr]"><input v-model="search" type="search" placeholder="Rechercher un élève…" class="rounded-xl border border-black/10 bg-[#faf9f6] px-4 py-3 text-sm"/><select v-model="courseFilter" class="rounded-xl border border-black/10 bg-white px-4 py-3 text-sm"><option value="all">Tous les cours</option><option v-for="course in courses" :key="course.id" :value="course.id">{{ course.title }}</option></select><select v-model="statusFilter" class="rounded-xl border border-black/10 bg-white px-4 py-3 text-sm"><option value="all">Tous les statuts</option><option value="accepted">Inscrit</option><option value="pending">À traiter</option><option value="waitlist">Liste d’attente</option><option value="invited">Invitation envoyée</option><option value="expired">Invitation expirée</option></select></div>
        <div v-if="filtered.length" class="overflow-x-auto"><table class="w-full min-w-[1050px] text-left"><thead class="bg-[#f7f5f0] text-xs text-black/45"><tr><th class="p-4">Élève</th><th class="p-4">Cours</th><th class="p-4">Début</th><th class="p-4">Statut</th><th class="p-4">Montant</th><th class="p-4 text-right">Actions</th></tr></thead><tbody><tr v-for="item in filtered" :key="item.id"><td class="p-5"><strong>{{ item.first_name }} {{ item.last_name }}</strong><p class="text-xs text-black/40">{{ item.email }}</p></td><td class="p-5 font-semibold">{{ item.course?.title || 'Cours supprimé' }}</td><td class="p-5">{{ date(item.start_date) }}</td><td class="p-5"><span class="rounded-full px-3 py-1 text-xs font-bold" :class="badge(item.status)">{{ label(item.status) }}</span></td><td class="p-5 font-bold">{{ money(item.amount) }} CHF</td><td class="p-5"><div class="flex justify-end gap-2"><button @click="edit(item)" class="grid h-9 w-9 place-items-center rounded-full bg-black/5" title="Modifier">✎</button><button @click="cancel(item)" class="grid h-9 w-9 place-items-center rounded-full bg-red-50 text-red-500" title="Supprimer">×</button></div></td></tr></tbody></table></div>
        <p v-else class="p-16 text-center text-black/40">Aucune inscription ne correspond aux filtres.</p>
    </section>

    <div v-if="editing" class="fixed inset-0 z-50 grid place-items-center bg-black/45 p-4" @click.self="close">
        <form @submit.prevent="submit" class="w-full max-w-xl rounded-3xl bg-white p-7">
            <h2 class="font-serif text-3xl">Modifier l’inscription</h2>
            <div class="mt-5 grid gap-3 sm:grid-cols-2">
                <select v-model="form.dance_course_id" class="rounded-xl border bg-white px-4 py-3 sm:col-span-2"><option v-for="course in courses" :key="course.id" :value="course.id">{{ course.title }} · {{ course.day }} {{ course.time }} · {{ course.places }} place(s)</option></select>
                <input v-model="form.first_name" class="rounded-xl border px-4 py-3" placeholder="Prénom"/><input v-model="form.last_name" class="rounded-xl border px-4 py-3" placeholder="Nom"/>
                <input v-model="form.email" type="email" class="rounded-xl border px-4 py-3"/><input v-model="form.phone" class="rounded-xl border px-4 py-3"/>
                <input v-model="form.start_date" type="date" class="rounded-xl border px-4 py-3"/><select v-model="form.dance_role" class="rounded-xl border bg-white px-4 py-3"><option value="">Aucun rôle</option><option value="lead">Lead</option><option value="follow">Follow</option></select>
                <textarea v-model="form.comment" rows="3" class="rounded-xl border p-3 sm:col-span-2" placeholder="Commentaire"></textarea>
            </div>
            <p class="mt-2 text-xs text-black/40">Le changement de cours ne recalcule pas les montants déjà facturés.</p>
            <p v-if="Object.keys(form.errors).length" class="mt-3 text-sm text-red-600">{{ Object.values(form.errors)[0] }}</p>
            <div class="mt-5 flex gap-2"><button type="button" @click="close" class="flex-1 rounded-full border py-3">Annuler</button><button class="flex-1 rounded-full bg-ink py-3 font-bold text-white">Enregistrer</button></div>
        </form>
    </div>
</template>
