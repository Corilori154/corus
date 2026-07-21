<script setup>
import { router } from '@inertiajs/vue3';

defineProps({ enrollments: Array });
const money = value => Number(value || 0).toLocaleString('fr-CH', { minimumFractionDigits: 2 });
const date = value => new Date(`${String(value).slice(0, 10)}T12:00:00`).toLocaleDateString('fr-CH');
const label = value => ({ waitlist: 'Liste d’attente', invited: 'Invitation envoyée', expired: 'Invitation expirée', accepted: 'Inscrit', pending: 'À traiter' }[value] || value);
const cancel = item => {
    const invoiceWarning = item.invoice ? ' Sa facture et ses éventuels paiements seront également supprimés.' : '';
    if (confirm(`Annuler l’inscription de ${item.first_name} ${item.last_name} ?${invoiceWarning}`)) {
        router.delete(`/admin/inscriptions/${item.id}`, { preserveScroll: true });
    }
};
</script>

<template>
    <section class="overflow-hidden rounded-3xl bg-white">
        <div class="border-b border-black/5 px-6 py-5"><h2 class="font-serif text-2xl">Inscriptions reçues</h2><p class="mt-1 text-xs text-black/45">{{ enrollments.length }} demandes enregistrées</p></div>
        <div v-if="enrollments.length" class="overflow-x-auto">
            <table class="w-full min-w-[1050px] text-left">
                <thead class="bg-[#f7f5f0] text-[11px] font-bold uppercase tracking-wider text-black/45"><tr><th class="px-6 py-4">Élève</th><th class="px-5 py-4">Cours</th><th class="px-5 py-4">Début</th><th class="px-5 py-4">Rôle</th><th class="px-5 py-4">Statut</th><th class="px-5 py-4 text-right">Montant</th><th class="px-6 py-4 text-right">Actions</th></tr></thead>
                <tbody class="divide-y divide-black/5">
                    <tr v-for="item in enrollments" :key="item.id" class="hover:bg-[#faf9f6]">
                        <td class="px-6 py-5"><strong>{{ item.first_name }} {{ item.last_name }}</strong><a :href="`mailto:${item.email}`" class="mt-1 block text-xs text-black/40">{{ item.email }}</a><a v-if="item.phone" :href="`tel:${item.phone}`" class="text-xs text-black/40">{{ item.phone }}</a></td>
                        <td class="px-5 py-5"><strong>{{ item.course?.title || 'Cours supprimé' }}</strong><p class="mt-1 text-xs text-black/40">{{ item.lessons_count }} leçons</p></td>
                        <td class="px-5 py-5">{{ date(item.start_date) }}<p class="mt-1 text-xs text-black/40">Inscrit le {{ date(item.created_at) }}</p></td>
                        <td class="px-5 py-5"><span class="rounded-full bg-black/5 px-3 py-1 text-xs font-bold uppercase">{{ item.dance_role || '—' }}</span></td>
                        <td class="px-5 py-5"><span class="rounded-full px-3 py-1 text-[10px] font-bold uppercase" :class="item.status === 'waitlist' ? 'bg-purple-50 text-purple-700' : item.status === 'accepted' ? 'bg-green-50 text-green-700' : 'bg-amber-50 text-amber-700'">{{ label(item.status) }}</span></td>
                        <td class="px-5 py-5 text-right"><strong>{{ money(item.amount) }} CHF</strong><p v-if="Number(item.discount_amount)" class="text-xs text-green-700">−{{ money(item.discount_amount) }} CHF de rabais</p></td>
                        <td class="px-6 py-5 text-right"><button @click="cancel(item)" class="rounded-full bg-red-50 px-4 py-2 text-xs font-bold text-red-600 hover:bg-red-600 hover:text-white">Annuler</button></td>
                    </tr>
                </tbody>
            </table>
        </div>
        <div v-else class="px-6 py-16 text-center text-black/40">Aucune inscription.</div>
    </section>
</template>
