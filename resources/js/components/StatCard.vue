<template>
    <component :is="to ? RouterLink : 'div'" :to="to" class="stat-card group p-5 flex flex-col gap-4" :class="{ 'cursor-pointer': to }">
        <div class="flex items-center justify-between gap-3">
            <p class="tech-label !text-[var(--text-secondary)]">{{ label }}</p>
            <span class="panel-icon">
                <component :is="icon" class="w-4 h-4" />
            </span>
        </div>

        <div v-if="loading" class="shimmer h-8 w-24 rounded-md"></div>
        <p v-else class="page-hero-title text-[1.9rem] leading-none">{{ value }}</p>

        <p v-if="hint && !loading" class="text-xs flex items-center gap-1.5" :class="warning ? 'text-amber-300' : ''" :style="warning ? null : 'color: var(--text-muted)'">
            <span v-if="warning" class="w-1.5 h-1.5 rounded-full bg-amber-300"></span>
            {{ hint }}
        </p>
    </component>
</template>

<script setup>
import { RouterLink } from 'vue-router'

defineProps({
    label: String,
    value: [Number, String],
    icon: [Object, Function],
    to: String,
    hint: String,
    warning: Boolean,
    loading: Boolean,
})
</script>
