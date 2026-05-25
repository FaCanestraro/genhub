<template>
    <span :class="classes" class="text-xs font-medium px-2 py-0.5 rounded-full flex items-center gap-1">
        <span>{{ emoji }}</span>
        {{ label }}
    </span>
</template>

<script setup>
import { computed } from 'vue'

const props = defineProps({ platform: String })

const map = {
    instagram: { label: 'Instagram', emoji: '📸', cls: 'bg-pink-500/20 text-pink-400' },
    tiktok: { label: 'TikTok', emoji: '🎵', cls: 'bg-cyan-500/20 text-cyan-400' },
    facebook: { label: 'Facebook', emoji: '👤', cls: 'bg-blue-500/20 text-blue-400' },
    youtube: { label: 'YouTube', emoji: '▶️', cls: 'bg-red-500/20 text-red-400' },
}

const label = computed(() => map[props.platform]?.label || props.platform)
const emoji = computed(() => map[props.platform]?.emoji || '📱')
const classes = computed(() => map[props.platform]?.cls || 'bg-gray-700 text-gray-300')
</script>
