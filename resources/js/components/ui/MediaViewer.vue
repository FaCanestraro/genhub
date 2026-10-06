<template>
    <!-- Visualizador de mídia em tela cheia: setas/teclado navegam, Esc fecha. -->
    <Teleport to="body">
        <div class="fixed inset-0 z-[70] bg-black/90 backdrop-blur-xl flex items-center justify-center p-4" role="dialog" aria-modal="true" aria-label="Visualizar criativo" @click.self="$emit('close')">
            <button type="button" @click="$emit('close')" class="viewer-btn absolute top-4 right-4" aria-label="Fechar"><X class="w-5 h-5" /></button>
            <template v-if="assets.length > 1">
                <button type="button" @click="go(-1)" class="viewer-btn absolute left-2 sm:left-4" aria-label="Anterior"><ChevronLeft class="w-6 h-6" /></button>
                <button type="button" @click="go(1)" class="viewer-btn absolute right-2 sm:right-4" aria-label="Próximo"><ChevronRight class="w-6 h-6" /></button>
            </template>

            <div class="max-w-5xl w-full flex flex-col items-center gap-4" @click.self="$emit('close')">
                <video v-if="current.type === 'video'" :key="current.id" :src="current.url" controls autoplay playsinline class="max-w-full max-h-[80vh] rounded-xl bg-black"></video>
                <img v-else :key="current.id" :src="current.url" alt="Criativo" class="max-w-full max-h-[80vh] rounded-xl object-contain shadow-2xl" />

                <div class="flex items-center gap-3">
                    <span v-if="assets.length > 1" class="text-xs text-white/60 tabular-nums">{{ index + 1 }} / {{ assets.length }}</span>
                    <button type="button" @click="downloadAsset(current.url)" class="inline-flex items-center gap-1.5 text-sm text-white px-4 py-2 rounded-xl bg-white/10 hover:bg-white/20 transition-colors">
                        <Download class="w-4 h-4" /> Baixar
                    </button>
                </div>
            </div>
        </div>
    </Teleport>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { X, ChevronLeft, ChevronRight, Download } from 'lucide-vue-next'
import { downloadAsset } from '@/utils/download'

const props = defineProps({
    assets: { type: Array, required: true }, // [{ id, url, type }]
    start: { type: Number, default: 0 },
})
const emit = defineEmits(['close'])

const index = ref(props.start)
const current = computed(() => props.assets[index.value])
const go = (step) => { index.value = (index.value + step + props.assets.length) % props.assets.length }

function onKey(e) {
    if (e.key === 'Escape') emit('close')
    else if (e.key === 'ArrowLeft') go(-1)
    else if (e.key === 'ArrowRight') go(1)
}
onMounted(() => {
    window.addEventListener('keydown', onKey)
    document.body.style.overflow = 'hidden'
})
onUnmounted(() => {
    window.removeEventListener('keydown', onKey)
    document.body.style.overflow = ''
})
</script>

<style scoped>
.viewer-btn {
    width: 44px;
    height: 44px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 12px;
    color: rgba(255, 255, 255, 0.75);
    transition: color 0.15s, background-color 0.15s;
}
.viewer-btn:hover { color: #fff; background: rgba(255, 255, 255, 0.1); }
</style>
