<script setup>
import { Head, router, useForm, usePage } from '@inertiajs/vue3';
import { computed, ref } from 'vue';
import InvoicesPanel from '../../Components/Admin/InvoicesPanel.vue';
import PaymentPlansPanel from '../../Components/Admin/PaymentPlansPanel.vue';
import SeasonsPanel from '../../Components/Admin/SeasonsPanel.vue';
import WaitlistPanel from '../../Components/Admin/WaitlistPanel.vue';
import CoursesTable from '../../Components/Admin/CoursesTable.vue';
import EnrollmentsTable from '../../Components/Admin/EnrollmentsTable.vue';
import StudentsTable from '../../Components/Admin/StudentsTable.vue';
import TrialsTable from '../../Components/Admin/TrialsTable.vue';
import DiscountRulesPanel from '../../Components/Admin/DiscountRulesPanel.vue';
import ReferencesPanel from '../../Components/Admin/ReferencesPanel.vue';
import AdministratorsPanel from '../../Components/Admin/AdministratorsPanel.vue';
import PaymentReminderSettings from '../../Components/Admin/PaymentReminderSettings.vue';
import TermsSettings from '../../Components/Admin/TermsSettings.vue';
import RegistrationFeeSettings from '../../Components/Admin/RegistrationFeeSettings.vue';
import ContactButtonSettings from '../../Components/Admin/ContactButtonSettings.vue';

const props = defineProps({ courses: Array, seasons: Array, enrollments: Array, trialRequests: Array, students: Array, discountRules: Array, paymentPlans: Array, invoices: Array, billingSettings: Object, paymentReminderSettings: Object, registrationFeeSettings: Object, contactButtonSettings: Object, termsAndConditions: String, administrators: Array, references: Object, stats: Object });
const page = usePage();
const showForm = ref(false);
const expandedCourse = ref(null);
const editingCourse = ref(null);
const editingLessons = ref([]);
const imagePreview = ref(null);
const adminViews = ['courses', 'seasons', 'enrollments', 'waitlist', 'trials', 'students', 'invoices', 'discounts', 'payments', 'settings', 'administrators'];
const requestedView = new URLSearchParams(window.location.search).get('section');
const activeView = ref(adminViews.includes(requestedView) ? requestedView : 'courses');
const studentSearch = ref('');
const days = ['Lundi', 'Mardi', 'Mercredi', 'Jeudi', 'Vendredi', 'Samedi', 'Dimanche'];
const emptyCourse = { title: '', is_workshop: false, season_id: '', dance_discipline_id: '', dance_level_id: '', school_location_id: '', day: 'Lundi', time: '', start_date: '', end_date: '', teacher: '', description: '', capacity: 12, price: 25, session_price: 750, category_prices: {}, trial_enabled: true, trial_is_free: true, trial_price: 0, trial_payment_on_site: false, image: '', image_upload: null, accent: '#ef6f7f', is_active: true, couple_mode: false, max_role_gap: 2, balance_after_count: 0, waitlist_invitation_hours: 72 };
const form = useForm({ ...emptyCourse });
const discountForm = useForm({ course_count: 2, percentage: 10 });
const paymentPlanForm = useForm({ name: '', installment_count: 2, schedule_mode: 'evenly_spaced', adjustment_direction: 'fee', adjustment_mode: 'fixed', adjustment_value: 0 });
const filteredStudents = computed(() => {
    const term = studentSearch.value.trim().toLocaleLowerCase('fr');
    if (!term) return props.students;
    return props.students.filter(student => `${student.name} ${student.email} ${student.phone || ''} ${student.courses.join(' ')}`.toLocaleLowerCase('fr').includes(term));
});
const logout = () => router.post('/admin/deconnexion');
const currentSeason = () => {
    const now = new Date();
    const today = `${now.getFullYear()}-${String(now.getMonth() + 1).padStart(2, '0')}-${String(now.getDate()).padStart(2, '0')}`;
    return props.seasons.find(season => season.is_active && season.start_date <= today && season.end_date >= today)
        || props.seasons.find(season => season.is_active)
        || null;
};
function applySeasonDates() {
    const season = props.seasons.find(item => item.id === Number(form.season_id));
    form.start_date = season?.start_date || '';
    form.end_date = season?.end_date || '';
}
function setActiveView(view) {
    activeView.value = view;
    router.visit(`/admin?section=${encodeURIComponent(view)}`, {
        preserveState: false,
        preserveScroll: false,
        replace: true,
    });
}
const removeLesson = (course, lesson) => {
    if (confirm(`Retirer la leçon du ${new Date(`${lesson.lesson_date}T12:00:00`).toLocaleDateString('fr-FR')} ?`)) {
        router.delete(`/admin/cours/${course.id}/lecons/${lesson.id}`, {
            preserveScroll: true,
            onSuccess: () => {
                if (editingCourse.value?.id === course.id) {
                    editingLessons.value = editingLessons.value.filter(item => item.id !== lesson.id);
                }
            },
        });
    }
};

