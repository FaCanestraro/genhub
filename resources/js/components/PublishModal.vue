<template>
    <Teleport to="body">
        <div class="fixed inset-0 z-[60] bg-black/70 backdrop-blur-sm flex items-center justify-center p-4" @click.self="$emit('close')">
            <div class="w-full max-w-lg rounded-2xl p-6 bg-gray-900 border border-gray-800 max-h-[90vh] overflow-y-auto" @click.stop>
                <div class="flex items-center justify-between mb-5">
                    <h2 class="text-lg font-semibold text-white">Publicar nas redes</h2>
                    <button @click="$emit('close')" class="p-1.5 text-gray-400 hover:text-white rounded-lg hover:bg-gray-800"><X class="w-4 h-4" /></button>
                </div>

                <div v-if="loading" class="py-10 flex justify-center"><Loader2 class="w-5 h-5 animate-spin text-gray-500" /></div>

                <div v-else-if="!accounts.length" class="py-8 text-center">
                    <p class="text-gray-300 text-sm mb-3">Nenhuma conta conectada.</p>
                    <RouterLink to="/settings?section=sociais" class="text-sm text-violet-400 hover:text-violet-300">Conectar em Configurações → Redes Sociais</RouterLink>
                </div>

                <!-- Resultado -->
                <div v-else-if="publications.length" class="space-y-2">
                    <div v-for="p in publications" :key="p.id" class="flex items-center justify-between gap-3 py-2.5 px-3 rounded-lg bg-gray-800/50">
                        <div class="min-w-0">
                            <p class="text-sm text-white truncate">{{ p.social_account.name }}</p>
                            <p v-if="p.status === 'failed'" class="text-xs text-red-400">{{ p.error_message }}</p>
                        </div>
                        <a v-if="p.status === 'published' && p.permalink" :href="p.permalink" target="_blank" class="text-xs text-green-400 flex items-center gap-1 flex-shrink-0">
                            Publicado <ExternalLink class="w-3 h-3" />
                        </a>
                        <span v-else-if="p.status === 'published'" class="text-xs text-green-400 flex-shrink-0">Publicado</span>
                        <span v-else-if="p.status === 'failed'" class="text-xs text-red-400 flex-shrink-0">Falhou</span>
                        <span v-else class="text-xs text-gray-400 flex items-center gap-1 flex-shrink-0"><Loader2 class="w-3 h-3 animate-spin" /> Publicando...</span>
                    </div>
                    <p class="text-xs text-gray-500 pt-2">Pode fechar esta janela; a publicação continua em segundo plano.</p>
                </div>

                <form v-else @submit.prevent="submit" class="space-y-4">
                    <div>
                        <p class="label">Contas</p>
                        <label v-for="account in accounts" :key="account.id" class="flex items-center gap-3 py-2 px-3 rounded-lg hover:bg-gray-800/50 cursor-pointer">
                            <input type="checkbox" :value="account.id" v-model="selected" class="accent-violet-500" />
                            <span class="text-sm text-white flex-1 truncate">{{ account.name }}</span>
                            <PlatformIcon :platform="account.provider" />
                        </label>
                    </div>

                    <label v-if="hasInstagram" class="flex items-center gap-2 text-sm text-gray-300 cursor-pointer">
                        <input type="checkbox" v-model="asStory" class="accent-violet-500" />
                        Publicar no Instagram como Story
                    </label>

                    <div>
                        <label class="label">Legenda</label>
                        <textarea v-model="caption" rows="6" maxlength="2200" class="input resize-none" placeholder="Escreva a legenda do post..." />
                        <p class="text-[11px] text-gray-600 mt-1 text-right">{{ caption.length }}/2200<span v-if="asStory && hasInstagram"> · Stories não exibem legenda</span></p>
                    </div>

                    <p class="text-xs text-gray-500">{{ mediaCount }} mídia(s) desta geração serão publicadas{{ mediaCount > 1 ? ' como carrossel' : '' }}.</p>

                    <button type="submit" :disabled="!selected.length || submitting" class="btn-primary w-full py-2.5 flex items-center justify-center gap-2">
                        <Loader2 v-if="submitting" class="w-4 h-4 animate-spin" />
                        <Send v-else class="w-4 h-4" />
                        Publicar
                    </button>
                </form>
            </div>
        </div>
    </Teleport>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { RouterLink } from 'vue-router'
import { X, Loader2, Send, ExternalLink } from 'lucide-vue-next'
import api from '@/services/api'
import PlatformIcon from '@/components/PlatformIcon.vue'
import { useToastStore } from '@/stores/toast'

const props = defineProps({ generation: { type: Object, required: true } })
defineEmits(['close'])
const toast = useToastStore()

const accounts     = ref([])
const selected     = ref([])
const loading      = ref(true)
const submitting   = ref(false)
const publications = ref([])
const asStory      = ref(props.generation.action?.type === 'story')
const caption      = ref(initialCaption())

const hasInstagram = computed(() => accounts.value.some(a => a.provider === 'instagram' && selected.value.includes(a.id)))
const mediaCount   = computed(() => (props.generation.assets || []).filter(a => ['image', 'video'].includes(a.type)).length)

function initialCaption() {
    const { action, result_text } = props.generation
    let text = action?.caption
    let tags = action?.hashtags || []
    if (!text && result_text) {
        try {
            const j = JSON.parse(result_text.replace(/```json|```/g, '').trim())
            text = j.caption
            tags = j.hashtags || []
        } catch {
            text = result_text
        }
    }
    const hashtags = tags.map(t => (t.startsWith('#') ? t : `#${t}`)).join(' ')
    return [text, hashtags].filter(Boolean).join('\n\n')
}

let timer = null
function poll() {
    timer = setTimeout(async () => {
        const pending = publications.value.filter(p => ['pending', 'processing'].includes(p.status))
        for (const p of pending) {
            Object.assign(p, (await api.get(`/publications/${p.id}`)).data)
        }
        if (publications.value.some(p => ['pending', 'processing'].includes(p.status))) poll()
    }, 4000)
}

async function submit() {
    submitting.value = true
    try {
        const { data } = await api.post('/publications', {
            generation_id: props.generation.id,
            social_account_ids: selected.value,
            caption: caption.value,
            as_story: asStory.value,
        })
        publications.value = data
        poll()
    } catch (e) {
        if (e.response?.status === 422) toast.push(e.response.data.message, 'error')
    } finally {
        submitting.value = false
    }
}

onMounted(async () => {
    try {
        accounts.value = (await api.get('/social-accounts')).data
    } finally {
        loading.value = false
    }
})
onUnmounted(() => clearTimeout(timer))
</script>

<style scoped>
@reference "tailwindcss";
.label       { @apply block text-xs text-gray-400 mb-1 font-medium; }
.input       { @apply w-full bg-gray-800/50 border border-gray-700 rounded-lg px-3 py-2 text-sm text-white placeholder-gray-500 focus:outline-none focus:border-violet-500 transition-colors; }
.btn-primary { @apply disabled:opacity-50 text-white font-medium rounded-lg transition-colors text-sm; background: var(--brand, #7c3aed); }
</style>
