<template>
    <span class="inline-flex items-center gap-1.5 text-xs font-medium px-2.5 py-1 rounded-full" :style="{ color: cfg.color, background: `${cfg.color}1f`, border: `1px solid ${cfg.color}33` }">
        <component :is="cfg.icon" class="w-3.5 h-3.5" />
        {{ cfg.label }}
    </span>
</template>

<script setup>
import { computed } from 'vue'
import { Instagram, Facebook, Youtube, Music2, Smartphone } from 'lucide-vue-next'

const props = defineProps({ platform: String })

const map = {
    instagram: { label: 'Instagram', icon: Instagram, color: '#f472b6' },
    tiktok: { label: 'TikTok', icon: Music2, color: '#22d3ee' },
    facebook: { label: 'Facebook', icon: Facebook, color: '#60a5fa' },
    youtube: { label: 'YouTube', icon: Youtube, color: '#f87171' },
}

const cfg = computed(() => map[props.platform] ?? { label: props.platform, icon: Smartphone, color: '#9ca3af' })
</script>
