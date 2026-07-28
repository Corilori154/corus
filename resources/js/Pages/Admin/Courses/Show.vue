<script setup>
import { Head, router } from '@inertiajs/vue3';

defineProps({ course: Object, trialRequests: Array, stats: Object });
const money = value => Number(value || 0).toLocaleString('fr-CH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const date = value => value ? new Date(`${String(value).slice(0, 10)}T12:00:00`).toLocaleDateString('fr-CH') : '—';
const statusLabel = status => ({ accepted: 'Inscrit', waitlist: 'Liste d’attente', invited: 'Invitation envoyée', expired: 'Expirée' }[status] || 'En traitement');
const statusClass = status => status === 'accepted' ? 'bg-green-50 text-green-700' : status === 'invited' ? 'bg-blue-50 text-blue-700' : status === 'waitlist' ? 'bg-purple-50 text-purple-700' : 'bg-black/5 text-black/45';
const logout = () => router.post('/admin/deconnexion');
</script>

<template>
    <Head :title="course.title" />
    <div class="min-h-screen bg-[#f5f3ee]">
        <header class="border-b border-black/5 bg-white">
            <div class="mx-auto flex h-20 max-w-7xl items-center justify-between px-5 lg:px-8">
                <a href="/admin?section=courses" class="flex items-center gap-3"><span class="grid h-10 w-10 place-items-center rounded-full bg-ink text-white">♪</span><span class="font-serif text-2xl font-semibold">Corus</span><span class="rounded-full bg-[#f2eee8] px-3 py-1 text-[10px] font-bold uppercase text-black/45">Admin</span></a>
                <button @click="logout" class="rounded-full border border-black/10 px-4 py-2 text-sm font-semibold">Déconnexion</button>
            </div>
        </header>

        <main class="mx-auto max-w-7xl px-5 py-10 lg:px-8">
            <div class="flex flex-wrap items-center justify-between gap-3">
                <a href="/admin?section=courses" class="text-sm font-bold text-black/45 hover:text-coral">← Retour aux cours</a>
                <a :href="`/admin/cours/${course.id}/eleves/export`" class="rounded-full bg-green-700 px-5 py-3 text-sm font-bold text-white transition hover:bg-green-800">↓ Exporter les élèves pour Excel</a>
            </div>

            <div class="mt-7 overflow-hidden rounded-[2rem] bg-ink text-white">
                <div>
                    <div class="p-7 sm:p-10 lg:p-12">
                        <div class="flex flex-wrap items-center gap-2"><span class="rounded-full px-3 py-1 text-[10px] font-bold uppercase" :class="course.is_active ? 'bg-green-400/20 text-green-200' : 'bg-white/10 text-white/60'">{{ course.is_active ? 'Publié' : 'Brouillon' }}</span><span v-if="course.couple_mode" class="rounded-full bg-purple-400/20 px-3 py-1 text-[10px] font-bold uppercase text-purple-200">Cours de couple</span></div>
                        <div class="mt-7 max-w-3xl"><p class="text-xs font-bold uppercase tracking-[.2em] text-coral">Fiche du cours</p><h1 class="mt-3 font-serif text-4xl leading-tight sm:text-6xl">{{ course.title }}</h1><p class="mt-5 text-base leading-7 text-white/60">{{ course.description }}</p></div>
                        <div class="mt-10 grid gap-3 border-t border-white/10 pt-7 sm:grid-cols-2 lg:grid-cols-4">
                            <div class="rounded-2xl bg-white/[.06] p-4"><p class="text-[10px] font-bold uppercase tracking-wider text-white/35">Discipline</p><strong class="mt-2 block text-sm">{{ course.style }}</strong></div>
                            <div class="rounded-2xl bg-white/[.06] p-4"><p class="text-[10px] font-bold uppercase tracking-wider text-white/35">Niveau</p><strong class="mt-2 block text-sm">{{ course.level }}</strong></div>
                            <div class="rounded-2xl bg-white/[.06] p-4"><p class="text-[10px] font-bold uppercase tracking-wider text-white/35">Horaire</p><strong class="mt-2 block text-sm">{{ course.day }} · {{ course.time }}</strong></div>
                            <div class="rounded-2xl bg-white/[.06] p-4"><p class="text-[10px] font-bold uppercase tracking-wider text-white/35">Lieu</p><strong class="mt-2 block text-sm">{{ course.location }}</strong></div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="mt-6 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-3xl bg-white p-6"><p class="text-sm text-black/45">Inscriptions confirmées</p><strong class="mt-3 block font-serif text-4xl">{{ stats.confirmed }}</strong></div>
                <div class="rounded-3xl bg-white p-6"><p class="text-sm text-black/45">Liste d’attente</p><strong class="mt-3 block font-serif text-4xl">{{ stats.waitlist }}</strong></div>
                <div class="rounded-3xl bg-[#eed5d5] p-6"><p class="text-sm text-black/45">Demandes d’essai</p><strong class="mt-3 block font-serif text-4xl">{{ stats.trials }}</strong></div>
                <div class="rounded-3xl bg-coral p-6 text-white"><p class="text-sm text-white/65">Montant des inscriptions</p><strong class="mt-3 block font-serif text-3xl">{{ money(stats.revenue) }} CHF</strong></div>
            </div>

            <div class="mt-6 grid gap-6 lg:grid-cols-[.72fr_1.28fr]">
                <aside class="space-y-6">
                    <section class="rounded-3xl bg-white p-6"><h2 class="font-serif text-2xl">Informations</h2><dl class="mt-5 space-y-4 text-sm"><div><dt class="text-black/40">Saison</dt><dd class="mt-1 font-semibold">{{ course.season?.name || 'Non attribuée' }}</dd></div><div><dt class="text-black/40">Période</dt><dd class="mt-1 font-semibold">{{ date(course.start_date) }} – {{ date(course.end_date) }}</dd></div><div><dt class="text-black/40">Professeur</dt><dd class="mt-1 font-semibold">{{ course.teacher }}</dd></div><div><dt class="text-black/40">Places disponibles</dt><dd class="mt-1 font-semibold">{{ course.places }} sur {{ course.capacity }}</dd></div></dl></section>
                    <section class="rounded-3xl bg-white p-6"><h2 class="font-serif text-2xl">Tarification</h2><dl class="mt-5 space-y-4 text-sm"><div><dt class="text-black/40">Session complète</dt><dd class="mt-1 font-serif text-2xl font-semibold">{{ money(course.session_price) }} CHF</dd></div><div><dt class="text-black/40">Prix indicatif par leçon</dt><dd class="mt-1 font-semibold">{{ money(course.price) }} CHF</dd></div><div><dt class="text-black/40">Cours d’essai</dt><dd class="mt-1 font-semibold">{{ !course.trial_enabled ? 'Désactivé' : course.trial_is_free ? 'Gratuit' : `${money(course.trial_price)} CHF${course.trial_payment_on_site ? ' · paiement sur place' : ''}` }}</dd></div></dl></section>
                    <section v-if="course.couple_mode" class="rounded-3xl bg-[#eee9f5] p-6"><h2 class="font-serif text-2xl">Équilibre Lead / Follow</h2><p class="mt-4 text-sm text-black/60">La règle s’applique après {{ course.balance_after_count }} inscriptions, avec un écart maximal de {{ course.max_role_gap }}.</p><p class="mt-2 text-xs text-black/40">Une invitation reste valable {{ course.waitlist_invitation_hours }} heures.</p></section>
                </aside>

                <div class="space-y-6">
                    <section class="overflow-hidden rounded-3xl bg-white"><div class="border-b border-black/5 px-6 py-5"><h2 class="font-serif text-2xl">Calendrier des leçons</h2><p class="mt-1 text-xs text-black/45">{{ course.lessons.length }} leçons programmées</p></div><div v-if="course.lessons.length" class="grid gap-2 p-6 sm:grid-cols-2 lg:grid-cols-3"><div v-for="(lesson, index) in course.lessons" :key="lesson.id" class="rounded-2xl bg-[#f7f5f0] p-4"><span class="text-[10px] font-bold uppercase text-black/35">Leçon {{ index + 1 }}</span><strong class="mt-1 block text-sm">{{ date(lesson.lesson_date) }}</strong></div></div><div v-else class="p-10 text-center text-black/40">Aucune leçon planifiée.</div></section>

                    <section class="overflow-hidden rounded-3xl bg-white"><div class="border-b border-black/5 px-6 py-5"><h2 class="font-serif text-2xl">Inscriptions</h2><p class="mt-1 text-xs text-black/45">Participants et état de leur inscription</p></div><div v-if="course.enrollments.length" class="overflow-x-auto"><table class="w-full min-w-[760px] text-left"><thead class="bg-[#f7f5f0] text-[10px] font-bold uppercase tracking-wider text-black/45"><tr><th class="px-6 py-4">Élève</th><th class="px-5 py-4">Rôle</th><th class="px-5 py-4">Début</th><th class="px-5 py-4">Montant</th><th class="px-6 py-4">Statut</th></tr></thead><tbody class="divide-y divide-black/5"><tr v-for="item in course.enrollments" :key="item.id"><td class="px-6 py-4"><strong>{{ item.first_name }} {{ item.last_name }}</strong><p class="text-xs text-black/40">{{ item.email }}<span v-if="item.phone"> · {{ item.phone }}</span></p><p v-if="item.is_minor" class="mt-1 text-xs font-semibold text-purple-700">Mineur · Représentant légal : {{ item.legal_guardian_first_name }} {{ item.legal_guardian_last_name }}</p></td><td class="px-5 py-4 text-sm">{{ item.dance_role || '—' }}</td><td class="px-5 py-4 text-sm">{{ date(item.start_date) }}</td><td class="px-5 py-4 font-semibold">{{ money(item.amount) }} CHF</td><td class="px-6 py-4"><span class="rounded-full px-2.5 py-1 text-[10px] font-bold uppercase" :class="statusClass(item.status)">{{ statusLabel(item.status) }}</span></td></tr></tbody></table></div><div v-else class="p-12 text-center text-black/40">Aucune inscription pour ce cours.</div></section>

                    <section class="overflow-hidden rounded-3xl bg-white"><div class="border-b border-black/5 px-6 py-5"><h2 class="font-serif text-2xl">Demandes de cours d’essai</h2></div><div v-if="trialRequests.length" class="divide-y divide-black/5"><div v-for="trial in trialRequests" :key="trial.id" class="flex flex-col justify-between gap-3 p-5 sm:flex-row sm:items-center"><div><strong>{{ trial.first_name }} {{ trial.last_name }}</strong><p class="mt-1 text-xs text-black/40">{{ trial.email }}<span v-if="trial.phone"> · {{ trial.phone }}</span></p><p v-if="trial.message" class="mt-2 text-sm italic text-black/55">« {{ trial.message }} »</p></div><div class="sm:text-right"><strong class="text-sm">{{ date(trial.preferred_date) }}</strong><p class="mt-1 text-xs text-black/40">{{ trial.trial_is_free ? 'Gratuit' : `${money(trial.trial_price)} CHF` }}</p></div></div></div><div v-else class="p-12 text-center text-black/40">Aucune demande d’essai.</div></section>
                </div>
            </div>
        </main>
    </div>
</template>
