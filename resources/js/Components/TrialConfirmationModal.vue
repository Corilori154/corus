<script setup>
defineProps({ message: { type: String, required: true } });
defineEmits(['close']);
</script>

<template>
    <div class="fixed inset-0 z-[100] grid place-items-center bg-black/65 p-4 backdrop-blur-sm" role="dialog" aria-modal="true" aria-labelledby="trial-confirmation-title">
        <div class="w-full max-w-2xl rounded-[2rem] bg-white p-8 text-center shadow-2xl sm:p-12">
            <div class="mx-auto grid h-20 w-20 place-items-center rounded-full bg-green-100 text-4xl font-bold text-green-700" aria-hidden="true">✓</div>
            <p class="mt-7 text-xs font-bold uppercase tracking-[.2em] text-green-700">Inscription confirmée</p>
            <h2 id="trial-confirmation-title" class="mt-3 font-serif text-4xl leading-tight sm:text-5xl">Votre cours d’essai est confirmé</h2>
            <p class="mx-auto mt-6 max-w-xl text-lg leading-relaxed text-black/65 sm:text-xl">{{ message }}</p>
            <button type="button" autofocus class="mt-8 w-full rounded-full bg-ink px-8 py-4 text-lg font-bold text-white transition hover:bg-coral sm:w-auto" @click="$emit('close')">
                J’ai compris
            </button>
        </div>
    </div>
</template>
