<script setup>
import { computed } from 'vue';
import { Head, router, useForm, usePage } from '@inertiajs/vue3';

const props = defineProps({ school: Object, enrollments: Array, trials: Array });
const page = usePage();
const passwordForm = useForm({ current_password: '', password: '', password_confirmation: '' });
const activeEnrollments = computed(() => props.enrollments.filter(item => !['waitlist', 'expired'].includes(item.status)));
const waitingEnrollments = computed(() => props.enrollments.filter(item => ['waitlist', 'invited', 'expired'].includes(item.status)));
const invoices = computed(() => props.enrollments.flatMap(item => (item.invoices || []).map(invoice => ({ ...invoice, course: item.course }))));
const money = value => Number(value || 0).toLocaleString('fr-CH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const date = value => value ? new Date(`${String(value).slice(0, 10)}T12:00:00`).toLocaleDateString('fr-CH') : '—';
const statusLabel = status => ({ accepted: 'Acceptée', pending: 'En cours', waitlist: 'Liste d’attente', invited: 'Invitation envoyée', expired: 'Invitation expirée' }[status] || status);
const logout = () => router.post(`/ecole/${props.school.slug}/mon-espace/deconnexion`);
const updatePassword = () => passwordForm.put(`/ecole/${props.school.slug}/mon-espace/mot-de-passe`, { preserveScroll: true, onSuccess: () => passwordForm.reset() });
</script>

<template>
    <Head :title="`Mon espace — ${school.name}`" />
    <div class="min-h-screen bg-[#f5f3ee] text-ink">
        <header class="border-b border-black/5 bg-white">
            <div class="mx-auto flex h-20 max-w-7xl items-center justify-between px-5 lg:px-8">
                <a :href="`/ecole/${school.slug}`" class="flex items-center gap-3"><span class="grid h-10 w-10 place-items-center rounded-full bg-ink text-white">♪</span><span class="font-serif text-2xl font-semibold">{{ school.name }}</span></a>
                <div class="flex items-center gap-2"><a :href="`/ecole/${school.slug}`" class="hidden rounded-full px-4 py-2 text-sm font-semibold hover:bg-black/5 sm:block">Voir les cours</a><button @click="logout" class="rounded-full border border-black/10 px-4 py-2 text-sm font-semibold">Déconnexion</button></div>
            </div>
        </header>

        <main class="mx-auto max-w-7xl px-5 py-10 lg:px-8">
            <div v-if="page.props.flash?.success" class="mb-6 rounded-2xl bg-green-50 px-5 py-4 text-sm font-semibold text-green-700">{{ page.props.flash.success }}</div>
            <p class="text-xs font-bold uppercase tracking-[.2em] text-coral">Espace élève</p>
            <h1 class="mt-2 font-serif text-4xl sm:text-5xl">Bonjour, {{ page.props.auth.user.name.split(' ')[0] }}.</h1>
            <p class="mt-2 text-black/50">Suivez vos inscriptions, invitations et paiements.</p>

            <div class="mt-8 grid gap-4 sm:grid-cols-3">
                <div class="rounded-3xl bg-white p-6"><p class="text-sm text-black/45">Cours</p><strong class="mt-2 block font-serif text-4xl">{{ activeEnrollments.length }}</strong></div>
                <div class="rounded-3xl bg-[#eee9f5] p-6"><p class="text-sm text-black/45">Liste d’attente</p><strong class="mt-2 block font-serif text-4xl">{{ waitingEnrollments.length }}</strong></div>
                <div class="rounded-3xl bg-ink p-6 text-white"><p class="text-sm text-white/50">Factures ouvertes</p><strong class="mt-2 block font-serif text-4xl">{{ invoices.filter(item => item.payment_status !== 'paid').length }}</strong></div>
            </div>

            <section class="mt-8 overflow-hidden rounded-3xl bg-white">
                <div class="border-b border-black/5 p-6"><h2 class="font-serif text-2xl">Mes inscriptions</h2></div>
                <div v-if="enrollments.length" class="divide-y divide-black/5">
                    <article v-for="item in enrollments" :key="item.id" class="grid gap-4 p-6 md:grid-cols-[1fr_auto] md:items-center">
                        <div class="flex items-center gap-4"><img v-if="item.course?.image" :src="item.course.image" :alt="item.course.title" class="h-16 w-16 rounded-2xl object-cover" /><div><h3 class="font-serif text-xl">{{ item.course?.title }}</h3><p class="mt-1 text-sm text-black/45">{{ item.course?.day }} · {{ item.course?.time }} · {{ item.course?.location }}</p><p class="mt-1 text-xs text-black/40">Début le {{ date(item.start_date) }} · {{ item.lessons_count }} leçons</p></div></div>
                        <div class="md:text-right"><span class="inline-flex rounded-full px-3 py-1.5 text-xs font-bold" :class="item.status === 'accepted' ? 'bg-green-50 text-green-700' : item.status === 'invited' ? 'bg-purple-600 text-white' : 'bg-purple-50 text-purple-700'">{{ statusLabel(item.status) }}</span><p class="mt-2 font-bold">{{ money(item.amount) }} CHF</p></div>
                    </article>
                </div>
                <p v-else class="p-10 text-center text-black/40">Aucune inscription pour le moment.</p>
            </section>

            <div class="mt-8 grid gap-8 lg:grid-cols-[1.4fr_.6fr]">
                <section class="overflow-hidden rounded-3xl bg-white">
                    <div class="border-b border-black/5 p-6"><h2 class="font-serif text-2xl">Mes factures</h2></div>
                    <div v-if="invoices.length" class="divide-y divide-black/5">
                        <div v-for="invoice in invoices" :key="invoice.id" class="flex flex-col justify-between gap-3 p-6 sm:flex-row sm:items-center">
                            <div><strong>{{ invoice.number }}</strong><p class="mt-1 text-sm text-black/45">{{ invoice.course?.title }} · échéance {{ date(invoice.due_at) }}</p></div>
                            <div class="sm:text-right"><strong>{{ money(invoice.amount) }} CHF</strong><p class="mt-1 text-xs font-bold" :class="invoice.payment_status === 'paid' ? 'text-green-700' : 'text-orange-600'">{{ invoice.payment_status === 'paid' ? 'Payée' : invoice.payment_status === 'partial' ? `Solde ${money(invoice.balance)} CHF` : 'À payer' }}</p></div>
                        </div>
                    </div>
                    <p v-else class="p-10 text-center text-black/40">Aucune facture.</p>
                </section>

                <aside class="space-y-8">
                    <section v-if="trials.length" class="rounded-3xl bg-white p-6"><h2 class="font-serif text-2xl">Cours d’essai</h2><div class="mt-4 space-y-4"><div v-for="trial in trials" :key="trial.id"><strong>{{ trial.course?.title }}</strong><p class="text-xs text-black/45">{{ date(trial.preferred_date) }} · {{ trial.status }}</p></div></div></section>
                    <section class="rounded-3xl bg-white p-6"><h2 class="font-serif text-2xl">Sécurité</h2><p class="mt-2 text-sm text-black/45">Modifiez le mot de passe temporaire reçu par e-mail.</p><form @submit.prevent="updatePassword" class="mt-5 space-y-3"><input v-model="passwordForm.current_password" type="password" autocomplete="current-password" placeholder="Mot de passe actuel" class="w-full rounded-xl border border-black/10 px-4 py-3 text-sm" /><input v-model="passwordForm.password" type="password" autocomplete="new-password" placeholder="Nouveau mot de passe" class="w-full rounded-xl border border-black/10 px-4 py-3 text-sm" /><input v-model="passwordForm.password_confirmation" type="password" autocomplete="new-password" placeholder="Confirmer" class="w-full rounded-xl border border-black/10 px-4 py-3 text-sm" /><p v-if="Object.keys(passwordForm.errors).length" class="text-xs text-red-600">{{ Object.values(passwordForm.errors)[0] }}</p><button :disabled="passwordForm.processing" class="w-full rounded-full bg-ink py-3 text-sm font-bold text-white disabled:opacity-50">Modifier</button></form></section>
                </aside>
            </div>
        </main>
    </div>
</template>
