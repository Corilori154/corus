<script setup>
import { computed, ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';

const props = defineProps({ invoices: Array, enrollments: Array, billingSettings: Object });
const showSettings = ref(false);
const showCreate = ref(false);
const paymentInvoice = ref(null);
const search = ref(''); const status = ref('all'); const course = ref('all'); const from = ref(''); const to = ref('');
const billing = useForm({
    billing_name: props.billingSettings.billing_name || '', billing_street: props.billingSettings.billing_street || '',
    billing_house_number: props.billingSettings.billing_house_number || '', billing_postal_code: props.billingSettings.billing_postal_code || '',
    billing_city: props.billingSettings.billing_city || '', billing_country: props.billingSettings.billing_country || 'CH',
    billing_iban: props.billingSettings.billing_iban || '', invoice_prefix: props.billingSettings.invoice_prefix || 'FAC', invoice_due_days: props.billingSettings.invoice_due_days ?? 30,
});
const payment = useForm({ amount: '', paid_on: new Date().toISOString().slice(0, 10), method: 'bank_transfer', note: '' });
const today = new Date().toISOString().slice(0, 10);
const dueDate = new Date(Date.now() + Number(props.billingSettings.invoice_due_days ?? 30) * 86400000).toISOString().slice(0, 10);
const createForm = useForm({ enrollment_id: '', amount: '', issued_at: today, due_at: dueDate });
const invoiceEnrollments = computed(() => props.enrollments.filter(item => !['waitlist', 'invited', 'expired'].includes(item.status)));
const courses = computed(() => [...new Map(props.invoices.map(i => [i.enrollment.course?.id, i.enrollment.course]).filter(([id]) => id)).values()]);
const filtered = computed(() => props.invoices.filter(i => {
    const q = search.value.trim().toLocaleLowerCase('fr');
    if (q && !`${i.number} ${i.enrollment.first_name} ${i.enrollment.last_name} ${i.enrollment.email} ${i.enrollment.course?.title || ''}`.toLocaleLowerCase('fr').includes(q)) return false;
    if (status.value !== 'all' && i.payment_status !== status.value) return false;
    if (course.value !== 'all' && String(i.enrollment.course?.id) !== String(course.value)) return false;
    const date = String(i.issued_at).slice(0, 10);
    return !(from.value && date < from.value) && !(to.value && date > to.value);
}));
const totals = computed(() => ({
    billed: props.invoices.reduce((s, i) => s + Number(i.amount), 0), paid: props.invoices.reduce((s, i) => s + Number(i.paid_amount), 0), balance: props.invoices.reduce((s, i) => s + Number(i.balance), 0),
}));
const money = value => Number(value || 0).toLocaleString('fr-CH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const date = value => new Date(`${String(value).slice(0, 10)}T12:00:00`).toLocaleDateString('fr-CH');
const label = value => ({ open: 'Ouverte', partial: 'Partiellement payée', paid: 'Payée' }[value]);
const badge = value => ({ open: 'bg-amber-50 text-amber-700', partial: 'bg-blue-50 text-blue-700', paid: 'bg-green-50 text-green-700' }[value]);
function openPayment(invoice) { paymentInvoice.value = invoice; payment.defaults({ amount: Number(invoice.balance).toFixed(2), paid_on: new Date().toISOString().slice(0, 10), method: 'bank_transfer', note: '' }); payment.reset(); payment.clearErrors(); }
function submitPayment() { payment.post(`/admin/factures/${paymentInvoice.value.id}/paiements`, { preserveScroll: true, onSuccess: () => paymentInvoice.value = null }); }
function openInvoice(invoice) { router.visit(`/admin/factures/${invoice.id}`); }
function editInvoice(invoice) {
    const amount = prompt('Montant de la facture (CHF)', invoice.amount);
    if (amount === null) return;
    const dueAt = prompt('Date d’échéance (AAAA-MM-JJ)', String(invoice.due_at).slice(0, 10));
    if (dueAt === null) return;
    router.put(`/admin/factures/${invoice.id}`, { amount, issued_at: String(invoice.issued_at).slice(0, 10), due_at: dueAt }, { preserveScroll: true });
}
function deleteInvoice(invoice) {
    if (confirm(`Supprimer définitivement la facture ${invoice.number} et tous ses paiements ?`)) router.delete(`/admin/factures/${invoice.id}`, { preserveScroll: true });
}
function openCreateInvoice() {
    createForm.defaults({ enrollment_id: '', amount: '', issued_at: today, due_at: dueDate });
    createForm.reset();
    createForm.clearErrors();
    showCreate.value = true;
}
function submitInvoice() {
    createForm.post('/admin/factures', { onSuccess: () => { showCreate.value = false; } });
}
</script>

<template>
  <section class="space-y-6">
    <div v-if="showCreate" class="fixed inset-0 z-50 grid place-items-center bg-black/45 p-5 backdrop-blur-sm" @click.self="showCreate = false">
      <form @submit.prevent="submitInvoice" class="w-full max-w-xl rounded-3xl bg-white p-7 shadow-2xl">
        <div class="flex items-start justify-between"><div><p class="text-xs font-bold uppercase tracking-[.18em] text-coral">Nouvelle facture</p><h3 class="mt-1 font-serif text-3xl">Créer manuellement</h3><p class="mt-2 text-sm text-black/45">La facture sera disponible immédiatement, sans être envoyée automatiquement.</p></div><button type="button" @click="showCreate = false" class="grid h-9 w-9 place-items-center rounded-full bg-black/5">×</button></div>
        <div class="mt-6 grid gap-4 sm:grid-cols-2">
          <label class="text-sm font-semibold sm:col-span-2">Élève et cours<select v-model="createForm.enrollment_id" required class="mt-2 w-full rounded-xl border border-black/10 bg-white px-4 py-3 font-normal"><option value="" disabled>Sélectionner une inscription</option><option v-for="item in invoiceEnrollments" :key="item.id" :value="item.id">{{ item.first_name }} {{ item.last_name }} — {{ item.course?.title }}</option></select><span v-if="createForm.errors.enrollment_id" class="mt-1 block text-xs text-red-600">{{ createForm.errors.enrollment_id }}</span></label>
          <label class="text-sm font-semibold">Montant CHF<input v-model="createForm.amount" required type="number" min="0.01" step="0.01" class="mt-2 w-full rounded-xl border border-black/10 px-4 py-3 font-normal" /><span v-if="createForm.errors.amount" class="mt-1 block text-xs text-red-600">{{ createForm.errors.amount }}</span></label>
          <label class="text-sm font-semibold">Date d’émission<input v-model="createForm.issued_at" required type="date" class="mt-2 w-full rounded-xl border border-black/10 px-4 py-3 font-normal" /><span v-if="createForm.errors.issued_at" class="mt-1 block text-xs text-red-600">{{ createForm.errors.issued_at }}</span></label>
          <label class="text-sm font-semibold sm:col-span-2">Date d’échéance<input v-model="createForm.due_at" required type="date" :min="createForm.issued_at" class="mt-2 w-full rounded-xl border border-black/10 px-4 py-3 font-normal" /><span v-if="createForm.errors.due_at" class="mt-1 block text-xs text-red-600">{{ createForm.errors.due_at }}</span></label>
        </div>
        <div class="mt-6 flex gap-3"><button type="button" @click="showCreate = false" class="flex-1 rounded-full border border-black/10 py-3 font-bold">Annuler</button><button :disabled="createForm.processing" class="flex-1 rounded-full bg-coral py-3 font-bold text-white disabled:opacity-50">Créer la facture</button></div>
      </form>
    </div>
    <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-center"><div><p class="text-xs font-bold uppercase tracking-[.18em] text-coral">Gestion financière</p><h2 class="mt-1 font-serif text-4xl">Factures</h2><p class="mt-1 text-sm text-black/45">Suivez les montants facturés, les acomptes et les soldes restant à encaisser.</p></div><div class="flex flex-wrap gap-3"><button @click="openCreateInvoice" class="rounded-full bg-coral px-5 py-3 text-sm font-bold text-white hover:bg-ink">＋ Créer une facture</button><button @click="showSettings = !showSettings" class="rounded-full bg-ink px-5 py-3 text-sm font-bold text-white hover:bg-coral">⚙ Configurer les données de paiement</button></div></div>

    <div v-if="showSettings" class="rounded-3xl bg-white p-6"><div class="flex items-center justify-between"><div><h3 class="font-serif text-2xl">Données de paiement Swiss QR</h3><p class="mt-1 text-xs text-black/45">Adresse structurée et IBAN utilisés sur toutes les factures de l’école.</p></div><span class="rounded-full px-3 py-1 text-xs font-bold" :class="billingSettings.complete ? 'bg-green-50 text-green-700' : 'bg-amber-50 text-amber-700'">{{ billingSettings.complete ? 'Configuré' : 'À compléter' }}</span></div><form @submit.prevent="billing.put('/admin/facturation', { preserveScroll: true, onSuccess: () => showSettings = false })" class="mt-6 grid gap-4 md:grid-cols-2 lg:grid-cols-4"><label class="text-sm font-semibold lg:col-span-2">Titulaire<input v-model="billing.billing_name" class="mt-2 w-full rounded-xl border border-black/10 px-4 py-3 font-normal" /></label><label class="text-sm font-semibold">Rue<input v-model="billing.billing_street" class="mt-2 w-full rounded-xl border border-black/10 px-4 py-3 font-normal" /></label><label class="text-sm font-semibold">Numéro<input v-model="billing.billing_house_number" class="mt-2 w-full rounded-xl border border-black/10 px-4 py-3 font-normal" /></label><label class="text-sm font-semibold">NPA<input v-model="billing.billing_postal_code" class="mt-2 w-full rounded-xl border border-black/10 px-4 py-3 font-normal" /></label><label class="text-sm font-semibold">Localité<input v-model="billing.billing_city" class="mt-2 w-full rounded-xl border border-black/10 px-4 py-3 font-normal" /></label><label class="text-sm font-semibold">Pays<select v-model="billing.billing_country" class="mt-2 w-full rounded-xl border border-black/10 bg-white px-4 py-3 font-normal"><option>CH</option><option>LI</option></select></label><label class="text-sm font-semibold">Échéance en jours<input v-model="billing.invoice_due_days" type="number" class="mt-2 w-full rounded-xl border border-black/10 px-4 py-3 font-normal" /></label><label class="text-sm font-semibold md:col-span-2">IBAN<input v-model="billing.billing_iban" class="mt-2 w-full rounded-xl border border-black/10 px-4 py-3 font-mono font-normal uppercase" /></label><label class="text-sm font-semibold">Préfixe des factures<input v-model="billing.invoice_prefix" class="mt-2 w-full rounded-xl border border-black/10 px-4 py-3 font-normal uppercase" /></label><div class="flex items-end"><button class="w-full rounded-full bg-ink py-3 font-bold text-white">Enregistrer</button></div><p v-if="Object.keys(billing.errors).length" class="text-sm text-red-600 md:col-span-4">{{ Object.values(billing.errors)[0] }}</p></form></div>

    <div class="grid gap-4 sm:grid-cols-3"><div class="rounded-3xl bg-white p-6"><p class="text-sm text-black/45">Total facturé</p><strong class="mt-2 block font-serif text-3xl">{{ money(totals.billed) }} CHF</strong></div><div class="rounded-3xl bg-[#e3f0e8] p-6"><p class="text-sm text-green-800/60">Total encaissé</p><strong class="mt-2 block font-serif text-3xl text-green-800">{{ money(totals.paid) }} CHF</strong></div><div class="rounded-3xl bg-ink p-6 text-white"><p class="text-sm text-white/50">Reste à encaisser</p><strong class="mt-2 block font-serif text-3xl">{{ money(totals.balance) }} CHF</strong></div></div>

    <div class="overflow-hidden rounded-3xl bg-white"><div class="border-b border-black/5 p-5"><div class="grid gap-3 lg:grid-cols-[1.5fr_repeat(4,1fr)]"><input v-model="search" type="search" placeholder="N° facture, élève, e-mail…" class="rounded-xl border border-black/10 bg-[#faf9f6] px-4 py-3 text-sm" /><select v-model="status" class="rounded-xl border border-black/10 bg-white px-3 py-3 text-sm"><option value="all">Tous les statuts</option><option value="open">Ouvertes</option><option value="partial">Partielles</option><option value="paid">Payées</option></select><select v-model="course" class="rounded-xl border border-black/10 bg-white px-3 py-3 text-sm"><option value="all">Tous les cours</option><option v-for="item in courses" :key="item.id" :value="item.id">{{ item.title }}</option></select><input v-model="from" type="date" title="Du" class="rounded-xl border border-black/10 px-3 py-3 text-sm" /><input v-model="to" type="date" title="Au" class="rounded-xl border border-black/10 px-3 py-3 text-sm" /></div><p class="mt-3 text-xs text-black/35">{{ filtered.length }} facture{{ filtered.length > 1 ? 's' : '' }} affichée{{ filtered.length > 1 ? 's' : '' }}</p></div>
      <div v-if="filtered.length" class="overflow-x-auto">
        <table class="w-full min-w-[1180px] border-collapse text-left">
          <thead class="bg-[#f7f5f0] text-[11px] font-bold uppercase tracking-wider text-black/45">
            <tr><th class="px-5 py-4">Facture</th><th class="px-5 py-4">Élève</th><th class="px-5 py-4">Cours et échéance</th><th class="px-5 py-4">Montants</th><th class="px-5 py-4">Statut de paiement</th><th class="px-5 py-4 text-right">Actions</th></tr>
          </thead>
          <tbody class="divide-y divide-black/5">
            <tr v-for="invoice in filtered" :key="invoice.id" tabindex="0" role="link" :aria-label="`Voir les détails de la facture ${invoice.number}`" class="cursor-pointer align-middle transition hover:bg-[#faf9f6] focus:bg-[#faf9f6] focus:outline-none" @click="openInvoice(invoice)" @keydown.enter.self="openInvoice(invoice)">
              <td class="px-5 py-5"><strong>{{ invoice.number }}</strong><p v-if="invoice.installment_count > 1" class="mt-1 text-xs text-black/40">Échéance {{ invoice.installment_number }}/{{ invoice.installment_count }}</p></td>
              <td class="px-5 py-5"><strong class="text-sm">{{ invoice.enrollment.first_name }} {{ invoice.enrollment.last_name }}</strong><p class="mt-1 text-xs text-black/40">{{ invoice.enrollment.email }}</p></td>
              <td class="px-5 py-5"><strong class="text-sm">{{ invoice.enrollment.course?.title }}</strong><p class="mt-1 text-xs text-black/40">Émise le {{ date(invoice.issued_at) }}</p><p class="text-xs text-black/40">À payer le {{ date(invoice.due_at) }}</p></td>
              <td class="px-5 py-5"><p class="text-xs text-black/40">Facturé : {{ money(invoice.amount) }} CHF</p><p class="text-xs text-green-700">Payé : {{ money(invoice.paid_amount) }} CHF</p><strong class="mt-1 block text-sm">Solde : {{ money(invoice.balance) }} CHF</strong></td>
              <td class="px-5 py-5"><span class="inline-flex rounded-full px-3 py-1.5 text-[10px] font-bold uppercase" :class="badge(invoice.payment_status)">{{ label(invoice.payment_status) }}</span><p v-if="invoice.payments.length" class="mt-2 text-xs text-black/40">{{ invoice.payments.length }} paiement{{ invoice.payments.length > 1 ? 's' : '' }}</p></td>
              <td class="px-5 py-5"><div class="flex justify-end gap-2"><button v-if="invoice.balance > 0" @click.stop="openPayment(invoice)" class="rounded-full bg-green-700 px-4 py-2 text-xs font-bold text-white">＋ Paiement</button><a :href="`/admin/factures/${invoice.id}`" class="rounded-full bg-ink px-4 py-2 text-xs font-bold text-white" @click.stop>Voir les détails</a><button @click.stop="router.post(`/admin/factures/${invoice.id}/envoyer`, {}, { preserveScroll: true })" class="rounded-full border border-black/10 px-4 py-2 text-xs font-bold">Envoyer</button><button @click.stop="editInvoice(invoice)" class="grid h-9 w-9 place-items-center rounded-full bg-black/5" title="Modifier">✎</button><button @click.stop="deleteInvoice(invoice)" class="grid h-9 w-9 place-items-center rounded-full bg-red-50 text-red-500" title="Supprimer">×</button></div></td>
            </tr>
          </tbody>
        </table>
      </div>
      <div v-else class="px-6 py-20 text-center"><p class="font-serif text-2xl">Aucune facture trouvée</p><p class="mt-2 text-sm text-black/45">Modifiez les filtres pour afficher d’autres résultats.</p></div>
    </div>
    <div v-if="paymentInvoice" class="fixed inset-0 z-50 grid place-items-center bg-black/45 p-5 backdrop-blur-sm" @click.self="paymentInvoice = null"><form @submit.prevent="submitPayment" class="w-full max-w-lg rounded-3xl bg-white p-7 shadow-2xl"><div class="flex items-start justify-between"><div><p class="text-xs font-bold uppercase tracking-[.18em] text-green-700">Encaissement</p><h3 class="mt-1 font-serif text-3xl">Ajouter un paiement</h3><p class="mt-2 text-sm text-black/45">{{ paymentInvoice.number }} · solde {{ money(paymentInvoice.balance) }} CHF</p></div><button type="button" @click="paymentInvoice = null" class="grid h-9 w-9 place-items-center rounded-full bg-black/5">×</button></div><div class="mt-6 grid gap-4 sm:grid-cols-2"><label class="text-sm font-semibold">Montant CHF<input v-model="payment.amount" type="number" min="0.01" :max="paymentInvoice.balance" step="0.01" class="mt-2 w-full rounded-xl border border-black/10 px-4 py-3 font-normal" /></label><label class="text-sm font-semibold">Date du paiement<input v-model="payment.paid_on" type="date" class="mt-2 w-full rounded-xl border border-black/10 px-4 py-3 font-normal" /></label><label class="text-sm font-semibold sm:col-span-2">Mode de paiement<select v-model="payment.method" class="mt-2 w-full rounded-xl border border-black/10 bg-white px-4 py-3 font-normal"><option value="bank_transfer">Virement bancaire</option><option value="twint">TWINT</option><option value="cash">Espèces</option><option value="card">Carte</option><option value="other">Autre</option></select></label><label class="text-sm font-semibold sm:col-span-2">Note facultative<textarea v-model="payment.note" rows="3" class="mt-2 w-full rounded-xl border border-black/10 px-4 py-3 font-normal" placeholder="Référence, commentaire…"></textarea></label></div><p v-if="Object.keys(payment.errors).length" class="mt-4 rounded-xl bg-red-50 p-3 text-sm text-red-600">{{ Object.values(payment.errors)[0] }}</p><div class="mt-6 flex gap-3"><button type="button" @click="paymentInvoice = null" class="flex-1 rounded-full border border-black/10 py-3 font-bold">Annuler</button><button :disabled="payment.processing" class="flex-1 rounded-full bg-green-700 py-3 font-bold text-white">Enregistrer</button></div></form></div>
  </section>
</template>
