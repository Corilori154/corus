<script setup>
import { computed, ref } from 'vue';

const props = defineProps({ enrollments: Array });
const search = ref('');
const courseId = ref('all');
const status = ref('all');
const waitlist = computed(() => props.enrollments.filter(item => ['waitlist', 'invited', 'expired'].includes(item.status)));
const courses = computed(() => [...new Map(waitlist.value.map(item => [item.course?.id, item.course]).filter(([id]) => id)).values()]);
const filtered = computed(() => waitlist.value.filter(item => {
    const term = search.value.trim().toLocaleLowerCase('fr');
    if (term && !`${item.first_name} ${item.last_name} ${item.email}`.toLocaleLowerCase('fr').includes(term)) return false;
    if (courseId.value !== 'all' && String(item.course?.id) !== String(courseId.value)) return false;
    return status.value === 'all' || item.status === status.value;
}));
const date = value => value ? new Date(value).toLocaleString('fr-CH', { dateStyle: 'short', timeStyle: 'short' }) : '—';
const label = value => ({ waitlist: 'En attente', invited: 'Invitation envoyée', expired: 'Invitation expirée' }[value]);
const badge = value => ({ waitlist: 'bg-purple-50 text-purple-700', invited: 'bg-blue-50 text-blue-700', expired: 'bg-red-50 text-red-700' }[value]);
</script>

<template>
  <section class="space-y-6">
    <div><p class="text-xs font-bold uppercase tracking-[.18em] text-coral">Équilibrage Lead / Follow</p><h2 class="mt-1 font-serif text-4xl">Liste d’attente</h2><p class="mt-2 text-sm text-black/45">Suivez les personnes en attente et les invitations automatiques envoyées.</p></div>
    <div class="grid gap-4 sm:grid-cols-3"><div class="rounded-3xl bg-white p-6"><p class="text-sm text-black/45">En attente</p><strong class="mt-2 block font-serif text-3xl">{{ waitlist.filter(item => item.status === 'waitlist').length }}</strong></div><div class="rounded-3xl bg-[#e7eaf5] p-6"><p class="text-sm text-blue-800/60">Invitations en cours</p><strong class="mt-2 block font-serif text-3xl text-blue-800">{{ waitlist.filter(item => item.status === 'invited').length }}</strong></div><div class="rounded-3xl bg-[#f4e3e1] p-6"><p class="text-sm text-red-800/60">Invitations expirées</p><strong class="mt-2 block font-serif text-3xl text-red-800">{{ waitlist.filter(item => item.status === 'expired').length }}</strong></div></div>
    <div class="overflow-hidden rounded-3xl bg-white"><div class="grid gap-3 border-b border-black/5 p-5 md:grid-cols-[1.5fr_1fr_1fr]"><input v-model="search" type="search" placeholder="Rechercher un nom ou un e-mail…" class="rounded-xl border border-black/10 bg-[#faf9f6] px-4 py-3 text-sm" /><select v-model="courseId" class="rounded-xl border border-black/10 bg-white px-4 py-3 text-sm"><option value="all">Tous les cours</option><option v-for="course in courses" :key="course.id" :value="course.id">{{ course.title }}</option></select><select v-model="status" class="rounded-xl border border-black/10 bg-white px-4 py-3 text-sm"><option value="all">Tous les statuts</option><option value="waitlist">En attente</option><option value="invited">Invitation envoyée</option><option value="expired">Invitation expirée</option></select></div>
      <div v-if="filtered.length" class="overflow-x-auto"><table class="w-full min-w-[850px] text-left"><thead class="bg-[#f7f5f0] text-[11px] font-bold uppercase tracking-wider text-black/45"><tr><th class="px-6 py-4">Élève</th><th class="px-5 py-4">Cours</th><th class="px-5 py-4">Rôle</th><th class="px-5 py-4">Statut</th><th class="px-6 py-4">Invitation</th></tr></thead><tbody class="divide-y divide-black/5"><tr v-for="item in filtered" :key="item.id" class="hover:bg-[#faf9f6]"><td class="px-6 py-5"><strong>{{ item.first_name }} {{ item.last_name }}</strong><a :href="`mailto:${item.email}`" class="mt-1 block text-xs text-black/40 hover:text-coral">{{ item.email }}</a><a v-if="item.phone" :href="`tel:${item.phone}`" class="mt-1 block text-xs text-black/40 hover:text-coral">{{ item.phone }}</a></td><td class="px-5 py-5"><strong class="text-sm">{{ item.course?.title || 'Cours supprimé' }}</strong><p class="mt-1 text-xs text-black/40">Inscription du {{ date(item.created_at) }}</p></td><td class="px-5 py-5"><span class="rounded-full bg-black/5 px-3 py-1 text-xs font-bold uppercase">{{ item.dance_role || '—' }}</span></td><td class="px-5 py-5"><span class="rounded-full px-3 py-1.5 text-[10px] font-bold uppercase" :class="badge(item.status)">{{ label(item.status) }}</span></td><td class="px-6 py-5 text-xs text-black/45"><template v-if="item.status === 'invited'">Envoyée le {{ date(item.waitlist_invited_at) }}<br><strong class="text-black/65">Expire le {{ date(item.waitlist_invitation_expires_at) }}</strong></template><template v-else-if="item.status === 'expired'">Délai dépassé</template><template v-else>En attente d’un rééquilibrage</template></td></tr></tbody></table></div>
      <div v-else class="px-6 py-20 text-center"><p class="font-serif text-2xl">Aucun résultat</p><p class="mt-2 text-sm text-black/45">Aucune personne ne correspond aux filtres sélectionnés.</p></div>
    </div>
  </section>
</template>
