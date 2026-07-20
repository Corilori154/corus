<script setup>
import { Head } from '@inertiajs/vue3';

defineProps({ school: Object, course: Object, enrollment: Object, invoice: Object });
const money = value => Number(value || 0).toLocaleString('fr-CH', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
const date = value => value ? new Date(`${String(value).slice(0, 10)}T12:00:00`).toLocaleDateString('fr-CH') : '—';
</script>

<template>
    <Head title="Inscription confirmée" />
    <main class="grid min-h-screen place-items-center bg-[#f5f3ee] px-5 py-12">
        <section class="w-full max-w-2xl overflow-hidden rounded-[2rem] bg-white shadow-xl shadow-black/5">
            <div class="h-2" :style="{ backgroundColor: school.accent || '#ef6f7f' }"></div>
            <div class="p-7 text-center sm:p-12">
                <span class="mx-auto grid h-20 w-20 place-items-center rounded-full bg-green-50 text-4xl text-green-700">✓</span>
                <p class="mt-7 text-xs font-bold uppercase tracking-[.2em] text-green-700">Confirmation réussie</p>
                <h1 class="mt-3 font-serif text-4xl leading-tight sm:text-5xl">Merci {{ enrollment.first_name }}, votre inscription est confirmée.</h1>
                <p class="mx-auto mt-5 max-w-lg text-lg leading-relaxed text-black/55">Votre place est désormais réservée pour le cours <strong class="text-black">{{ course.title }}</strong>.</p>

                <div class="mt-8 rounded-3xl bg-[#f7f5f0] p-6 text-left">
                    <div class="grid gap-5 sm:grid-cols-2">
                        <div><p class="text-xs font-bold uppercase tracking-wide text-black/35">Cours</p><strong class="mt-1 block">{{ course.title }}</strong><p class="mt-1 text-sm text-black/45">{{ course.day }} {{ course.time }}<span v-if="course.location"> · {{ course.location }}</span></p></div>
                        <div><p class="text-xs font-bold uppercase tracking-wide text-black/35">Début choisi</p><strong class="mt-1 block">{{ date(enrollment.start_date) }}</strong><p v-if="enrollment.dance_role" class="mt-1 text-sm capitalize text-black/45">Rôle : {{ enrollment.dance_role }}</p></div>
                    </div>
                </div>

                <div class="mt-5 rounded-2xl border border-green-100 bg-green-50 p-5 text-left text-sm leading-relaxed text-green-900">
                    <strong>Facture {{ invoice.number }}</strong><br>
                    La facture de {{ money(invoice.amount) }} CHF a été créée et envoyée à {{ enrollment.email }}.
                </div>

                <p class="mt-7 text-sm text-black/45">Vous n’avez aucune autre démarche à effectuer.</p>
                <a :href="`/ecole/${school.slug}`" class="mt-6 inline-flex rounded-full bg-ink px-6 py-3.5 text-sm font-bold text-white hover:bg-coral">Retour au site de {{ school.name }}</a>
            </div>
        </section>
    </main>
</template>
