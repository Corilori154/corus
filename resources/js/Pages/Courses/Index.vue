<script setup>
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref, watch } from 'vue';

const props = defineProps({ courses: Array, school: Object, paymentPlans: Array });
const page = usePage();
const activeStyle = ref('Tous');
const activeLevel = ref('Tous');
const activeLocation = ref('Tous');
const search = ref('');
const selectedCourse = ref(null);
const selectedTrial = ref(null);
const waitlistResult = ref(null);
const menuOpen = ref(false);
const accountUrl = computed(() => {
    const user = page.props.auth?.user;
    if (!user) return `/ecole/${props.school.slug}/connexion`;
    if (user.is_admin) return '/admin';
    return `/ecole/${user.school.slug}/mon-espace`;
});

const styles = computed(() => ['Tous', ...new Set(props.courses.map(course => course.style))]);
const levels = computed(() => ['Tous', ...new Set(props.courses.map(course => course.level))]);
const locations = computed(() => ['Tous', ...new Set(props.courses.map(course => course.location))]);
const filteredCourses = computed(() => props.courses.filter(course => {
    const matchesStyle = activeStyle.value === 'Tous' || course.style === activeStyle.value;
    const matchesLevel = activeLevel.value === 'Tous' || course.level === activeLevel.value;
    const matchesLocation = activeLocation.value === 'Tous' || course.location === activeLocation.value;
    const term = search.value.toLocaleLowerCase('fr');
    const matchesSearch = !term || `${course.title} ${course.teacher} ${course.day}`.toLocaleLowerCase('fr').includes(term);
    return matchesStyle && matchesLevel && matchesLocation && matchesSearch;
}));

const dayOrder = ['lundi', 'mardi', 'mercredi', 'jeudi', 'vendredi', 'samedi', 'dimanche'];
const dayThemes = [
    { accent: '#d95f6f', soft: '#fce8e9', border: '#f2c3c8' },
    { accent: '#c26a2e', soft: '#fbeedc', border: '#efd1ad' },
    { accent: '#2f7d68', soft: '#e2f2ed', border: '#b9ded3' },
    { accent: '#3972a8', soft: '#e6f0f9', border: '#bfd6eb' },
    { accent: '#7259a6', soft: '#eee9f8', border: '#d4c8eb' },
    { accent: '#a04f7a', soft: '#f8e8f0', border: '#e9c5d8' },
    { accent: '#66703f', soft: '#f0f1df', border: '#d9dcba' },
];
const normalizeDay = day => (day || '').toLocaleLowerCase('fr').normalize('NFD').replace(/[\u0300-\u036f]/g, '').trim();
const timeValue = time => {
    const match = String(time || '').match(/(\d{1,2})(?:[h:.](\d{2}))?/i);
    return match ? Number(match[1]) * 60 + Number(match[2] || 0) : Number.MAX_SAFE_INTEGER;
};
const groupedCourses = computed(() => {
    const groups = new Map();
    filteredCourses.value.forEach(course => {
        const key = normalizeDay(course.day);
        if (!groups.has(key)) groups.set(key, { key, label: course.day || 'Autres jours', courses: [] });
        groups.get(key).courses.push(course);
    });

    return [...groups.values()]
        .map((group, index) => {
            const weekdayIndex = dayOrder.indexOf(group.key);
            return {
                ...group,
                order: weekdayIndex === -1 ? dayOrder.length + index : weekdayIndex,
                theme: dayThemes[weekdayIndex === -1 ? index % dayThemes.length : weekdayIndex],
                courses: group.courses.sort((a, b) => timeValue(a.time) - timeValue(b.time) || a.title.localeCompare(b.title, 'fr')),
            };
        })
        .sort((a, b) => a.order - b.order);
});
const openCourse = course => router.visit(`/ecole/${props.school.slug}/cours/${course.id}`);

