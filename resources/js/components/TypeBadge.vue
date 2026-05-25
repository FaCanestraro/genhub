<template>
    <span class="text-xs text-gray-400 bg-gray-800 px-2 py-0.5 rounded-full">{{ label }}</span>
</template>

<script setup>
import { computed } from 'vue'
const props = defineProps({ type: String })
const map = { post: 'Post', reel: 'Reel', carousel: 'Carrossel', story: 'Story', tiktok_video: 'Vídeo' }
const label = computed(() => map[props.type] || props.type)
</script>
