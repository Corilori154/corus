<script setup>
import { ref } from 'vue';
import { router, useForm } from '@inertiajs/vue3';

defineProps({ rules: Array });
const editing = ref(null);
const defaults = { course_count: 2, discount_type: 'percentage', percentage: 10, fixed_amount: 50 };
const form = useForm({ ...defaults });
const money = value => Number(value || 0).toLocaleString('fr-CH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const label = rule => rule.discount_type === 'fixed' ? `${money(rule.fixed_amount)} CHF` : `${Number(rule.percentage)} %`;

function edit(rule) {
    editing.value = rule;
    form.defaults({
        course_count: rule.course_count,
        discount_type: rule.discount_type || 'percentage',
        percentage: Number(rule.percentage) || 10,
        fixed_amount: Number(rule.fixed_amount) || 50,
    });
    form.reset();
    form.clearErrors();
}

function cancel() {
    editing.value = null;
    form.defaults({ ...defaults });
    form.reset();
    form.clearErrors();
}

function submit() {
    const options = { preserveScroll: true, onSuccess: cancel };
    editing.value
        ? form.put(`/admin/rabais/${editing.value.id}`, options)
        : form.post('/admin/rabais', options);
}

const remove = rule => confirm(`Supprimer le rabais de ${label(rule)} ?`)
    && router.delete(`/admin/rabais/${rule.id}`, { preserveScroll: true });
</script>

<template>
    <section class="grid gap-6 lg:grid-cols-[.7fr_1.3fr]">
        <div class="rounded-3xl bg-white p-6">
            <p class="text-xs font-bold uppercase tracking-[.18em] text-coral">{{ editing ? 'Modification' : 'Nouveau palier' }}</p>
            <h2 class="mt-2 font-serif text-3xl">{{ editing ? 'Modifier le rabais' : 'Créer un rabais' }}</h2>
            <form @submit.prevent="submit" class="mt-6 space-y-4">
                <label class="block text-sm font-semibold">À partir de combien de cours ?
                    <input v-model="form.course_count" type="number" min="2" class="mt-2 w-full rounded-xl border border-black/10 px-4 py-3 font-normal" />
                </label>

                <fieldset>
                    <legend class="text-sm font-semibold">Type de rabais</legend>
                    <div class="mt-2 grid grid-cols-2 gap-3">
                        <label class="cursor-pointer rounded-xl border p-4 text-center transition" :class="form.discount_type === 'percentage' ? 'border-coral bg-red-50 text-coral' : 'border-black/10'">
                            <input v-model="form.discount_type" type="radio" value="percentage" class="sr-only" />
                            <strong>Pourcentage</strong><span class="mt-1 block text-xs opacity-55">Ex. 10 %</span>
                        </label>
                        <label class="cursor-pointer rounded-xl border p-4 text-center transition" :class="form.discount_type === 'fixed' ? 'border-coral bg-red-50 text-coral' : 'border-black/10'">
                            <input v-model="form.discount_type" type="radio" value="fixed" class="sr-only" />
                            <strong>Montant fixe</strong><span class="mt-1 block text-xs opacity-55">Ex. 50 CHF</span>
                        </label>
                    </div>
                </fieldset>

                <label v-if="form.discount_type === 'percentage'" class="block text-sm font-semibold">Pourcentage
                    <div class="relative mt-2"><input v-model="form.percentage" type="number" min="0.01" max="100" step="0.01" class="w-full rounded-xl border border-black/10 px-4 py-3 pr-12 font-normal" /><span class="absolute right-4 top-3 text-black/40">%</span></div>
                </label>
                <label v-else class="block text-sm font-semibold">Montant du rabais
                    <div class="relative mt-2"><input v-model="form.fixed_amount" type="number" min="0.01" step="0.01" class="w-full rounded-xl border border-black/10 px-4 py-3 pr-16 font-normal" /><span class="absolute right-4 top-3 text-black/40">CHF</span></div>
                </label>

                <p class="rounded-xl bg-[#f7f5f0] p-3 text-xs leading-relaxed text-black/50">Le rabais s’applique au total des cours de la même saison. Le montant restant est déduit lors de la nouvelle inscription.</p>
                <p v-if="Object.keys(form.errors).length" class="text-xs text-red-600">{{ Object.values(form.errors)[0] }}</p>
                <div class="flex gap-2"><button v-if="editing" type="button" @click="cancel" class="flex-1 rounded-full border py-3">Annuler</button><button class="flex-1 rounded-full bg-ink py-3.5 font-bold text-white">Enregistrer</button></div>
            </form>
        </div>

        <div class="overflow-hidden rounded-3xl bg-white">
            <div class="border-b border-black/5 px-6 py-5"><h2 class="font-serif text-2xl">Paliers de rabais</h2></div>
            <table v-if="rules.length" class="w-full text-left">
                <thead class="bg-[#f7f5f0] text-[11px] font-bold uppercase text-black/45"><tr><th class="px-6 py-4">Nombre de cours</th><th class="px-5 py-4">Type</th><th class="px-5 py-4">Rabais</th><th class="px-6 py-4 text-right">Actions</th></tr></thead>
                <tbody class="divide-y"><tr v-for="rule in rules" :key="rule.id"><td class="px-6 py-5 font-bold">À partir de {{ rule.course_count }} cours</td><td class="px-5 py-5"><span class="rounded-full bg-black/5 px-3 py-1 text-xs font-bold">{{ rule.discount_type === 'fixed' ? 'Fixe' : 'Pourcentage' }}</span></td><td class="px-5 py-5 font-bold">{{ label(rule) }}</td><td class="px-6 py-5"><div class="flex justify-end gap-2"><button @click="edit(rule)" class="grid h-9 w-9 place-items-center rounded-full bg-black/5" title="Modifier">✎</button><button @click="remove(rule)" class="grid h-9 w-9 place-items-center rounded-full bg-red-50 text-red-500" title="Supprimer">×</button></div></td></tr></tbody>
            </table>
            <div v-else class="p-16 text-center text-black/40">Aucun rabais configuré.</div>
        </div>
    </section>
</template>
