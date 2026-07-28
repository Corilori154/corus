<script setup>
import { Head, useForm } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({ school: Object, course: Object, pricingCategories: Array, paymentPlans: Array });
const modal = ref(null);
const enrollment = useForm({ course_id: props.course.id, first_name: '', last_name: '', email: '', phone: '', is_minor: false, legal_guardian_first_name: '', legal_guardian_last_name: '', dance_role: '', start_date: props.course.start_date, pricing_category_id: '', payment_plan_id: '', comment: '', terms_accepted: false });
const trial = useForm({ course_id: props.course.id, first_name: '', last_name: '', email: '', phone: '', dance_role: '', preferred_date: props.course.lessons[0]?.lesson_date || '', message: '' });
const remainingLessons = computed(() => props.course.lessons.filter(item => item.lesson_date >= enrollment.start_date).length);
const selectedCategory = computed(() => props.pricingCategories.find(category => category.id === Number(enrollment.pricing_category_id)));
const estimatedPrice = computed(() => {
    const sessionPrice = selectedCategory.value ? Number(selectedCategory.value.pivot.price) : Number(props.course.session_price);
    return props.course.lessons.length ? sessionPrice * remainingLessons.value / props.course.lessons.length : 0;
});
const serverQuote = ref(null);
const quoteLoading = ref(false);
let quoteTimer;
watch([() => enrollment.email, () => enrollment.start_date, () => enrollment.pricing_category_id, () => enrollment.payment_plan_id], () => {
    clearTimeout(quoteTimer);
    serverQuote.value = null;
    if (!enrollment.start_date || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(enrollment.email)) return;
    quoteTimer = setTimeout(loadQuote, 350);
});
async function loadQuote() {
    quoteLoading.value = true;
    try {
        const response = await fetch(`/ecole/${props.school.slug}/devis`, {
            method: 'POST',
            credentials: 'same-origin',
            headers: {
                'Content-Type': 'application/json',
                'Accept': 'application/json',
                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').content,
            },
            body: JSON.stringify({
                email: enrollment.email,
                course_id: props.course.id,
                start_date: enrollment.start_date,
                pricing_category_id: enrollment.pricing_category_id || null,
                payment_plan_id: enrollment.payment_plan_id || null,
            }),
        });
        if (!response.ok) return;
        const quote = await response.json();
        serverQuote.value = {
            amount: Number(quote.amount),
            registrationFeeName: quote.registration_fee_name,
            registrationFeeAmount: Number(quote.registration_fee_amount),
            remainingLessons: quote.remaining_lessons,
        };
    } finally {
        quoteLoading.value = false;
    }
}
const displayedPrice = computed(() => serverQuote.value?.amount ?? estimatedPrice.value);
const submitEnrollment = () => enrollment.post(`/ecole/${props.school.slug}/inscriptions`, { preserveScroll: true, onSuccess: () => modal.value = null });
const submitTrial = () => trial.post(`/ecole/${props.school.slug}/cours-essai`, { preserveScroll: true, onSuccess: () => modal.value = null });
const formatDate = date => new Date(`${date}T12:00:00`).toLocaleDateString('fr-FR', { day: 'numeric', month: 'long', year: 'numeric' });
</script>

