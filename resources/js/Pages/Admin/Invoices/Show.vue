<script setup>
import { Head, router, useForm } from '@inertiajs/vue3';
import { ref } from 'vue';

const props = defineProps({ invoice: Object, documentUrl: String });
const showPayment = ref(false);
const money = value => Number(value || 0).toLocaleString('fr-CH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const date = value => value ? new Date(`${String(value).slice(0, 10)}T12:00:00`).toLocaleDateString('fr-CH') : '—';
const statusLabel = status => status === 'paid' ? 'Payée' : status === 'partial' ? 'Partiellement payée' : 'Ouverte';
const statusClass = status => status === 'paid' ? 'bg-green-50 text-green-700' : status === 'partial' ? 'bg-amber-50 text-amber-700' : 'bg-red-50 text-red-700';
const methodLabel = method => ({ bank_transfer: 'Virement bancaire', cash: 'Espèces', card: 'Carte', twint: 'TWINT', other: 'Autre' }[method] || method);
const payment = useForm({ amount: Number(props.invoice.balance).toFixed(2), paid_on: new Date().toISOString().slice(0, 10), method: 'bank_transfer', note: '' });
const invoiceForm = useForm({
    amount: Number(props.invoice.amount).toFixed(2),
    issued_at: String(props.invoice.issued_at).slice(0, 10),
    due_at: String(props.invoice.due_at).slice(0, 10),
});
const submitPayment = () => payment.post(`/admin/factures/${props.invoice.id}/paiements`, { preserveScroll: true, onSuccess: () => showPayment.value = false });
const submitInvoice = () => invoiceForm.put(`/admin/factures/${props.invoice.id}`, {
    preserveScroll: true,
});
function editPayment(item) {
    const amount = prompt('Montant du paiement (CHF)', item.amount); if (amount === null) return;
    const paid_on = prompt('Date du paiement (AAAA-MM-JJ)', String(item.paid_on).slice(0, 10)); if (paid_on === null) return;
    router.put(`/admin/factures/${props.invoice.id}/paiements/${item.id}`, { amount, paid_on, method: item.method, note: item.note || '' }, { preserveScroll: true });
}
function deletePayment(item) { if (confirm(`Supprimer ce paiement de ${money(item.amount)} CHF ?`)) router.delete(`/admin/factures/${props.invoice.id}/paiements/${item.id}`, { preserveScroll: true }); }
</script>

<template>
    <Head :title="`Facture ${invoice.number}`" />
    <div class="min-h-screen bg-[#f5f3ee]">
        <header class="border-b border-black/5 bg-white"><div class="mx-auto flex h-20 max-w-7xl items-center justify-between px-5 lg:px-8"><a href="/admin?section=invoices" class="flex items-center gap-3"><span class="grid h-10 w-10 place-items-center rounded-full bg-ink text-white">♪</span><span class="font-serif text-2xl font-semibold">Corus</span><span class="rounded-full bg-[#f2eee8] px-3 py-1 text-[10px] font-bold uppercase text-black/45">Admin</span></a><a href="/admin?section=invoices" class="rounded-full border border-black/10 px-4 py-2 text-sm font-semibold">Retour aux factures</a></div></header>
        <main class="mx-auto max-w-7xl px-5 py-10 lg:px-8">
            <a href="/admin?section=invoices" class="text-sm font-bold text-black/45 hover:text-coral">← Retour aux factures</a>
            <div class="mt-7 flex flex-col justify-between gap-5 lg:flex-row lg:items-end"><div><p class="text-xs font-bold uppercase tracking-[.2em] text-coral">Gestion de la facture</p><div class="mt-2 flex flex-wrap items-center gap-3"><h1 class="font-serif text-4xl sm:text-5xl">{{ invoice.number }}</h1><span class="rounded-full px-3 py-1.5 text-xs font-bold uppercase" :class="statusClass(invoice.payment_status)">{{ statusLabel(invoice.payment_status) }}</span></div><p class="mt-2 text-sm text-black/45">Émise le {{ date(invoice.issued_at) }} · échéance le {{ date(invoice.due_at) }}</p></div><div class="flex flex-wrap gap-2"><button v-if="Number(invoice.balance) > 0" @click="showPayment = true" class="rounded-full bg-green-700 px-5 py-3 text-sm font-bold text-white">＋ Ajouter un paiement</button><button @click="router.post(`/admin/factures/${invoice.id}/envoyer`)" class="rounded-full border border-black/10 bg-white px-5 py-3 text-sm font-bold">Envoyer par e-mail</button><a :href="documentUrl" target="_blank" class="rounded-full bg-ink px-5 py-3 text-sm font-bold text-white">Ouvrir le document PDF ↗</a></div></div>

            <div class="mt-8 grid gap-4 sm:grid-cols-3"><div class="rounded-3xl bg-white p-6"><p class="text-sm text-black/45">Montant facturé</p><strong class="mt-3 block font-serif text-3xl">{{ money(invoice.amount) }} CHF</strong></div><div class="rounded-3xl bg-[#e4f1e8] p-6"><p class="text-sm text-green-800/60">Montant encaissé</p><strong class="mt-3 block font-serif text-3xl text-green-800">{{ money(invoice.paid_amount) }} CHF</strong></div><div class="rounded-3xl bg-ink p-6 text-white"><p class="text-sm text-white/50">Solde restant</p><strong class="mt-3 block font-serif text-3xl">{{ money(invoice.balance) }} CHF</strong></div></div>

            <div class="mt-6 grid gap-6 lg:grid-cols-[.75fr_1.25fr]">
                <aside class="space-y-6"><section class="rounded-3xl bg-white p-6"><h2 class="font-serif text-2xl">Élève</h2><dl class="mt-5 space-y-4 text-sm"><div><dt class="text-black/40">Nom</dt><dd class="mt-1 font-semibold">{{ invoice.enrollment.first_name }} {{ invoice.enrollment.last_name }}</dd></div><div><dt class="text-black/40">E-mail</dt><dd class="mt-1 font-semibold"><a :href="`mailto:${invoice.enrollment.email}`">{{ invoice.enrollment.email }}</a></dd></div><div><dt class="text-black/40">Téléphone</dt><dd class="mt-1 font-semibold">{{ invoice.enrollment.phone || 'Non renseigné' }}</dd></div><div v-if="invoice.enrollment.dance_role"><dt class="text-black/40">Rôle</dt><dd class="mt-1 font-semibold capitalize">{{ invoice.enrollment.dance_role }}</dd></div></dl></section><section class="rounded-3xl bg-white p-6"><h2 class="font-serif text-2xl">Cours</h2><h3 class="mt-5 font-bold">{{ invoice.enrollment.course?.title || 'Cours supprimé' }}</h3><p class="mt-2 text-sm text-black/45">{{ invoice.enrollment.course?.style }} · {{ invoice.enrollment.course?.level }}</p><p class="mt-1 text-sm text-black/45">{{ invoice.enrollment.course?.day }} {{ invoice.enrollment.course?.time }} · {{ invoice.enrollment.course?.location }}</p><div class="mt-4 rounded-2xl bg-[#f7f5f0] p-4 text-sm"><p>Début choisi : <strong>{{ date(invoice.enrollment.start_date) }}</strong></p><p class="mt-2">Leçons facturées : <strong>{{ invoice.enrollment.lessons_count }}</strong></p></div></section></aside>

                <div class="space-y-6"><section class="rounded-3xl bg-white p-6"><h2 class="font-serif text-2xl">Détail du calcul</h2><div class="mt-5 divide-y divide-black/5 text-sm"><div class="flex justify-between py-3"><span>Prix des leçons restantes</span><strong>{{ money(invoice.enrollment.base_amount) }} CHF</strong></div><div v-if="Number(invoice.enrollment.category_discount_amount)" class="flex justify-between py-3 text-green-700"><span>Rabais {{ invoice.enrollment.pricing_category_name }}</span><strong>−{{ money(invoice.enrollment.category_discount_amount) }} CHF</strong></div><div v-if="Number(invoice.enrollment.discount_amount)" class="flex justify-between py-3 text-green-700"><span>Rabais multi-cours<span v-if="Number(invoice.enrollment.discount_percentage)"> ({{ Number(invoice.enrollment.discount_percentage) }} %)</span></span><strong>−{{ money(invoice.enrollment.discount_amount) }} CHF</strong></div><div v-if="Number(invoice.enrollment.payment_adjustment_amount)" class="flex justify-between py-3"><span>Ajustement du plan de paiement</span><strong>{{ Number(invoice.enrollment.payment_adjustment_amount) > 0 ? '+' : '−' }}{{ money(Math.abs(Number(invoice.enrollment.payment_adjustment_amount))) }} CHF</strong></div><div class="flex justify-between py-4 text-lg"><span>Total de l’inscription</span><strong>{{ money(invoice.enrollment.amount) }} CHF</strong></div><div v-if="invoice.installment_count > 1" class="flex justify-between py-3"><span>{{ invoice.enrollment.payment_plan_name }} · échéance {{ invoice.installment_number }}/{{ invoice.installment_count }}</span><strong>{{ money(invoice.amount) }} CHF</strong></div></div></section>
                    <section class="overflow-hidden rounded-3xl bg-white"><div class="border-b border-black/5 p-6"><h2 class="font-serif text-2xl">Historique des paiements</h2></div><div v-if="invoice.payments.length" class="overflow-x-auto"><table class="w-full min-w-[650px] text-left text-sm"><thead class="bg-[#f8f7f3] text-xs uppercase text-black/40"><tr><th class="px-6 py-4">Date</th><th class="px-4 py-4">Mode</th><th class="px-4 py-4">Note</th><th class="px-4 py-4">Enregistré par</th><th class="px-6 py-4 text-right">Montant</th><th class="px-6 py-4 text-right">Actions</th></tr></thead><tbody class="divide-y divide-black/5"><tr v-for="item in invoice.payments" :key="item.id"><td class="px-6 py-4">{{ date(item.paid_on) }}</td><td class="px-4 py-4">{{ methodLabel(item.method) }}</td><td class="px-4 py-4 text-black/45">{{ item.note || '—' }}</td><td class="px-4 py-4">{{ item.recorder?.name || 'Système' }}</td><td class="px-6 py-4 text-right font-bold text-green-700">{{ money(item.amount) }} CHF</td><td class="px-6 py-4"><div class="flex justify-end gap-2"><button @click="editPayment(item)" class="grid h-9 w-9 place-items-center rounded-full bg-black/5" title="Modifier">✎</button><button @click="deletePayment(item)" class="grid h-9 w-9 place-items-center rounded-full bg-red-50 text-red-500" title="Supprimer">×</button></div></td></tr></tbody></table></div><div v-else class="p-10 text-center text-sm text-black/40">Aucun paiement enregistré.</div></section>
                    <section v-if="invoice.installment_count > 1" class="overflow-hidden rounded-3xl bg-white"><div class="border-b border-black/5 p-6"><h2 class="font-serif text-2xl">Échéancier complet</h2></div><div class="divide-y divide-black/5"><div v-for="item in invoice.enrollment.invoices" :key="item.id" class="flex items-center justify-between gap-4 p-5"><div><strong>{{ item.number }}</strong><p class="mt-1 text-xs text-black/40">Échéance {{ item.installment_number }}/{{ item.installment_count }} · à payer le {{ date(item.due_at) }}</p></div><div class="text-right"><span class="rounded-full px-2.5 py-1 text-[10px] font-bold uppercase" :class="statusClass(item.payment_status)">{{ statusLabel(item.payment_status) }}</span><strong class="mt-2 block">{{ money(item.amount) }} CHF</strong></div></div></div></section>
                </div>
            </div>
            <form @submit.prevent="submitInvoice" class="mt-6 rounded-3xl bg-white p-6 sm:p-8">
                <div>
                    <p class="text-xs font-bold uppercase tracking-[.18em] text-coral">Paramètres</p>
                    <h2 class="mt-1 font-serif text-3xl">Modifier la facture</h2>
                    <p class="mt-2 text-sm text-black/45">Modifiez le montant et les dates associées à cette facture.</p>
                </div>
                <div class="mt-6 grid gap-4 md:grid-cols-3">
                    <label class="text-sm font-semibold">Montant CHF
                        <input v-model="invoiceForm.amount" type="number" min="0.01" step="0.01" required class="mt-2 w-full rounded-xl border border-black/10 px-4 py-3 font-normal focus:border-coral focus:outline-none" />
                        <span v-if="invoiceForm.errors.amount" class="mt-2 block text-xs font-normal text-red-600">{{ invoiceForm.errors.amount }}</span>
                    </label>
                    <label class="text-sm font-semibold">Date d’émission
                        <input v-model="invoiceForm.issued_at" type="date" required class="mt-2 w-full rounded-xl border border-black/10 px-4 py-3 font-normal focus:border-coral focus:outline-none" />
                        <span v-if="invoiceForm.errors.issued_at" class="mt-2 block text-xs font-normal text-red-600">{{ invoiceForm.errors.issued_at }}</span>
                    </label>
                    <label class="text-sm font-semibold">Date d’échéance
                        <input v-model="invoiceForm.due_at" type="date" :min="invoiceForm.issued_at" required class="mt-2 w-full rounded-xl border border-black/10 px-4 py-3 font-normal focus:border-coral focus:outline-none" />
                        <span v-if="invoiceForm.errors.due_at" class="mt-2 block text-xs font-normal text-red-600">{{ invoiceForm.errors.due_at }}</span>
                    </label>
                </div>
                <div class="mt-6 flex justify-end">
                    <button type="submit" :disabled="invoiceForm.processing" class="rounded-full bg-ink px-6 py-3 text-sm font-bold text-white disabled:cursor-not-allowed disabled:opacity-50">
                        {{ invoiceForm.processing ? 'Enregistrement…' : 'Enregistrer les modifications' }}
                    </button>
                </div>
            </form>
        </main>

        <div v-if="showPayment" class="fixed inset-0 z-50 grid place-items-center bg-black/45 p-5 backdrop-blur-sm" @click.self="showPayment = false"><form @submit.prevent="submitPayment" class="w-full max-w-lg rounded-3xl bg-white p-7 shadow-2xl"><div class="flex items-start justify-between"><div><p class="text-xs font-bold uppercase tracking-[.18em] text-green-700">Encaissement</p><h3 class="mt-1 font-serif text-3xl">Ajouter un paiement</h3><p class="mt-2 text-sm text-black/45">Solde : {{ money(invoice.balance) }} CHF</p></div><button type="button" @click="showPayment = false" class="grid h-9 w-9 place-items-center rounded-full bg-black/5">×</button></div><div class="mt-6 grid gap-4 sm:grid-cols-2"><label class="text-sm font-semibold">Montant CHF<input v-model="payment.amount" type="number" min="0.01" :max="invoice.balance" step="0.01" class="mt-2 w-full rounded-xl border border-black/10 px-4 py-3 font-normal" /></label><label class="text-sm font-semibold">Date<input v-model="payment.paid_on" type="date" class="mt-2 w-full rounded-xl border border-black/10 px-4 py-3 font-normal" /></label><label class="text-sm font-semibold sm:col-span-2">Mode<select v-model="payment.method" class="mt-2 w-full rounded-xl border border-black/10 bg-white px-4 py-3 font-normal"><option value="bank_transfer">Virement bancaire</option><option value="twint">TWINT</option><option value="cash">Espèces</option><option value="card">Carte</option><option value="other">Autre</option></select></label><label class="text-sm font-semibold sm:col-span-2">Note<textarea v-model="payment.note" rows="3" class="mt-2 w-full rounded-xl border border-black/10 px-4 py-3 font-normal"></textarea></label></div><p v-if="Object.keys(payment.errors).length" class="mt-4 rounded-xl bg-red-50 p-3 text-sm text-red-600">{{ Object.values(payment.errors)[0] }}</p><div class="mt-6 flex gap-3"><button type="button" @click="showPayment = false" class="flex-1 rounded-full border border-black/10 py-3 font-bold">Annuler</button><button class="flex-1 rounded-full bg-green-700 py-3 font-bold text-white">Enregistrer</button></div></form></div>
    </div>
</template>
