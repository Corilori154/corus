<script setup>
import { useForm } from '@inertiajs/vue3';

const props = defineProps({ settings: Object });
const form = useForm({
    payment_reminders_enabled: Boolean(props.settings.payment_reminders_enabled),
    payment_reminder_steps: (props.settings.payment_reminder_steps || [{ delay_days: 1, fee: 0 }])
        .map(step => ({ delay_days: step.delay_days, fee: step.fee })),
});

function addReminder() {
    const lastDelay = Number(form.payment_reminder_steps.at(-1)?.delay_days || 0);
    form.payment_reminder_steps.push({ delay_days: lastDelay + 7, fee: 0 });
}

function removeReminder(index) {
    if (form.payment_reminder_steps.length > 1) form.payment_reminder_steps.splice(index, 1);
}
</script>

<template>
    <section class="mb-6 rounded-3xl bg-white p-6 sm:p-8">
        <div class="flex flex-col justify-between gap-4 sm:flex-row sm:items-start">
            <div><p class="text-xs font-bold uppercase tracking-[.18em] text-coral">Facturation</p><h2 class="mt-1 font-serif text-3xl">Rappels automatiques de paiement</h2><p class="mt-2 max-w-2xl text-sm text-black/45">Créez autant de rappels que nécessaire. Pour chacun, choisissez sa date après l’échéance et les frais à ajouter.</p></div>
            <label class="flex items-center gap-3 rounded-full bg-[#f7f5f0] px-5 py-3 text-sm font-bold"><input v-model="form.payment_reminders_enabled" type="checkbox" class="h-5 w-5 accent-coral" /> Activer</label>
        </div>
        <form @submit.prevent="form.put('/admin/rappels-paiement', { preserveScroll: true })" class="mt-7">
            <div class="space-y-3">
                <div v-for="(step, index) in form.payment_reminder_steps" :key="index" class="grid items-end gap-3 rounded-2xl bg-[#f7f5f0] p-4 sm:grid-cols-[auto_1fr_1fr_auto]">
                    <div class="pb-3 text-sm font-bold text-coral">Rappel {{ index + 1 }}</div>
                    <label class="text-sm font-semibold">Jours après l’échéance<input v-model="step.delay_days" type="number" min="0" max="3650" class="mt-2 w-full rounded-xl border border-black/10 bg-white px-4 py-3 font-normal" /></label>
                    <label class="text-sm font-semibold">Frais ajoutés (CHF)<input v-model="step.fee" type="number" min="0" max="9999" step="0.01" class="mt-2 w-full rounded-xl border border-black/10 bg-white px-4 py-3 font-normal" /></label>
                    <button type="button" :disabled="form.payment_reminder_steps.length === 1" @click="removeReminder(index)" class="grid h-11 w-11 place-items-center rounded-full bg-white text-xl text-red-500 disabled:cursor-not-allowed disabled:opacity-30" :aria-label="`Supprimer le rappel ${index + 1}`">×</button>
                </div>
            </div>
            <button v-if="form.payment_reminder_steps.length < 50" type="button" @click="addReminder" class="mt-4 rounded-full border border-black/10 px-5 py-2.5 text-sm font-bold">＋ Ajouter un rappel</button>
            <p v-if="Object.keys(form.errors).length" class="mt-4 rounded-xl bg-red-50 p-3 text-sm text-red-600">{{ Object.values(form.errors)[0] }}</p>
            <button :disabled="form.processing" class="mt-5 rounded-full bg-ink px-6 py-3 font-bold text-white disabled:opacity-50">Enregistrer les rappels</button>
        </form>
    </section>
</template>