<template>
    <Head :title="course.title" />
    <div class="min-h-screen bg-[#fbfaf6]">
        <header class="border-b border-black/5 bg-white/90 backdrop-blur-xl"><nav class="mx-auto flex h-20 max-w-7xl items-center justify-between px-5 lg:px-8"><a :href="`/ecole/${school.slug}`" class="flex items-center gap-3"><span class="grid h-10 w-10 place-items-center rounded-full bg-ink text-white">♪</span><span class="font-serif text-2xl font-semibold">{{ school.name }}<span class="text-coral">.</span></span></a><a :href="`/ecole/${school.slug}`" class="rounded-full border border-black/10 px-4 py-2 text-sm font-bold hover:border-coral">← Tous les cours</a></nav></header>
        <main class="mx-auto max-w-7xl px-5 py-10 lg:px-8 lg:py-16">
            <div class="grid gap-10 lg:grid-cols-[1.05fr_.95fr]">
                <div><div class="relative overflow-hidden rounded-[2rem]"><img :src="course.image" :alt="course.title" class="h-[420px] w-full object-cover sm:h-[560px]" /><span class="absolute left-5 top-5 rounded-full bg-white/90 px-4 py-2 text-xs font-bold">{{ course.level }}</span><span v-if="course.couple_mode" class="absolute right-5 top-5 rounded-full bg-purple-600 px-4 py-2 text-xs font-bold text-white">Lead / Follow</span></div></div>
                <div class="lg:py-5"><p class="text-xs font-bold uppercase tracking-[.2em] text-coral">{{ course.style }}</p><h1 class="mt-3 font-serif text-5xl leading-tight sm:text-6xl">{{ course.title }}</h1><p class="mt-5 text-lg leading-relaxed text-black/55">{{ course.description }}</p><div class="mt-7 grid gap-3 sm:grid-cols-2"><div class="rounded-2xl bg-white p-4"><p class="text-xs text-black/40">Professeur</p><strong class="mt-1 block">{{ course.teacher }}</strong></div><div class="rounded-2xl bg-white p-4"><p class="text-xs text-black/40">Lieu</p><strong class="mt-1 block">{{ course.location }}</strong></div><div class="rounded-2xl bg-white p-4"><p class="text-xs text-black/40">Horaire</p><strong class="mt-1 block">{{ course.day }} · {{ course.time }}</strong></div><div class="rounded-2xl bg-white p-4"><p class="text-xs text-black/40">Session</p><strong class="mt-1 block">{{ formatDate(course.start_date) }} — {{ formatDate(course.end_date) }}</strong></div></div><div class="mt-6 rounded-2xl bg-ink p-5 text-white"><div class="flex items-end justify-between"><div><p class="text-xs text-white/45">Prix de la session</p><strong class="font-serif text-4xl">{{ Number(course.session_price).toLocaleString('fr-FR') }} CHF</strong></div><p class="text-right text-xs text-white/50">{{ course.lessons.length }} leçons<br>{{ course.places }} places restantes</p></div></div><div class="mt-6 grid gap-3" :class="course.trial_enabled ? 'sm:grid-cols-2' : 'sm:grid-cols-1'"><button v-if="course.trial_enabled" :disabled="!course.lessons.length" @click="modal = 'trial'" class="rounded-full border border-ink px-6 py-4 font-bold transition hover:bg-ink hover:text-white disabled:opacity-30">{{ course.trial_is_free ? 'Essai gratuit' : 'Essai · ' + Number(course.trial_price).toLocaleString('fr-CH', { minimumFractionDigits: 2 }) + ' CHF' }}</button><button :disabled="course.places === 0" @click="modal = 'enrollment'" class="rounded-full bg-ink px-6 py-4 font-bold text-white transition hover:bg-coral disabled:opacity-30">S’inscrire</button></div></div>
            </div>
            <section class="mt-16"><h2 class="font-serif text-3xl">Calendrier des leçons</h2><div class="mt-5 flex gap-2 overflow-x-auto pb-3"><span v-for="lesson in course.lessons" :key="lesson.id" class="whitespace-nowrap rounded-full bg-white px-4 py-2 text-sm font-semibold">{{ formatDate(lesson.lesson_date) }}</span></div></section>
        </main>

        <div v-if="modal" class="fixed inset-0 z-50 grid place-items-center bg-black/60 p-4 backdrop-blur-sm" @click.self="modal = null"><div class="max-h-[92vh] w-full max-w-lg overflow-y-auto rounded-[2rem] bg-white p-6 sm:p-8"><div class="mb-6 flex justify-between"><div><p class="text-xs font-bold uppercase tracking-[.18em] text-coral">{{ modal === 'trial' ? 'Découvrir' : 'Rejoindre' }}</p><h2 class="mt-1 font-serif text-3xl">{{ modal === 'trial' ? 'Cours d’essai' : 'Inscription' }}</h2></div><button @click="modal = null" class="grid h-9 w-9 place-items-center rounded-full bg-black/5 text-xl">×</button></div>
                <div v-if="modal === 'enrollment'" class="mb-4 space-y-4"><label class="block text-sm font-semibold">Commentaire <span class="font-normal text-black/35">(facultatif)</span><textarea v-model="enrollment.comment" rows="3" maxlength="2000" class="mt-2 w-full resize-none rounded-xl border border-black/10 px-4 py-3 font-normal" placeholder="Une information utile pour l’école…"></textarea></label><div class="rounded-xl border border-black/10 p-4"><details v-if="school.terms_and_conditions" class="mb-3"><summary class="cursor-pointer text-sm font-bold text-coral">Lire les conditions générales</summary><div class="mt-3 max-h-48 overflow-y-auto whitespace-pre-wrap rounded-lg bg-[#f7f5f0] p-3 text-xs leading-relaxed text-black/60">{{ school.terms_and_conditions }}</div></details><label class="flex cursor-pointer items-start gap-3 text-sm"><input v-model="enrollment.terms_accepted" type="checkbox" class="mt-0.5 h-5 w-5 shrink-0 accent-coral" /><span>J’ai lu et j’accepte les conditions générales de l’école.</span></label><span v-if="enrollment.errors.terms_accepted" class="mt-2 block text-xs text-coral">{{ enrollment.errors.terms_accepted }}</span></div></div>
                <form v-if="modal === 'trial'" @submit.prevent="submitTrial" class="space-y-4"><div class="rounded-2xl p-4 text-sm" :class="course.trial_is_free ? 'bg-green-50 text-green-800' : 'bg-amber-50 text-amber-900'"><strong>{{ course.trial_is_free ? 'Cours d’essai gratuit' : 'Cours d’essai payant · ' + Number(course.trial_price).toLocaleString('fr-CH', { minimumFractionDigits: 2 }) + ' CHF' }}</strong><p class="mt-1 text-xs opacity-70">{{ course.trial_is_free ? 'Aucun montant ne sera demandé pour cet essai.' : course.trial_payment_on_site ? 'Le cours d’essai sera à régler sur place auprès de l’école.' : 'Le règlement sera organisé avec l’école après confirmation de la demande.' }}</p></div><div class="grid gap-3 sm:grid-cols-2"><input v-model="trial.first_name" class="rounded-xl border border-black/10 px-4 py-3" placeholder="Prénom" /><input v-model="trial.last_name" class="rounded-xl border border-black/10 px-4 py-3" placeholder="Nom" /></div><input v-model="trial.email" type="email" class="w-full rounded-xl border border-black/10 px-4 py-3" placeholder="E-mail" /><input v-model="trial.phone" type="tel" class="w-full rounded-xl border border-black/10 px-4 py-3" placeholder="Téléphone" /><div v-if="course.couple_mode" class="grid grid-cols-2 gap-3"><label class="rounded-xl border p-3 text-center" :class="trial.dance_role === 'lead' && 'border-purple-500 bg-purple-50'"><input v-model="trial.dance_role" type="radio" value="lead" class="sr-only" />Lead</label><label class="rounded-xl border p-3 text-center" :class="trial.dance_role === 'follow' && 'border-purple-500 bg-purple-50'"><input v-model="trial.dance_role" type="radio" value="follow" class="sr-only" />Follow</label></div><select v-model="trial.preferred_date" class="w-full rounded-xl border border-black/10 bg-white px-4 py-3"><option v-for="lesson in course.lessons" :key="lesson.id" :value="lesson.lesson_date">{{ formatDate(lesson.lesson_date) }}</option></select><textarea v-model="trial.message" rows="3" class="w-full rounded-xl border border-black/10 px-4 py-3" placeholder="Message facultatif"></textarea><p v-if="Object.keys(trial.errors).length" class="text-sm text-coral">{{ Object.values(trial.errors)[0] }}</p><button class="w-full rounded-full bg-ink py-3.5 font-bold text-white">Envoyer ma demande</button></form>
                <form v-else @submit.prevent="submitEnrollment" class="space-y-4"><div class="grid gap-3 sm:grid-cols-2"><input v-model="enrollment.first_name" class="rounded-xl border border-black/10 px-4 py-3" placeholder="Prénom" /><input v-model="enrollment.last_name" class="rounded-xl border border-black/10 px-4 py-3" placeholder="Nom" /></div><input v-model="enrollment.email" type="email" class="w-full rounded-xl border border-black/10 px-4 py-3" placeholder="E-mail" /><input v-model="enrollment.phone" type="tel" class="w-full rounded-xl border border-black/10 px-4 py-3" placeholder="Téléphone" /><div class="rounded-2xl border border-black/10 bg-[#faf9f6] p-4"><label class="flex cursor-pointer items-center gap-3 text-sm font-semibold"><input v-model="enrollment.is_minor" type="checkbox" class="h-5 w-5 shrink-0 accent-coral" /><span>Je suis mineur</span></label><div v-if="enrollment.is_minor" class="mt-4 grid gap-3 border-t border-black/5 pt-4 sm:grid-cols-2"><label class="text-sm font-semibold">Prénom du représentant légal<input v-model="enrollment.legal_guardian_first_name" class="mt-2 w-full rounded-xl border border-black/10 px-4 py-3 font-normal" /><span v-if="enrollment.errors.legal_guardian_first_name" class="mt-1 block text-xs text-coral">{{ enrollment.errors.legal_guardian_first_name }}</span></label><label class="text-sm font-semibold">Nom du représentant légal<input v-model="enrollment.legal_guardian_last_name" class="mt-2 w-full rounded-xl border border-black/10 px-4 py-3 font-normal" /><span v-if="enrollment.errors.legal_guardian_last_name" class="mt-1 block text-xs text-coral">{{ enrollment.errors.legal_guardian_last_name }}</span></label></div></div><div v-if="course.couple_mode" class="grid grid-cols-2 gap-3"><label class="rounded-xl border p-3 text-center" :class="enrollment.dance_role === 'lead' && 'border-purple-500 bg-purple-50'"><input v-model="enrollment.dance_role" type="radio" value="lead" class="sr-only" />Lead</label><label class="rounded-xl border p-3 text-center" :class="enrollment.dance_role === 'follow' && 'border-purple-500 bg-purple-50'"><input v-model="enrollment.dance_role" type="radio" value="follow" class="sr-only" />Follow</label></div><label class="block text-sm font-semibold">Date de début<input v-model="enrollment.start_date" type="date" :min="course.start_date" :max="course.end_date" class="mt-2 w-full rounded-xl border border-black/10 px-4 py-3" /></label><select v-if="pricingCategories.length" v-model="enrollment.pricing_category_id" class="w-full rounded-xl border border-black/10 bg-white px-4 py-3"><option value="">Tarif standard · {{ Number(course.session_price).toLocaleString('fr-CH') }} CHF</option><option v-for="category in pricingCategories" :key="category.id" :value="category.id">{{ category.name }} · {{ Number(category.pivot.price).toLocaleString('fr-CH') }} CHF</option></select><select v-if="paymentPlans.length" v-model="enrollment.payment_plan_id" class="w-full rounded-xl border border-black/10 bg-white px-4 py-3"><option value="">Paiement en une fois</option><option v-for="plan in paymentPlans" :key="plan.id" :value="plan.id">{{ plan.name }} · {{ plan.installment_count }} fois</option></select><div class="rounded-xl bg-[#f5f3ee] p-4"><p class="text-xs text-black/45">Estimation selon la date choisie</p><strong class="font-serif text-2xl">{{ displayedPrice.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }} CHF</strong><p class="text-xs text-black/40">{{ serverQuote?.remainingLessons ?? remainingLessons }} leçons restantes</p><p v-if="serverQuote?.registrationFeeAmount > 0" class="mt-3 flex justify-between border-t border-black/10 pt-3 text-xs"><span>{{ serverQuote.registrationFeeName }}</span><strong>+ {{ serverQuote.registrationFeeAmount.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }} CHF</strong></p><p v-else-if="quoteLoading" class="mt-2 text-[10px] text-black/35">Vérification des rabais et frais…</p></div><p v-if="Object.keys(enrollment.errors).length" class="text-sm text-coral">{{ Object.values(enrollment.errors)[0] }}</p><button class="w-full rounded-full bg-ink py-3.5 font-bold text-white">Confirmer mon inscription</button></form>
            </div></div>
    </div>
</template>
