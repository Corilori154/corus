<script setup>
import { useForm } from '@inertiajs/vue3';
const props = defineProps({ content: String });
const form = useForm({ terms_and_conditions: props.content || '' });
</script>

<template>
    <section class="mb-6 rounded-3xl bg-white p-6 sm:p-8">
        <p class="text-xs font-bold uppercase tracking-[.18em] text-coral">Inscription</p>
        <h2 class="mt-1 font-serif text-3xl">Conditions générales</h2>
        <p class="mt-2 text-sm text-black/45">Ce texte sera présenté aux élèves et devra être accepté avant chaque inscription.</p>
        <form @submit.prevent="form.put('/admin/conditions-generales', { preserveScroll: true })" class="mt-6">
            <textarea v-model="form.terms_and_conditions" rows="14" maxlength="50000" class="w-full rounded-2xl border border-black/10 px-4 py-4 text-sm leading-relaxed" placeholder="Saisissez les conditions d’inscription, d’annulation, de paiement…"></textarea>
            <div class="mt-2 flex justify-between text-xs text-black/35"><span v-if="form.errors.terms_and_conditions" class="text-red-600">{{ form.errors.terms_and_conditions }}</span><span class="ml-auto">{{ form.terms_and_conditions.length }} / 50 000</span></div>
            <button :disabled="form.processing" class="mt-4 rounded-full bg-ink px-6 py-3 font-bold text-white disabled:opacity-50">Enregistrer les conditions</button>
        </form>
    </section>
</template>