function openCreate() {
    const season = currentSeason();
    editingCourse.value = null;
    editingLessons.value = [];
    form.defaults({
        ...emptyCourse,
        season_id: season?.id || '',
        start_date: season?.start_date || '',
        end_date: season?.end_date || '',
    });
    form.reset();
    form.clearErrors();
    imagePreview.value = null;
    showForm.value = true;
}

function openEdit(course) {
    editingCourse.value = course;
    editingLessons.value = [...course.lessons];
    form.defaults({
        title: course.title, is_workshop: course.is_workshop ?? false, season_id: course.season_id, dance_discipline_id: course.dance_discipline_id, dance_level_id: course.dance_level_id, school_location_id: course.school_location_id, day: course.day,
        time: course.time, start_date: course.start_date, end_date: course.end_date,
        teacher: course.teacher, description: course.description || '',
        capacity: course.capacity, price: course.price, session_price: course.session_price,
        category_prices: Object.fromEntries((course.pricing_categories || []).map(category => [category.id, category.pivot.price])),
        trial_enabled: course.trial_enabled ?? true, trial_is_free: course.trial_is_free, trial_price: course.trial_price, trial_payment_on_site: course.trial_payment_on_site ?? false,
        image: course.image, image_upload: null, accent: course.accent, is_active: course.is_active,
        couple_mode: course.couple_mode, max_role_gap: course.max_role_gap ?? 2,
        balance_after_count: course.balance_after_count ?? 0,
        waitlist_invitation_hours: course.waitlist_invitation_hours ?? 72,
    });
    form.reset();
    form.clearErrors();
    imagePreview.value = course.image;
    showForm.value = true;
}

function deleteCourse(course) {
    if (confirm(`Supprimer définitivement « ${course.title} » et ses inscriptions ?`)) {
        router.delete(`/admin/cours/${course.id}`, { preserveScroll: true });
    }
}

function addDiscount() {
    discountForm.post('/admin/rabais', { preserveScroll: true, onSuccess: () => discountForm.reset() });
}

function deleteDiscount(rule) {
    if (confirm(`Supprimer le rabais de ${rule.percentage} % à partir de ${rule.course_count} cours ?`)) {
        router.delete(`/admin/rabais/${rule.id}`, { preserveScroll: true });
    }
}

function addPaymentPlan() {
    paymentPlanForm.post('/admin/plans-paiement', { preserveScroll: true, onSuccess: () => paymentPlanForm.reset() });
}

function deletePaymentPlan(plan) {
    if (confirm(`Supprimer le plan « ${plan.name} » ?`)) router.delete(`/admin/plans-paiement/${plan.id}`, { preserveScroll: true });
}

function submit() {
    const options = {
        preserveScroll: true,
        onSuccess: () => { form.transform(data => data); form.reset(); showForm.value = false; editingCourse.value = null; imagePreview.value = null; },
    };
    if (editingCourse.value) {
        form.transform(data => ({ ...data, _method: 'put' })).post(`/admin/cours/${editingCourse.value.id}`, { ...options, forceFormData: true });
    } else {
        form.transform(data => data).post('/admin/cours', { ...options, forceFormData: true });
    }
}

function selectCourseImage(event) {
    const file = event.target.files?.[0] || null;
    form.image_upload = file;
    imagePreview.value = file ? URL.createObjectURL(file) : (editingCourse.value?.image || null);
}
</script>