const form = useForm({ course_id: null, first_name: '', last_name: '', email: '', phone: '', is_minor: false, legal_guardian_first_name: '', legal_guardian_last_name: '', start_date: '', dance_role: '', pricing_category_id: '', payment_plan_id: '', comment: '', terms_accepted: false });
const trialForm = useForm({ course_id: null, first_name: '', last_name: '', email: '', phone: '', dance_role: '', preferred_date: '', message: '' });
const trialLessons = computed(() => selectedTrial.value?.lessons || []);
const serverQuote = ref(null);
const quoteLoading = ref(false);
let quoteTimer;
const localQuote = computed(() => {
    if (!selectedCourse.value || !form.start_date) return null;
    const lessons = selectedCourse.value.lessons || [];
    const remaining = lessons.filter(lesson => lesson.lesson_date >= form.start_date).length;
    const listAmount = lessons.length ? Number(selectedCourse.value.session_price) * remaining / lessons.length : 0;
    const category = (selectedCourse.value.pricing_categories || []).find(item => item.id === Number(form.pricing_category_id));
    const categoryDiscount = 0;
    const amount = category && lessons.length ? Number(category.pivot.price) * remaining / lessons.length : listAmount;
    return { remaining, total: lessons.length, listAmount, categoryDiscount, categoryName: category?.name, baseAmount: amount, amount, discountAmount: 0, discountPercentage: 0, discountType: 'percentage', discountValue: 0, courseCount: 1 };
});
const priceQuote = computed(() => serverQuote.value || localQuote.value);
watch([() => form.email, () => form.start_date, () => form.pricing_category_id, () => form.payment_plan_id, () => selectedCourse.value?.id], () => {
    clearTimeout(quoteTimer);
    serverQuote.value = null;
    if (!selectedCourse.value || !form.start_date || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(form.email)) return;
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
            body: JSON.stringify({ email: form.email, course_id: selectedCourse.value.id, start_date: form.start_date, pricing_category_id: form.pricing_category_id || null, payment_plan_id: form.payment_plan_id || null }),
        });
        if (!response.ok) return;
        const quote = await response.json();
        serverQuote.value = {
            remaining: quote.remaining_lessons, total: quote.total_lessons,
            baseAmount: Number(quote.base_amount), amount: Number(quote.amount),
            listAmount: Number(quote.list_amount), categoryDiscount: Number(quote.category_discount_amount), categoryName: quote.category_name,
            discountAmount: Number(quote.discount_amount), discountPercentage: Number(quote.discount_percentage),
            discountType: quote.discount_type, discountValue: Number(quote.discount_value),
            courseCount: quote.course_count,
            paymentPlanName: quote.payment_plan_name, paymentAdjustment: Number(quote.payment_adjustment_amount),
            registrationFeeName: quote.registration_fee_name, registrationFeeAmount: Number(quote.registration_fee_amount),
            installmentCount: quote.installment_count, installmentAmount: Number(quote.installment_amount),
        };
    } finally {
        quoteLoading.value = false;
    }
}

function openEnrollment(course) {
    selectedCourse.value = course;
    form.course_id = course.id;
    form.start_date = course.start_date;
    serverQuote.value = null;
    form.clearErrors();
}

function closeEnrollment() {
    selectedCourse.value = null;
    form.reset();
}

function openTrial(course) {
    selectedTrial.value = course;
    trialForm.course_id = course.id;
    trialForm.preferred_date = course.lessons[0]?.lesson_date || '';
    trialForm.clearErrors();
}

function closeTrial() {
    selectedTrial.value = null;
    trialForm.reset();
}

function submitTrial() {
    trialForm.post(`/ecole/${props.school.slug}/cours-essai`, { preserveScroll: true, onSuccess: closeTrial });
}

function submit() {
    form.post(`/ecole/${props.school.slug}/inscriptions`, {
        preserveScroll: true,
        onSuccess: (response) => {
            const waitlistMessage = response.props.flash?.waitlist;
            closeEnrollment();
            if (waitlistMessage) waitlistResult.value = waitlistMessage;
        },
    });
}
</script>

