<script setup>
import { useForm } from '@inertiajs/vue3';

const props = defineProps({ settings: Object });
const form = useForm({
    payment_reminders_enabled: Boolean(props.settings.payment_reminders_enabled),
    payment_reminder_delay_days: props.settings.payment_reminder_delay_days ?? 1,
    payment_reminder_interval_days: props.settings.payment_reminder_interval_days ?? 7,
    payment_reminder_max_count: props.settings.payment_reminder_max_count ?? 3,
    payment_reminder_fee: props.settings.payment_reminder_fee ?? 0,
});
</script>

<template>
    <section class="mb-6 rounded-3xl bg-white p-6 sm:p-8">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-start"><div><p class="text-xs font-bold uppercase tracking-[.18em] text-coral">Facturation</p><h2 class="mt-1 font-serif text-3xl">Rappels automatiques de paiement</h2><p class="mt-2 max-w-2xl text-sm text-black/45">Chaque jour à 08:00, les factures échues et impayées sont contrôlées. Les frais indiqués sont ajoutés à chaque rappel envoyé.</p></div><label class="flex items-center gap-3 rounded-full bg-[#f7f5f0] px-5 py-3 text-sm font-bold"><input v-model="form.payment_reminders_enabled" type="checkbox" class="h-5 w-5 accent-coral" /> Activer</label></div>
        <form @submit.prevent="form.put('/admin/rappels-paiement', { preserveScroll: true })" class="mt-7 grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <label class="text-sm font-semibold">Premier rappel après l’échéance<input v-model="form.payment_reminder_delay_days" type="number" min="0" max="365" class="mt-2 w-full rounded-xl border border-black/10 px-4 py-3 font-normal" /><span class="mt-1 block text-xs font-normal text-black/40">Nombre de jours</span></label>
            <label class="text-sm font-semibold">Intervalle entre les rappels<input v-model="form.payment_reminder_interval_days" type="number" min="1" max="365" class="mt-2 w-full rounded-xl border border-black/10 px-4 py-3 font-normal" /><span class="mt-1 block text-xs font-normal text-black/40">Nombre de jours</span></label>
            <label class="text-sm font-semibold">Nombre maximal de rappels<input v-model="form.payment_reminder_max_count" type="number" min="1" max="10" class="mt-2 w-full rounded-xl border border-black/10 px-4 py-3 font-normal" /></label>
            <label class="text-sm font-semibold">Frais par rappel (CHF)<input v-model="form.payment_reminder_fee" type="number" min="0" max="9999" step="0.01" class="mt-2 w-full rounded-xl border border-black/10 px-4 py-3 font-normal" /><span class="mt-1 block text-xs font-normal text-black/40">0 pour aucun frais</span></label>
            <div class="sm:col-span-2 xl:col-span-4"><p v-if="Object.keys(form.errors).length" class="mb-3 rounded-xl bg-red-50 p-3 text-sm text-red-600">{{ Object.values(form.errors)[0] }}</p><button :disabled="form.processing" class="rounded-full bg-ink px-6 py-3 font-bold text-white disabled:opacity-50">Enregistrer les rappels</button></div>
        </form>
    </section>
</template>