<template>
    <Head title="Administration" />
    <div class="min-h-screen bg-[#f5f3ee]">
        <header class="border-b border-black/5 bg-white">
            <div class="mx-auto flex h-20 max-w-7xl items-center justify-between px-5 lg:px-8">
                <a href="/admin" class="flex items-center gap-3"><span class="grid h-10 w-10 place-items-center rounded-full bg-ink text-white">♪</span><span class="font-serif text-2xl font-semibold">Corus</span><span class="ml-2 rounded-full bg-[#f2eee8] px-3 py-1 text-[10px] font-bold uppercase tracking-wider text-black/45">Admin</span></a>
                <div class="flex items-center gap-3"><a :href="`/ecole/${page.props.auth.user.school.slug}`" class="hidden rounded-full px-4 py-2 text-sm font-semibold hover:bg-black/5 sm:block">Voir ma page ↗</a><button @click="logout" class="rounded-full border border-black/10 px-4 py-2 text-sm font-semibold hover:border-coral hover:text-coral">Déconnexion</button></div>
            </div>
        </header>

        <main class="mx-auto max-w-[1600px] px-5 py-10 lg:pl-72 lg:pr-8">
            <div class="mb-10 flex flex-col justify-between gap-5 sm:flex-row sm:items-end">
                <div><p class="text-xs font-bold uppercase tracking-[.2em] text-coral">{{ page.props.auth.user.school.name }}</p><h1 class="mt-2 font-serif text-4xl sm:text-5xl">Bonjour, {{ page.props.auth.user.name.split(' ')[0] }}.</h1><p class="mt-2 text-black/50">Voici un aperçu des activités de votre école.</p></div>
                <button @click="openCreate" class="rounded-full bg-ink px-6 py-3.5 text-sm font-bold text-white transition hover:bg-coral">＋ Créer un cours</button>
            </div>

            <div v-if="page.props.flash?.success" class="mb-7 rounded-2xl bg-[#dff0e5] px-5 py-4 text-sm font-semibold text-[#286342]">✓ {{ page.props.flash.success }}</div>

            <div class="mb-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                <div class="rounded-3xl bg-white p-6"><p class="text-sm text-black/45">Cours au total</p><strong class="mt-3 block font-serif text-4xl">{{ stats.courses }}</strong></div>
                <div class="rounded-3xl bg-white p-6"><p class="text-sm text-black/45">Cours publiés</p><strong class="mt-3 block font-serif text-4xl">{{ stats.active }}</strong></div>
                <div class="rounded-3xl bg-ink p-6 text-white"><p class="text-sm text-white/50">Places disponibles</p><strong class="mt-3 block font-serif text-4xl">{{ stats.places }}</strong></div>
                <div class="rounded-3xl bg-[#eed5d5] p-6"><p class="text-sm text-black/45">Inscriptions reçues</p><strong class="mt-3 block font-serif text-4xl">{{ stats.enrollments }}</strong></div>
            </div>

            <nav aria-label="Navigation admin" class="mb-5 flex gap-2 overflow-x-auto pb-2 lg:fixed lg:bottom-0 lg:left-0 lg:top-20 lg:z-20 lg:mb-0 lg:w-64 lg:flex-col lg:overflow-y-auto lg:border-r lg:border-black/5 lg:bg-white lg:p-5 lg:pb-8 [&>button]:shrink-0 lg:[&>button]:w-full lg:[&>button]:rounded-xl lg:[&>button]:px-4 lg:[&>button]:py-3 lg:[&>button]:text-left"><button @click="setActiveView('courses')" class="rounded-full px-5 py-2.5 text-sm font-bold" :class="activeView === 'courses' ? 'bg-ink text-white' : 'bg-white text-black/55'">Cours</button><button @click="setActiveView('seasons')" class="rounded-full px-5 py-2.5 text-sm font-bold" :class="activeView === 'seasons' ? 'bg-ink text-white' : 'bg-white text-black/55'">Saisons <span class="ml-1 opacity-60">{{ seasons.length }}</span></button><button @click="setActiveView('enrollments')" class="rounded-full px-5 py-2.5 text-sm font-bold" :class="activeView === 'enrollments' ? 'bg-ink text-white' : 'bg-white text-black/55'">Inscriptions <span class="ml-1 opacity-60">{{ enrollments.length }}</span></button><button @click="setActiveView('waitlist')" class="rounded-full px-5 py-2.5 text-sm font-bold" :class="activeView === 'waitlist' ? 'bg-ink text-white' : 'bg-white text-black/55'">Liste d'attente <span class="ml-1 opacity-60">{{ enrollments.filter(item => ['waitlist', 'invited', 'expired'].includes(item.status)).length }}</span></button><button @click="setActiveView('trials')" class="rounded-full px-5 py-2.5 text-sm font-bold" :class="activeView === 'trials' ? 'bg-ink text-white' : 'bg-white text-black/55'">Cours d’essai <span class="ml-1 opacity-60">{{ trialRequests.length }}</span></button><button @click="setActiveView('students')" class="rounded-full px-5 py-2.5 text-sm font-bold" :class="activeView === 'students' ? 'bg-ink text-white' : 'bg-white text-black/55'">Élèves <span class="ml-1 opacity-60">{{ students.length }}</span></button><button @click="setActiveView('invoices')" class="rounded-full px-5 py-2.5 text-sm font-bold" :class="activeView === 'invoices' ? 'bg-ink text-white' : 'bg-white text-black/55'">Factures <span class="ml-1 opacity-60">{{ invoices.length }}</span></button><button @click="setActiveView('discounts')" class="rounded-full px-5 py-2.5 text-sm font-bold" :class="activeView === 'discounts' ? 'bg-ink text-white' : 'bg-white text-black/55'">Rabais multi-cours <span class="ml-1 opacity-60">{{ discountRules.length }}</span></button><button @click="setActiveView('payments')" class="rounded-full px-5 py-2.5 text-sm font-bold" :class="activeView === 'payments' ? 'bg-ink text-white' : 'bg-white text-black/55'">Plans de paiement <span class="ml-1 opacity-60">{{ paymentPlans.length }}</span></button><button @click="setActiveView('settings')" class="rounded-full px-5 py-2.5 text-sm font-bold" :class="activeView === 'settings' ? 'bg-ink text-white' : 'bg-white text-black/55'">Paramétrages</button></nav>

            <button @click="setActiveView('administrators')" class="mb-5 rounded-full px-5 py-2.5 text-sm font-bold lg:fixed lg:bottom-5 lg:left-5 lg:z-30 lg:w-56 lg:text-left" :class="activeView === 'administrators' ? 'bg-ink text-white' : 'bg-white text-black/55'">Administrateurs <span class="ml-1 opacity-60">{{ administrators.length }}</span></button>

            <CoursesTable v-if="activeView === 'courses'" :courses="courses" @edit="openEdit" @delete="deleteCourse" @remove-lesson="removeLesson" />

            <EnrollmentsTable v-else-if="activeView === 'enrollments'" :enrollments="enrollments" :courses="courses" />

            <WaitlistPanel v-else-if="activeView === 'waitlist'" :enrollments="enrollments" />

            <StudentsTable v-else-if="activeView === 'students'" :students="students" />

            <TrialsTable v-else-if="activeView === 'trials'" :trials="trialRequests" />

            <DiscountRulesPanel v-else-if="activeView === 'discounts'" :rules="discountRules" />

            <InvoicesPanel v-else-if="activeView === 'invoices'" :invoices="invoices" :enrollments="enrollments" :billing-settings="billingSettings" />

            <SeasonsPanel v-else-if="activeView === 'seasons'" :seasons="seasons" />

            <PaymentPlansPanel v-else-if="activeView === 'payments'" :plans="paymentPlans" />

            

            <div v-else-if="activeView === 'settings'"><ContactButtonSettings :settings="contactButtonSettings" /><TermsSettings :content="termsAndConditions" /><RegistrationFeeSettings :settings="registrationFeeSettings" /><PaymentReminderSettings :settings="paymentReminderSettings" /><ReferencesPanel :references="references" /></div>

            <AdministratorsPanel v-else-if="activeView === 'administrators'" :administrators="administrators" :current-user="page.props.auth.user" />

        </main>

        <div v-if="showForm" class="fixed inset-0 z-50 flex justify-end bg-black/45 backdrop-blur-sm" @click.self="showForm = false">
            <aside class="h-full w-full max-w-2xl overflow-y-auto bg-[#fbfaf6] p-6 shadow-2xl sm:p-9">
                <div class="mb-8 flex items-start justify-between"><div><p class="text-xs font-bold uppercase tracking-[.2em] text-coral">{{ editingCourse ? 'Modification' : 'Nouveau' }}</p><h2 class="mt-1 font-serif text-4xl">{{ editingCourse ? 'Modifier le cours' : 'Créer un cours' }}</h2><p class="mt-2 text-sm text-black/50">{{ editingCourse ? 'Les changements seront visibles sur le catalogue.' : 'Il sera immédiatement visible sur le catalogue.' }}</p></div><button @click="showForm = false" class="grid h-10 w-10 place-items-center rounded-full bg-black/5 text-xl">×</button></div>
                <form @submit.prevent="submit" class="space-y-5">
                    <div class="grid grid-cols-2 gap-3"><label class="cursor-pointer rounded-2xl border p-4 text-center" :class="!form.is_workshop ? 'border-coral bg-red-50 text-coral' : 'border-black/10 bg-white'"><input v-model="form.is_workshop" type="radio" :value="false" class="sr-only" /><strong class="block">Cours régulier</strong><span class="mt-1 block text-xs opacity-60">Plusieurs leçons</span></label><label class="cursor-pointer rounded-2xl border p-4 text-center" :class="form.is_workshop ? 'border-coral bg-red-50 text-coral' : 'border-black/10 bg-white'"><input v-model="form.is_workshop" type="radio" :value="true" class="sr-only" /><strong class="block">Stage</strong><span class="mt-1 block text-xs opacity-60">Une date unique</span></label></div>
                    <label class="block text-sm font-semibold">Nom du cours<input v-model="form.title" class="mt-2 w-full rounded-xl border border-black/10 bg-white px-4 py-3 font-normal focus:border-coral" placeholder="Modern Jazz" /><span v-if="form.errors.title" class="mt-1 block text-xs text-coral">{{ form.errors.title }}</span></label>
                    <label class="block text-sm font-semibold">Saison<select v-model="form.season_id" @change="applySeasonDates" class="mt-2 w-full rounded-xl border border-black/10 bg-white px-4 py-3 font-normal"><option value="">Choisir une saison...</option><option v-for="season in seasons.filter(item => item.is_active || item.id === form.season_id)" :key="season.id" :value="season.id">{{ season.name }}</option></select><span v-if="form.errors.season_id" class="mt-1 block text-xs text-coral">{{ form.errors.season_id }}</span><span v-if="!seasons.length" class="mt-1 block text-xs text-amber-700">Créez d’abord une saison dans l’onglet Saisons.</span></label>
                    <div class="grid gap-4 sm:grid-cols-2"><label class="text-sm font-semibold">Discipline<select v-model="form.dance_discipline_id" class="mt-2 w-full rounded-xl border border-black/10 bg-white px-4 py-3 font-normal"><option value="">Choisir...</option><option v-for="item in references.disciplines" :key="item.id" :value="item.id">{{ item.name }}</option></select></label><label class="text-sm font-semibold">Niveau<select v-model="form.dance_level_id" class="mt-2 w-full rounded-xl border border-black/10 bg-white px-4 py-3 font-normal"><option value="">Choisir...</option><option v-for="item in references.levels" :key="item.id" :value="item.id">{{ item.name }}</option></select></label></div>
                    <label class="block text-sm font-semibold">Professeur<input v-model="form.teacher" class="mt-2 w-full rounded-xl border border-black/10 bg-white px-4 py-3 font-normal" placeholder="Prénom Nom" /></label>
                    <label class="block text-sm font-semibold">Lieu<select v-model="form.school_location_id" class="mt-2 w-full rounded-xl border border-black/10 bg-white px-4 py-3 font-normal"><option value="">Choisir...</option><option v-for="item in references.locations" :key="item.id" :value="item.id">{{ item.name }}</option></select><span v-if="form.errors.school_location_id" class="mt-1 block text-xs text-coral">{{ form.errors.school_location_id }}</span></label>
                    <label class="block text-sm font-semibold">Description<textarea v-model="form.description" rows="4" class="mt-2 w-full resize-none rounded-xl border border-black/10 bg-white px-4 py-3 font-normal" placeholder="Présentez le contenu, l’ambiance et les objectifs du cours..."></textarea><span v-if="form.errors.description" class="mt-1 block text-xs text-coral">{{ form.errors.description }}</span></label>
                    <div class="grid gap-4 sm:grid-cols-2"><label v-if="!form.is_workshop" class="text-sm font-semibold">Jour<select v-model="form.day" class="mt-2 w-full rounded-xl border border-black/10 bg-white px-4 py-3 font-normal"><option v-for="day in days" :key="day">{{ day }}</option></select></label><label class="text-sm font-semibold">Horaire<input v-model="form.time" class="mt-2 w-full rounded-xl border border-black/10 bg-white px-4 py-3 font-normal" placeholder="18:30 – 20:00" /></label></div>
                    <div v-if="form.is_workshop"><label class="text-sm font-semibold">Date du stage<input v-model="form.start_date" type="date" @change="form.end_date = form.start_date" class="mt-2 w-full rounded-xl border border-black/10 bg-white px-4 py-3 font-normal" /></label><p class="mt-2 text-xs text-black/45">Le stage apparaîtra dans la section dédiée à la fin du calendrier.</p></div>
                    <div v-else class="grid gap-4 sm:grid-cols-2"><label class="text-sm font-semibold">Début de la session<input v-model="form.start_date" type="date" class="mt-2 w-full rounded-xl border border-black/10 bg-white px-4 py-3 font-normal" /></label><label class="text-sm font-semibold">Fin de la session<input v-model="form.end_date" type="date" :min="form.start_date" class="mt-2 w-full rounded-xl border border-black/10 bg-white px-4 py-3 font-normal" /></label></div>
                    <div class="grid gap-4" :class="form.is_workshop ? 'sm:grid-cols-2' : 'sm:grid-cols-3'"><label class="text-sm font-semibold">Capacité<input v-model="form.capacity" type="number" min="1" class="mt-2 w-full rounded-xl border border-black/10 bg-white px-4 py-3 font-normal" /></label><label v-if="!form.is_workshop" class="text-sm font-semibold">Prix unitaire (CHF)<input v-model="form.price" type="number" min="0" step="0.01" class="mt-2 w-full rounded-xl border border-black/10 bg-white px-4 py-3 font-normal" /></label><label class="text-sm font-semibold">{{ form.is_workshop ? 'Prix du stage (CHF)' : 'Prix session (CHF)' }}<input v-model="form.session_price" type="number" min="0" step="0.01" class="mt-2 w-full rounded-xl border border-black/10 bg-white px-4 py-3 font-normal" /></label></div>
                    <section v-if="references.categories.length && !form.is_workshop" class="rounded-2xl border border-black/10 bg-white p-4"><h3 class="font-serif text-xl">Prix par catégorie tarifaire</h3><p class="mt-1 text-xs text-black/45">Laissez vide pour ne pas proposer la catégorie sur ce cours.</p><div class="mt-4 grid gap-4 sm:grid-cols-2"><label v-for="category in references.categories" :key="category.id" class="text-sm font-semibold">{{ category.name }} (CHF)<input v-model="form.category_prices[category.id]" type="number" min="0" max="99999" step="0.01" class="mt-2 w-full rounded-xl border border-black/10 px-4 py-3 font-normal" placeholder="Non proposé" /><span v-if="form.errors[`category_prices.${category.id}`]" class="mt-1 block text-xs text-coral">{{ form.errors[`category_prices.${category.id}`] }}</span></label></div></section>
                    <div v-if="!form.is_workshop" class="space-y-4 rounded-2xl border border-blue-100 bg-blue-50 p-4">
                        <label class="flex cursor-pointer items-center justify-between gap-4"><div><strong class="text-sm">Activer les cours d’essai</strong><p class="mt-1 text-xs text-blue-800/60">Affiche le bouton d’essai sur la page publique du cours.</p></div><input v-model="form.trial_enabled" type="checkbox" class="h-5 w-5 accent-coral" /></label>
                        <template v-if="form.trial_enabled">
                            <label class="flex cursor-pointer items-center justify-between gap-4 border-t border-blue-100 pt-4"><div><strong class="text-sm">Cours d’essai gratuit</strong><p class="mt-1 text-xs text-blue-800/60">Désactivez cette option pour définir un prix d’essai.</p></div><input v-model="form.trial_is_free" type="checkbox" class="h-5 w-5 accent-coral" /></label>
                            <div v-if="!form.trial_is_free" class="space-y-4 border-t border-blue-100 pt-4">
                                <label class="block text-sm font-semibold">Prix du cours d’essai (CHF)<input v-model="form.trial_price" type="number" min="0.01" max="9999" step="0.01" class="mt-2 w-full rounded-xl border border-blue-100 bg-white px-4 py-3 font-normal" /><span v-if="form.errors.trial_price" class="mt-1 block text-xs text-red-600">{{ form.errors.trial_price }}</span></label>
                                <label class="flex cursor-pointer items-center justify-between gap-4 rounded-xl bg-white p-4"><div><strong class="text-sm">Paiement sur place</strong><p class="mt-1 text-xs text-black/45">Le participant réglera le cours d’essai directement à l’école.</p></div><input v-model="form.trial_payment_on_site" type="checkbox" class="h-5 w-5 accent-coral" /></label>
                            </div>
                        </template>
                        <p v-else class="rounded-xl bg-white/70 p-3 text-xs text-blue-800/60">Aucun bouton ni formulaire de cours d’essai ne sera proposé aux visiteurs.</p>
                    </div>
                    <p class="rounded-xl bg-[#f3ece4] p-3 text-xs leading-relaxed text-black/55">{{ form.is_workshop ? 'Une seule leçon sera créée à la date du stage. Aucun rabais ni frais d’inscription ne sera appliqué.' : (editingCourse ? 'Si le jour ou les dates changent, le calendrier sera régénéré.' : `Une leçon sera générée chaque ${form.day.toLowerCase()} entre les deux dates.`) }}</p>
                    <div class="rounded-2xl border border-black/10 bg-white p-4"><p class="text-sm font-semibold">Image du cours</p><img v-if="imagePreview || form.image" :src="imagePreview || form.image" alt="Aperçu du cours" class="mt-3 h-40 w-full rounded-xl object-cover" /><div class="mt-3 grid gap-3 sm:grid-cols-2"><label class="cursor-pointer rounded-xl border border-dashed border-black/20 px-4 py-3 text-center text-sm font-bold hover:border-coral hover:text-coral">Téléverser une image<input type="file" accept="image/jpeg,image/png,image/webp" class="sr-only" @change="selectCourseImage" /></label><input v-model="form.image" type="text" class="rounded-xl border border-black/10 bg-white px-4 py-3 text-sm font-normal" placeholder="Ou coller une URL https://..." @input="!form.image_upload && (imagePreview = form.image)" /></div><p class="mt-2 text-xs text-black/40">JPG, PNG ou WebP · maximum 5 Mo.</p><span v-if="form.errors.image || form.errors.image_upload" class="mt-2 block text-xs text-coral">{{ form.errors.image || form.errors.image_upload }}</span></div>
                    <label class="flex items-center justify-between rounded-xl border border-black/10 bg-white p-4 text-sm font-semibold">Couleur du cours<input v-model="form.accent" type="color" class="h-9 w-14 cursor-pointer rounded border-0 bg-transparent" /></label>
                    <label class="flex cursor-pointer items-center justify-between rounded-xl border border-black/10 bg-white p-4"><div><strong class="text-sm">Publié</strong><p class="mt-1 text-xs text-black/45">Le cours est visible et ouvert aux inscriptions sur votre page.</p></div><input v-model="form.is_active" type="checkbox" class="h-5 w-5 accent-coral" /></label>
                    <label class="flex cursor-pointer items-center justify-between rounded-xl border border-black/10 bg-white p-4"><div><strong class="text-sm">Cours de couple</strong><p class="mt-1 text-xs text-black/45">Active l’équilibrage automatique entre Leads et Follows.</p></div><input v-model="form.couple_mode" type="checkbox" class="h-5 w-5 accent-coral" /></label>
                    <label v-if="form.couple_mode" class="block rounded-xl border border-purple-100 bg-purple-50 p-4 text-sm font-semibold">Écart maximum accepté<input v-model="form.max_role_gap" type="number" min="0" max="100" class="mt-2 w-full rounded-xl border border-purple-100 bg-white px-4 py-3 font-normal" /><span class="mt-2 block text-xs font-normal text-purple-700">Exemple : avec un écart de 2, 2 Leads et 4 Follows sont acceptés. Le prochain Follow passe en liste d’attente.</span><span v-if="form.errors.max_role_gap" class="mt-1 block text-xs text-red-600">{{ form.errors.max_role_gap }}</span></label>
                    <label v-if="form.couple_mode" class="block rounded-xl border border-purple-100 bg-purple-50 p-4 text-sm font-semibold">Appliquer la règle après combien d’inscrits ?<input v-model="form.balance_after_count" type="number" min="0" :max="form.capacity" class="mt-2 w-full rounded-xl border border-purple-100 bg-white px-4 py-3 font-normal" /><span class="mt-2 block text-xs font-normal text-purple-700">Avec 6, les six premières inscriptions sont acceptées librement. L’équilibrage commence à la 7e. Utilisez 0 pour l’appliquer dès la première inscription.</span><span v-if="form.errors.balance_after_count" class="mt-1 block text-xs text-red-600">{{ form.errors.balance_after_count }}</span></label>
                    <label v-if="form.couple_mode" class="block rounded-xl border border-purple-100 bg-purple-50 p-4 text-sm font-semibold">Validité du bouton d’invitation (heures)<input v-model="form.waitlist_invitation_hours" type="number" min="0.01" max="720" step="0.01" class="mt-2 w-full rounded-xl border border-purple-100 bg-white px-4 py-3 font-normal" /><span class="mt-2 block text-xs font-normal text-purple-700">Après ce délai, l’invitation expire et le système contacte automatiquement la prochaine personne éligible.</span><span v-if="form.errors.waitlist_invitation_hours" class="mt-1 block text-xs text-red-600">{{ form.errors.waitlist_invitation_hours }}</span></label>
                    <section v-if="editingCourse" class="overflow-hidden rounded-2xl border border-black/10 bg-white">
                        <div class="border-b border-black/5 px-5 py-4">
                            <h3 class="font-serif text-2xl">Calendrier des leçons</h3>
                            <p class="mt-1 text-xs text-black/45">{{ editingLessons.length }} leçon{{ editingLessons.length > 1 ? 's' : '' }} programmée{{ editingLessons.length > 1 ? 's' : '' }}. Supprimez ici les vacances et les dates sans cours.</p>
                        </div>
                        <div v-if="editingLessons.length" class="grid gap-2 p-4 sm:grid-cols-2">
                            <div v-for="(lesson, index) in editingLessons" :key="lesson.id" class="flex items-center justify-between gap-3 rounded-xl bg-[#f7f5f0] px-4 py-3">
                                <div><span class="text-[10px] font-bold uppercase tracking-wider text-black/35">Leçon {{ index + 1 }}</span><strong class="mt-1 block text-sm">{{ new Date(`${lesson.lesson_date}T12:00:00`).toLocaleDateString('fr-CH', { weekday: 'long', day: '2-digit', month: 'long', year: 'numeric' }) }}</strong></div>
                                <button type="button" @click="removeLesson(editingCourse, lesson)" class="grid h-9 w-9 shrink-0 place-items-center rounded-full bg-red-50 text-lg font-bold text-red-500 transition hover:bg-red-500 hover:text-white" :title="`Supprimer la leçon du ${lesson.lesson_date}`">×</button>
                            </div>
                        </div>
                        <div v-else class="px-5 py-10 text-center text-sm text-black/40">Aucune leçon programmée pour ce cours.</div>
                    </section>
                    <div v-if="Object.keys(form.errors).length" class="rounded-xl border border-red-100 bg-red-50 p-4 text-sm text-red-700" role="alert">
                        <strong>Le cours n’a pas pu être enregistré :</strong>
                        <ul class="mt-2 list-disc space-y-1 pl-5">
                            <li v-for="(message, field) in form.errors" :key="field">{{ message }}</li>
                        </ul>
                    </div>
                    <div class="flex gap-3 pt-3"><button type="button" @click="showForm = false" class="flex-1 rounded-full border border-black/10 py-3.5 font-bold">Annuler</button><button :disabled="form.processing" class="flex-1 rounded-full bg-ink py-3.5 font-bold text-white hover:bg-coral disabled:opacity-50">{{ form.processing ? 'Enregistrement…' : editingCourse ? 'Enregistrer' : 'Créer et publier' }}</button></div>
                </form>
            </aside>
        </div>
    </div>
</template>