<template>
    <Head title="Cours de danse" />

    <div class="min-h-screen overflow-hidden bg-[#fbfaf6]">
        <div class="pointer-events-none absolute -right-32 top-28 h-80 w-80 rounded-full bg-[#f4d9d8]/50 blur-3xl"></div>

        <header class="relative z-20 border-b border-black/5 bg-[#fbfaf6]/90 backdrop-blur-xl">
            <nav class="mx-auto flex h-20 max-w-7xl items-center justify-between px-5 lg:px-8">
                <a href="/" class="flex items-center gap-3" aria-label="Corus, accueil">
                    <span class="grid h-10 w-10 place-items-center rounded-full bg-ink text-xl text-white">♪</span>
                    <span class="font-serif text-2xl font-semibold tracking-tight">{{ school.name }}<span class="text-coral">.</span></span>
                </a>

                <div class="hidden items-center gap-8 text-sm font-medium md:flex">
                    <a href="#cours" class="text-coral">Les cours</a>
                </div>

                <div class="hidden items-center gap-3 md:flex">
                    <a :href="accountUrl" class="rounded-full px-4 py-2 text-sm font-semibold transition hover:bg-black/5">{{ page.props.auth?.user ? 'Mon espace' : 'Se connecter' }}</a>
                </div>
                <button class="grid h-10 w-10 place-items-center rounded-full border border-black/10 md:hidden" @click="menuOpen = !menuOpen" aria-label="Ouvrir le menu">☰</button>
            </nav>
            <div v-if="menuOpen" class="border-t border-black/5 px-5 py-4 md:hidden">
                <div class="flex flex-col gap-3 text-sm font-medium"><a href="#cours">Les cours</a><a :href="accountUrl" class="text-left">{{ page.props.auth?.user ? 'Mon espace' : 'Se connecter' }}</a></div>
            </div>
        </header>

        <main class="relative">
            <section id="cours" class="mx-auto max-w-7xl px-5 pb-24 pt-10 lg:px-8 lg:pt-14">
                <div v-if="page.props.flash?.success" class="mb-8 flex items-center justify-between rounded-2xl bg-[#e3f2e9] px-5 py-4 text-sm font-semibold text-[#27623f]">
                    <span>✓ {{ page.props.flash.success }}</span>
                </div>
                <div v-if="page.props.flash?.waitlist" class="mb-8 rounded-2xl border border-purple-200 bg-purple-50 px-5 py-5 text-purple-900">
                    <div class="flex items-start gap-3"><span class="grid h-8 w-8 shrink-0 place-items-center rounded-full bg-purple-600 font-bold text-white">!</span><div><strong class="block">Inscription sur liste d’attente</strong><p class="mt-1 text-sm leading-relaxed text-purple-700">{{ page.props.flash.waitlist }}</p></div></div>
                </div>

                <div class="mb-8 flex flex-col gap-5 border-y border-black/10 py-5 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex gap-2 overflow-x-auto pb-1">
                        <button v-for="style in styles" :key="style" @click="activeStyle = style" class="whitespace-nowrap rounded-full px-4 py-2 text-sm font-semibold transition" :class="activeStyle === style ? 'bg-ink text-white' : 'bg-white hover:bg-black/5'">{{ style }}</button>
                    </div>
                    <div class="flex flex-wrap gap-2"><select v-model="activeLevel" class="rounded-full border border-black/10 bg-white px-4 py-2.5 text-sm font-semibold"><option v-for="level in levels" :key="level" :value="level">Niveau : {{ level }}</option></select><select v-model="activeLocation" class="rounded-full border border-black/10 bg-white px-4 py-2.5 text-sm font-semibold"><option v-for="location in locations" :key="location" :value="location">Lieu : {{ location }}</option></select></div>
                    <label class="relative block min-w-64">
                        <span class="absolute left-4 top-1/2 -translate-y-1/2 text-black/35">⌕</span>
                        <input v-model="search" type="search" placeholder="Rechercher un cours..." class="w-full rounded-full border border-black/10 bg-white py-2.5 pl-10 pr-4 text-sm focus:border-coral" />
                    </label>
                </div>

                <div class="mb-7 flex items-baseline justify-between">
                    <h2 class="font-serif text-3xl sm:text-4xl">Nos cours</h2>
                    <p class="text-sm text-black/45">{{ filteredCourses.length }} cours disponible{{ filteredCourses.length > 1 ? 's' : '' }}</p>
                </div>

                <div v-if="filteredCourses.length" class="space-y-10">
                    <section v-for="group in groupedCourses" :key="group.key" class="overflow-hidden rounded-[2rem] border bg-white/60" :style="{ borderColor: group.theme.border }">
                        <header class="flex items-center justify-between gap-4 px-5 py-5 sm:px-7" :style="{ backgroundColor: group.theme.soft }">
                            <div class="flex items-center gap-4">
                                <span class="h-11 w-1.5 rounded-full" :style="{ backgroundColor: group.theme.accent }"></span>
                                <div>
                                    <p class="text-[10px] font-bold uppercase tracking-[.2em] text-black/45">Planning du jour</p>
                                    <h3 class="font-serif text-3xl capitalize sm:text-4xl">{{ group.label }}</h3>
                                </div>
                            </div>
                            <span class="rounded-full bg-white/80 px-3 py-1.5 text-xs font-bold" :style="{ color: group.theme.accent }">{{ group.courses.length }} cours</span>
                        </header>

                        <div class="grid gap-4 p-4 sm:p-5 lg:grid-cols-2">
                            <article v-for="course in group.courses" :key="course.id" tabindex="0" role="link" :aria-label="`Voir les détails du cours ${course.title}`" class="group cursor-pointer rounded-3xl border border-black/5 bg-white p-4 transition duration-300 hover:-translate-y-0.5 hover:shadow-lg focus:outline-none focus:ring-2 sm:p-5" :style="{ '--tw-ring-color': group.theme.accent }" @click="openCourse(course)" @keydown.enter.self="openCourse(course)">
                                <div class="flex gap-4">
                                    <div class="w-20 shrink-0 sm:w-24">
                                        <p class="font-serif text-2xl font-semibold leading-none" :style="{ color: group.theme.accent }">{{ course.time }}</p>
                                        <p class="mt-2 text-[11px] font-semibold leading-tight text-black/45">{{ course.location }}</p>
                                    </div>
                                    <div class="min-w-0 flex-1 border-l pl-4" :style="{ borderColor: group.theme.border }">
                                        <div class="flex items-start justify-between gap-3">
                                            <div class="min-w-0"><p class="text-[10px] font-bold uppercase tracking-[.16em]" :style="{ color: group.theme.accent }">{{ course.style }} · {{ course.level }}</p><h4 class="mt-1 font-serif text-xl leading-tight sm:text-2xl">{{ course.title }}</h4><p class="mt-1 text-xs text-black/45">avec {{ course.teacher }}</p></div>
                                            <img :src="course.image" :alt="course.title" class="h-14 w-14 shrink-0 rounded-2xl object-cover sm:h-16 sm:w-16" />
                                        </div>
                                        <div class="mt-4 flex flex-wrap items-end justify-between gap-3">
                                            <div><p class="text-sm font-bold">{{ Number(course.session_price).toLocaleString('fr-FR') }} CHF</p><p class="text-[10px] text-black/40">{{ course.lessons.length }} leçons · session complète</p><p class="mt-1 text-xs font-semibold" :class="course.places === 0 ? 'text-black/35' : course.places <= 3 ? 'text-coral' : 'text-black/50'">{{ course.places === 0 ? 'Cours complet' : `${course.places} places restantes` }}</p></div>
                                            <div class="flex flex-wrap justify-end gap-2"><button v-if="course.trial_enabled" @click.stop="openTrial(course)" class="rounded-full border border-black/10 px-3 py-2 text-xs font-bold transition hover:border-coral hover:text-coral">{{ course.trial_is_free ? 'Essai gratuit' : `Essai · ${Number(course.trial_price).toLocaleString('fr-CH')} CHF` }}</button><button :disabled="course.places === 0" @click.stop="openEnrollment(course)" class="rounded-full px-3 py-2 text-xs font-bold transition" :class="course.places === 0 ? 'cursor-not-allowed bg-black/5 text-black/30' : 'bg-ink text-white hover:bg-coral'">S’inscrire</button></div>
                                        </div>
                                    </div>
                                </div>
                            </article>
                        </div>
                    </section>
                </div>
                <div v-else class="rounded-3xl border border-dashed border-black/15 py-20 text-center"><p class="font-serif text-2xl">Aucun cours trouvé</p><button @click="search = ''; activeStyle = 'Tous'; activeLevel = 'Tous'; activeLocation = 'Tous'" class="mt-3 text-sm font-bold text-coral">Réinitialiser les filtres</button></div>
            </section>

            <section id="studio" class="bg-ink px-5 py-16 text-white">
                <div class="mx-auto flex max-w-7xl flex-col items-start justify-between gap-8 sm:flex-row sm:items-center lg:px-3">
                    <div><p class="mb-2 text-xs font-bold uppercase tracking-[.2em] text-coral">{{ school.city || 'Votre école de danse' }}</p><h2 class="max-w-2xl font-serif text-3xl sm:text-4xl">Une question sur nos cours ? Notre équipe vous accompagne.</h2></div>
                    <a :href="school.contact_button_url || `mailto:${school.email}`" class="whitespace-nowrap rounded-full bg-white px-6 py-3 font-bold text-ink transition hover:bg-coral hover:text-white">{{ school.contact_button_label || 'Nous contacter' }} →</a>
                </div>
            </section>
        </main>

        <div v-if="selectedCourse" class="fixed inset-0 z-50 grid place-items-center bg-black/55 p-4 backdrop-blur-sm" @click.self="closeEnrollment">
            <div class="max-h-[92vh] w-full max-w-lg overflow-y-auto rounded-[2rem] bg-white p-6 soft-shadow sm:p-8">
                <div class="mb-6 flex items-start justify-between gap-5">
                    <div><p class="text-xs font-bold uppercase tracking-[.18em] text-coral">Votre cours d’essai</p><h2 class="mt-1 font-serif text-3xl">{{ selectedCourse.title }}</h2><p class="mt-2 text-sm text-black/50">{{ selectedCourse.day }} · {{ selectedCourse.time }}</p></div>
                    <button @click="closeEnrollment" class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-black/5 text-xl" aria-label="Fermer">×</button>
                </div>
                <form @submit.prevent="submit" class="space-y-4">
                    <div class="grid gap-4 sm:grid-cols-2">
                        <label class="text-sm font-semibold">Prénom<input v-model="form.first_name" type="text" autocomplete="given-name" class="mt-2 w-full rounded-xl border border-black/10 px-4 py-3 font-normal focus:border-coral" placeholder="Camille" /><span v-if="form.errors.first_name" class="mt-1 block text-xs text-coral">{{ form.errors.first_name }}</span></label>
                        <label class="text-sm font-semibold">Nom<input v-model="form.last_name" type="text" autocomplete="family-name" class="mt-2 w-full rounded-xl border border-black/10 px-4 py-3 font-normal focus:border-coral" placeholder="Dupont" /><span v-if="form.errors.last_name" class="mt-1 block text-xs text-coral">{{ form.errors.last_name }}</span></label>
                    </div>
                    <label class="block text-sm font-semibold">Adresse e-mail<input v-model="form.email" type="email" autocomplete="email" class="mt-2 w-full rounded-xl border border-black/10 px-4 py-3 font-normal focus:border-coral" placeholder="camille@exemple.fr" /><span v-if="form.errors.email" class="mt-1 block text-xs text-coral">{{ form.errors.email }}</span></label>
                    <label class="block text-sm font-semibold">Numéro de téléphone<input v-model="form.phone" type="tel" autocomplete="tel" class="mt-2 w-full rounded-xl border border-black/10 px-4 py-3 font-normal focus:border-coral" placeholder="+41 79 123 45 67" /><span v-if="form.errors.phone" class="mt-1 block text-xs text-coral">{{ form.errors.phone }}</span></label>
                    <div class="rounded-2xl border border-black/10 bg-[#faf9f6] p-4">
                        <label class="flex cursor-pointer items-center gap-3 text-sm font-semibold"><input v-model="form.is_minor" type="checkbox" class="h-5 w-5 shrink-0 accent-coral" /><span>Je suis mineur</span></label>
                        <div v-if="form.is_minor" class="mt-4 grid gap-4 border-t border-black/5 pt-4 sm:grid-cols-2">
                            <label class="text-sm font-semibold">Prénom du représentant légal<input v-model="form.legal_guardian_first_name" type="text" class="mt-2 w-full rounded-xl border border-black/10 bg-white px-4 py-3 font-normal focus:border-coral" /><span v-if="form.errors.legal_guardian_first_name" class="mt-1 block text-xs text-coral">{{ form.errors.legal_guardian_first_name }}</span></label>
                            <label class="text-sm font-semibold">Nom du représentant légal<input v-model="form.legal_guardian_last_name" type="text" class="mt-2 w-full rounded-xl border border-black/10 bg-white px-4 py-3 font-normal focus:border-coral" /><span v-if="form.errors.legal_guardian_last_name" class="mt-1 block text-xs text-coral">{{ form.errors.legal_guardian_last_name }}</span></label>
                        </div>
                    </div>
                    <label v-if="selectedCourse.pricing_categories?.length" class="block text-sm font-semibold">Catégorie tarifaire<select v-model="form.pricing_category_id" class="mt-2 w-full rounded-xl border border-black/10 bg-white px-4 py-3 font-normal focus:border-coral"><option value="">Tarif standard · {{ Number(selectedCourse.session_price).toLocaleString('fr-CH') }} CHF</option><option v-for="category in selectedCourse.pricing_categories" :key="category.id" :value="category.id">{{ category.name }} · {{ Number(category.pivot.price).toLocaleString('fr-CH') }} CHF</option></select><span v-if="form.errors.pricing_category_id" class="mt-1 block text-xs text-coral">{{ form.errors.pricing_category_id }}</span></label>
                    <label v-if="paymentPlans.length" class="block text-sm font-semibold">Plan de paiement<select v-model="form.payment_plan_id" class="mt-2 w-full rounded-xl border border-black/10 bg-white px-4 py-3 font-normal focus:border-coral"><option value="">Paiement en une fois</option><option v-for="plan in paymentPlans" :key="plan.id" :value="plan.id">{{ `${plan.name} · ${plan.installment_count} fois${Number(plan.adjustment_value) ? ` · ${plan.adjustment_direction === 'fee' ? '+' : '−'}${Number(plan.adjustment_value)}${plan.adjustment_mode === 'percentage' ? ' %' : ' CHF'}` : ''}` }}</option></select><span v-if="form.errors.payment_plan_id" class="mt-1 block text-xs text-coral">{{ form.errors.payment_plan_id }}</span></label>
                    <fieldset v-if="selectedCourse.couple_mode"><legend class="text-sm font-semibold">Votre rôle</legend><div class="mt-2 grid grid-cols-2 gap-3"><label class="cursor-pointer rounded-xl border p-4 text-center transition" :class="form.dance_role === 'lead' ? 'border-purple-500 bg-purple-50 text-purple-800' : 'border-black/10'"><input v-model="form.dance_role" type="radio" value="lead" class="sr-only" /><strong>Lead</strong><span class="mt-1 block text-xs opacity-55">Je guide</span></label><label class="cursor-pointer rounded-xl border p-4 text-center transition" :class="form.dance_role === 'follow' ? 'border-purple-500 bg-purple-50 text-purple-800' : 'border-black/10'"><input v-model="form.dance_role" type="radio" value="follow" class="sr-only" /><strong>Follow</strong><span class="mt-1 block text-xs opacity-55">Je suis guidé·e</span></label></div><span v-if="form.errors.dance_role" class="mt-1 block text-xs text-coral">{{ form.errors.dance_role }}</span></fieldset>
                    <label class="block text-sm font-semibold">Je souhaite commencer le<input v-model="form.start_date" type="date" :min="selectedCourse.start_date" :max="selectedCourse.end_date" class="mt-2 w-full rounded-xl border border-black/10 px-4 py-3 font-normal focus:border-coral" /><span v-if="form.errors.start_date" class="mt-1 block text-xs text-coral">{{ form.errors.start_date }}</span></label>
                    <div v-if="priceQuote" class="rounded-2xl bg-ink p-4 text-white"><div class="flex items-end justify-between gap-4"><div><p class="text-xs text-white/50">{{ priceQuote.discountAmount > 0 ? `Tarif avec rabais multi-cours (${priceQuote.discountType === 'fixed' ? `${priceQuote.discountValue.toLocaleString('fr-CH', { minimumFractionDigits: 2 })} CHF fixe` : `${priceQuote.discountPercentage} %`})` : priceQuote.categoryName ? `Tarif ${priceQuote.categoryName}` : 'Tarif selon la date de début' }}</p><div class="mt-1 flex items-baseline gap-2"><span v-if="priceQuote.discountAmount > 0 || priceQuote.categoryDiscount > 0" class="text-sm text-white/40 line-through">{{ (priceQuote.categoryDiscount > 0 ? priceQuote.listAmount : priceQuote.baseAmount).toLocaleString('fr-FR', { minimumFractionDigits: 2 }) }} CHF</span><strong class="font-serif text-3xl">{{ priceQuote.amount.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }} CHF</strong></div></div><p class="text-right text-xs text-white/60">{{ priceQuote.remaining }} leçons restantes<br>sur {{ priceQuote.total }} planifiées</p></div><div class="mt-3 h-1.5 overflow-hidden rounded-full bg-white/15"><div class="h-full rounded-full bg-coral" :style="{ width: `${priceQuote.total ? priceQuote.remaining / priceQuote.total * 100 : 0}%` }"></div></div><p v-if="priceQuote.categoryDiscount > 0" class="mt-2 text-xs font-semibold text-[#9de0b3]">✓ {{ priceQuote.categoryDiscount.toLocaleString('fr-FR', { minimumFractionDigits: 2 }) }} CHF de tarif {{ priceQuote.categoryName }}</p><p v-if="priceQuote.discountAmount > 0" class="mt-1 text-xs font-semibold text-[#9de0b3]">✓ {{ priceQuote.discountAmount.toLocaleString('fr-FR', { minimumFractionDigits: 2 }) }} CHF de rabais multi-cours</p><p v-if="priceQuote.registrationFeeAmount > 0" class="mt-2 flex justify-between border-t border-white/15 pt-2 text-xs"><span>{{ priceQuote.registrationFeeName }}</span><strong>+ {{ priceQuote.registrationFeeAmount.toLocaleString('fr-FR', { minimumFractionDigits: 2, maximumFractionDigits: 2 }) }} CHF</strong></p><p v-else-if="quoteLoading" class="mt-2 text-[10px] text-white/40">Vérification de vos rabais et frais…</p></div>
                    <div v-if="priceQuote?.installmentCount > 1" class="rounded-xl bg-[#eef0f8] p-4 text-sm text-[#31395d]"><strong>{{ priceQuote.paymentPlanName }} : {{ priceQuote.installmentCount }} échéances de {{ priceQuote.installmentAmount.toLocaleString('fr-FR', { minimumFractionDigits: 2 }) }} CHF</strong><p v-if="priceQuote.paymentAdjustment" class="mt-1 text-xs opacity-65">{{ priceQuote.paymentAdjustment > 0 ? 'Frais ajoutés' : 'Remise appliquée' }} : {{ Math.abs(priceQuote.paymentAdjustment).toLocaleString('fr-FR', { minimumFractionDigits: 2 }) }} CHF</p></div>
                    <label class="block text-sm font-semibold">Commentaire <span class="font-normal text-black/35">(facultatif)</span><textarea v-model="form.comment" rows="3" maxlength="2000" class="mt-2 w-full resize-none rounded-xl border border-black/10 px-4 py-3 font-normal" placeholder="Une information utile pour l’école…"></textarea><span v-if="form.errors.comment" class="mt-1 block text-xs text-coral">{{ form.errors.comment }}</span></label>
                    <div class="rounded-xl border border-black/10 p-4"><details v-if="school.terms_and_conditions" class="mb-3"><summary class="cursor-pointer text-sm font-bold text-coral">Lire les conditions générales</summary><div class="mt-3 max-h-48 overflow-y-auto whitespace-pre-wrap rounded-lg bg-[#f7f5f0] p-3 text-xs leading-relaxed text-black/60">{{ school.terms_and_conditions }}</div></details><label class="flex cursor-pointer items-start gap-3 text-sm"><input v-model="form.terms_accepted" type="checkbox" class="mt-0.5 h-5 w-5 shrink-0 accent-coral" /><span>J’ai lu et j’accepte les conditions générales de l’école.</span></label><span v-if="form.errors.terms_accepted" class="mt-2 block text-xs text-coral">{{ form.errors.terms_accepted }}</span></div>
                    <button type="submit" :disabled="form.processing" class="w-full rounded-full bg-ink py-3.5 font-bold text-white transition hover:bg-coral disabled:opacity-50">{{ form.processing ? 'Envoi en cours…' : 'Confirmer mon inscription' }}</button>
                </form>
            </div>
        </div>

        <div v-if="selectedTrial" class="fixed inset-0 z-50 grid place-items-center bg-black/55 p-4 backdrop-blur-sm" @click.self="closeTrial">
            <div class="max-h-[92vh] w-full max-w-lg overflow-y-auto rounded-[2rem] bg-white p-6 soft-shadow sm:p-8"><div class="mb-6 flex items-start justify-between"><div><p class="text-xs font-bold uppercase tracking-[.18em] text-coral">Découvrir le cours</p><h2 class="mt-1 font-serif text-3xl">Cours d’essai</h2><p class="mt-2 text-sm text-black/50">{{ selectedTrial.title }} · {{ selectedTrial.location }}</p></div><button @click="closeTrial" class="grid h-9 w-9 place-items-center rounded-full bg-black/5 text-xl">×</button></div><div class="mb-5 rounded-2xl p-4 text-sm" :class="selectedTrial.trial_is_free ? 'bg-green-50 text-green-800' : 'bg-amber-50 text-amber-900'"><strong>{{ selectedTrial.trial_is_free ? "Cours d'essai gratuit" : `Cours d'essai payant · ${Number(selectedTrial.trial_price).toLocaleString('fr-CH', { minimumFractionDigits: 2 })} CHF` }}</strong><p class="mt-1 text-xs opacity-70">{{ selectedTrial.trial_is_free ? 'Aucun montant ne sera demandé pour cet essai.' : selectedTrial.trial_payment_on_site ? "Le cours d’essai sera à régler sur place auprès de l’école." : "Le règlement sera organisé avec l’école après confirmation de la demande." }}</p></div><form @submit.prevent="submitTrial" class="space-y-4"><div class="grid gap-4 sm:grid-cols-2"><label class="text-sm font-semibold">Prénom<input v-model="trialForm.first_name" class="mt-2 w-full rounded-xl border border-black/10 px-4 py-3 font-normal" /><span v-if="trialForm.errors.first_name" class="text-xs text-coral">{{ trialForm.errors.first_name }}</span></label><label class="text-sm font-semibold">Nom<input v-model="trialForm.last_name" class="mt-2 w-full rounded-xl border border-black/10 px-4 py-3 font-normal" /></label></div><label class="block text-sm font-semibold">E-mail<input v-model="trialForm.email" type="email" class="mt-2 w-full rounded-xl border border-black/10 px-4 py-3 font-normal" /></label><label class="block text-sm font-semibold">Téléphone<input v-model="trialForm.phone" type="tel" class="mt-2 w-full rounded-xl border border-black/10 px-4 py-3 font-normal" placeholder="+41 79 123 45 67" /></label><fieldset v-if="selectedTrial.couple_mode"><legend class="text-sm font-semibold">Votre rôle</legend><div class="mt-2 grid grid-cols-2 gap-3"><label class="cursor-pointer rounded-xl border p-3 text-center" :class="trialForm.dance_role === 'lead' ? 'border-purple-500 bg-purple-50' : 'border-black/10'"><input v-model="trialForm.dance_role" type="radio" value="lead" class="sr-only" />Lead</label><label class="cursor-pointer rounded-xl border p-3 text-center" :class="trialForm.dance_role === 'follow' ? 'border-purple-500 bg-purple-50' : 'border-black/10'"><input v-model="trialForm.dance_role" type="radio" value="follow" class="sr-only" />Follow</label></div><span v-if="trialForm.errors.dance_role" class="text-xs text-coral">{{ trialForm.errors.dance_role }}</span></fieldset><label class="block text-sm font-semibold">Date souhaitée<select v-model="trialForm.preferred_date" class="mt-2 w-full rounded-xl border border-black/10 bg-white px-4 py-3 font-normal"><option v-for="lesson in trialLessons" :key="lesson.id" :value="lesson.lesson_date">{{ new Date(`${lesson.lesson_date}T12:00:00`).toLocaleDateString('fr-FR', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' }) }}</option></select><span v-if="trialForm.errors.preferred_date" class="text-xs text-coral">{{ trialForm.errors.preferred_date }}</span></label><label class="block text-sm font-semibold">Message <span class="font-normal text-black/35">(facultatif)</span><textarea v-model="trialForm.message" rows="3" class="mt-2 w-full resize-none rounded-xl border border-black/10 px-4 py-3 font-normal" placeholder="Une question ou une information utile..."></textarea></label><button :disabled="trialForm.processing" class="w-full rounded-full bg-ink py-3.5 font-bold text-white hover:bg-coral disabled:opacity-50">{{ trialForm.processing ? 'Envoi…' : 'Demander mon cours d’essai' }}</button></form></div>
        </div>

        <div v-if="waitlistResult" class="fixed inset-0 z-[60] grid place-items-center bg-black/65 p-4 backdrop-blur-md">
            <div class="w-full max-w-lg overflow-hidden rounded-[2rem] bg-white text-center soft-shadow">
                <div class="bg-purple-600 px-8 py-8 text-white"><span class="mx-auto grid h-16 w-16 place-items-center rounded-full bg-white/15 text-3xl">⏳</span><p class="mt-4 text-xs font-bold uppercase tracking-[.2em] text-purple-200">Demande bien enregistrée</p><h2 class="mt-2 font-serif text-4xl">Vous êtes sur liste d’attente</h2></div>
                <div class="p-7 sm:p-8"><p class="text-sm leading-relaxed text-black/60">{{ waitlistResult }}</p><div class="mt-5 rounded-2xl bg-purple-50 p-4 text-left"><strong class="text-sm text-purple-900">Pourquoi cette liste d’attente ?</strong><p class="mt-1 text-xs leading-relaxed text-purple-700">Pour les cours de couple, l’école veille à conserver un bon équilibre entre Leads et Follows. Cela garantit plus de temps de danse avec un partenaire et une meilleure qualité de cours pour tout le groupe.</p></div><p class="mt-4 text-xs font-semibold text-black/45">L’école vous contactera dès qu’une place équilibrée se libère. Aucun paiement ne sera demandé avant confirmation.</p><button @click="waitlistResult = null" class="mt-6 w-full rounded-full bg-ink py-3.5 font-bold text-white hover:bg-purple-600">J’ai compris</button></div>
            </div>
        </div>
    </div>
</template>
