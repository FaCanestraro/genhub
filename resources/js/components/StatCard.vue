<template>
    <div class="stat-card p-6">
        <!-- Icon + label row -->
        <div class="flex items-center justify-between mb-5">
            <p class="text-xs font-medium tracking-wide uppercase" style="color: var(--text-muted)">{{ label }}</p>
            <div
                class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0"
                :style="{
                    background: `color-mix(in srgb, ${colorVar} 16%, transparent)`,
                    border: `1px solid color-mix(in srgb, ${colorVar} 28%, transparent)`,
                    boxShadow: `0 0 14px color-mix(in srgb, ${colorVar} 18%, transparent)`
                }"
            >
                <component :is="iconComponent" class="w-4 h-4" :style="{ color: colorVar }" />
            </div>
        </div>

        <!-- Value -->
        <p class="text-3xl font-bold tracking-tight text-white leading-none">{{ value ?? '—' }}</p>
    </div>
</template>

<script setup>
import { computed } from 'vue'
import { Megaphone, Layers, Sparkles, Package } from 'lucide-vue-next'

const props = defineProps({
    label: String,
    value: [Number, String],
    icon:  String,
    color: String,
})

const colorMap = {
    violet: '#a78bfa',
    blue:   '#60a5fa',
    green:  '#4ade80',
    pink:   '#f472b6',
    orange: '#fb923c',
    cyan:   '#22d3ee',
}

const icons = { Megaphone, Layers, Sparkles, Package }
const iconComponent = computed(() => icons[props.icon] || Sparkles)
const colorVar = computed(() => colorMap[props.color] || colorMap.violet)
</script>
