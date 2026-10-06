<template>
    <!-- Estado vazio: diz o que falta e, quando possível, o próximo passo. -->
    <div class="flex flex-col items-center text-center gap-3 py-10 px-4">
        <span class="w-12 h-12 rounded-2xl flex items-center justify-center" style="background: var(--surface-2); border: 1px solid var(--border-subtle)">
            <component :is="icon" class="w-5 h-5" style="color: var(--text-secondary)" />
        </span>
        <div class="flex flex-col gap-1">
            <p class="text-sm font-medium text-white">{{ title }}</p>
            <p v-if="text" class="text-sm max-w-xs" style="color: var(--text-muted)">{{ text }}</p>
        </div>
        <RouterLink v-if="ctaTo" :to="ctaTo" class="btn-primary inline-flex items-center gap-2 mt-1 px-4 py-2 rounded-lg text-sm font-medium">
            <Plus class="w-4 h-4" /> {{ ctaLabel }}
        </RouterLink>
    </div>
</template>

<script setup>
import { RouterLink } from 'vue-router'
import { Plus } from 'lucide-vue-next'

defineProps({
    icon: { type: [Object, Function], required: true },
    title: { type: String, required: true },
    text: String,
    ctaTo: String,
    ctaLabel: String,
})
</script>
