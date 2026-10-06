<template>
    <span class="inline-flex items-center gap-1.5 text-xs font-medium px-2.5 py-1 rounded-full" style="color: var(--text-secondary); background: var(--surface-2); border: 1px solid var(--border-subtle)">
        <component :is="cfg.icon" class="w-3.5 h-3.5" />
        {{ cfg.label }}
    </span>
</template>

<script setup>
import { computed } from 'vue'
import { Square, Clapperboard, GalleryHorizontal, RectangleVertical, Film } from 'lucide-vue-next'

const props = defineProps({ type: String })

const map = {
    post: { label: 'Post', icon: Square },
    reel: { label: 'Reel', icon: Clapperboard },
    carousel: { label: 'Carrossel', icon: GalleryHorizontal },
    story: { label: 'Story', icon: RectangleVertical },
    tiktok_video: { label: 'Vídeo', icon: Film },
}

const cfg = computed(() => map[props.type] ?? { label: props.type, icon: Square })
</script>
