<template>
    <div class="p-8 max-w-5xl mx-auto">
        <!-- Header -->
        <div v-if="action" class="mb-8">
            <div class="flex items-center gap-2 text-sm text-gray-400 mb-3">
                <RouterLink to="/campaigns" class="hover:text-white">Campanhas</RouterLink>
                <span>/</span>
                <RouterLink :to="`/campaigns/${action.campaign_id}`" class="hover:text-white">
                    {{ action.campaign?.name }}
                </RouterLink>
                <span>/</span>
                <span class="text-white">{{ action.title }}</span>
            </div>

            <div class="flex items-start justify-between">
                <div class="flex items-center gap-3 flex-wrap">
                    <PlatformIcon :platform="action.platform" />
                    <TypeBadge :type="action.type" />
                    <h1 class="text-xl font-bold text-white">{{ action.title }}</h1>
                    <StatusBadge :status="action.status" />
                </div>
            </div>

            <!-- Brief (editable) -->
            <div class="mt-3 rounded-xl text-sm" style="background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.10);box-shadow:inset 0 1px 0 rgba(255,255,255,0.07),inset 0 -1px 0 rgba(0,0,0,0.12)">
                <div class="flex items-center justify-between px-4 pt-3 pb-1">
                    <span class="text-xs text-gray-500 font-medium uppercase tracking-wide">Brief</span>
                    <div v-if="!editingBrief" class="flex gap-1">
                        <button @click="startEditBrief" class="p-1 text-gray-600 hover:text-white hover:bg-gray-700 rounded-lg transition-colors" title="Editar brief">
                            <Pencil class="w-3.5 h-3.5" />
                        </button>
                    </div>
                    <div v-else class="flex gap-1">
                        <button @click="saveBrief" :disabled="savingBrief" class="px-2 py-0.5 text-xs bg-violet-600 hover:bg-violet-700 disabled:opacity-50 text-white rounded-lg transition-colors">
                            {{ savingBrief ? '...' : 'Salvar' }}
                        </button>
                        <button @click="cancelEditBrief" class="px-2 py-0.5 text-xs text-gray-400 hover:text-white hover:bg-gray-700 rounded-lg transition-colors">
                            Cancelar
                        </button>
                    </div>
                </div>
                <textarea
                    v-if="editingBrief"
                    v-model="briefDraft"
                    rows="4"
                    autofocus
                    class="w-full bg-transparent px-4 pb-3 text-gray-300 placeholder-gray-600 focus:outline-none resize-none"
                    placeholder="Descreva o que a IA deve gerar..."
                ></textarea>
                <p v-else class="text-gray-400 px-4 pb-3 leading-relaxed whitespace-pre-wrap min-h-[2rem]">
                    {{ action.brief || 'Nenhum brief definido.' }}
                </p>
            </div>

            <!-- Produtos relacionados -->
            <div v-if="action.products?.length" class="mt-3">
                <p class="text-xs text-gray-500 font-medium uppercase tracking-wide mb-2">Produtos</p>
                <div class="flex flex-wrap gap-2">
                    <div
                        v-for="p in action.products"
                        :key="p.id"
                        class="flex items-center gap-2 rounded-xl px-3 py-2 transition-colors"
                        style="background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.09)"
                    >
                        <img
                            v-if="p.images?.length"
                            :src="`/storage/${p.images[0]}`"
                            :alt="p.name"
                            class="w-8 h-8 rounded-lg object-cover flex-shrink-0"
                        />
                        <div v-else class="w-8 h-8 rounded-lg flex items-center justify-center flex-shrink-0" style="background:rgba(255,255,255,0.06)">
                            <Package class="w-4 h-4 text-gray-600" />
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-medium text-white leading-tight truncate max-w-[140px]">{{ p.name }}</p>
                            <p v-if="p.price" class="text-xs text-violet-400 leading-tight">R$ {{ Number(p.price).toFixed(2) }}</p>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
            <!-- Generator panel -->
            <div class="lg:col-span-2">
                <div class="glass-panel rounded-2xl p-5 sticky top-8">
                    <h2 class="font-semibold text-white mb-4 flex items-center gap-2">
                        <Sparkles class="w-4 h-4 text-violet-400" />
                        Gerar com IA
                    </h2>

                    <form @submit.prevent="generate" class="space-y-4">
                        <!-- Tipo de geração -->
                        <div>
                            <label class="block text-xs text-gray-400 mb-2 uppercase tracking-wide">Tipo</label>
                            <div class="grid grid-cols-2 gap-2">
                                <button
                                    v-for="t in genTypes"
                                    :key="t.value"
                                    type="button"
                                    @click="genForm.type = t.value"
                                    :class="genForm.type === t.value
                                        ? 'bg-violet-600 text-white border-violet-500'
                                        : 'text-gray-400 hover:text-white'"
                                    :style="genForm.type !== t.value ? {
                                        background: 'rgba(255,255,255,0.045)',
                                        border: '1px solid rgba(255,255,255,0.10)',
                                        boxShadow: 'inset 0 1px 0 rgba(255,255,255,0.07)'
                                    } : {}"
                                    class="py-2 px-3 rounded-lg text-xs font-medium transition-colors border flex items-center gap-1.5"
                                >
                                    <span>{{ t.emoji }}</span>
                                    {{ t.label }}
                                </button>
                            </div>
                        </div>

                        <!-- Opções de vídeo -->
                        <template v-if="genForm.type === 'video'">
                            <div class="rounded-xl p-3" style="background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.10);box-shadow:inset 0 1px 0 rgba(255,255,255,0.07)">
                                <p class="text-xs text-gray-400 mb-1 font-medium">Modelo Veo</p>
                                <select v-model="genForm.videoModel" class="input text-xs">
                                    <option value="veo-2.0-generate-001">Veo 2 (estável)</option>
                                    <option value="veo-3.0-generate-001">Veo 3 (com áudio)</option>
                                    <option value="veo-3.0-fast-generate-001">Veo 3 Fast</option>
                                </select>
                                <p class="text-xs text-gray-500 mt-2 flex items-center gap-1">
                                    <Clock class="w-3 h-3" />
                                    Geração leva 1–3 minutos. Aguarde após clicar.
                                </p>
                            </div>
                        </template>

                        <!-- Prompt extra -->
                        <div>
                            <label class="block text-xs text-gray-400 mb-1 uppercase tracking-wide">
                                Instruções adicionais
                                <span class="text-gray-600 normal-case ml-1">(opcional)</span>
                            </label>
                            <textarea
                                v-model="genForm.prompt"
                                rows="4"
                                class="input resize-none"
                                :placeholder="promptPlaceholder"
                            ></textarea>
                        </div>

                        <!-- Botão gerar -->
                        <button
                            type="submit"
                            :disabled="generating"
                            class="w-full bg-violet-600 hover:bg-violet-700 disabled:opacity-50 text-white font-semibold py-3 rounded-xl transition-colors flex items-center justify-center gap-2"
                        >
                            <template v-if="generating">
                                <Loader2 class="w-4 h-4 animate-spin" />
                                <span>{{ generatingLabel }}</span>
                            </template>
                            <template v-else>
                                <component :is="genTypeIcon" class="w-4 h-4" />
                                Gerar {{ genTypes.find(t => t.value === genForm.type)?.label }}
                            </template>
                        </button>

                        <!-- Timer para vídeo -->
                        <div v-if="generating && genForm.type === 'video'" class="text-center">
                            <div class="text-2xl font-mono text-violet-400">{{ formatTimer(elapsed) }}</div>
                            <p class="text-xs text-gray-500 mt-1">aguardando Veo processar...</p>
                        </div>
                    </form>

                    <!-- Legenda gerada -->
                    <div v-if="action?.caption" class="mt-5 pt-5" style="border-top:1px solid rgba(255,255,255,0.08)">
                        <h3 class="text-xs font-medium text-gray-400 mb-2 uppercase tracking-wide">Legenda gerada</h3>
                        <p class="text-sm text-gray-300 whitespace-pre-wrap leading-relaxed">{{ action.caption }}</p>
                        <div v-if="action.hashtags?.length" class="flex flex-wrap gap-1 mt-3">
                            <span
                                v-for="tag in action.hashtags"
                                :key="tag"
                                class="text-xs text-violet-400 bg-violet-500/10 px-2 py-0.5 rounded-full"
                            >#{{ tag.replace('#', '') }}</span>
                        </div>
                        <button @click="copyCaption" class="mt-3 text-xs text-gray-400 hover:text-white flex items-center gap-1 transition-colors">
                            <Copy class="w-3.5 h-3.5" />
                            {{ copied ? 'Copiado!' : 'Copiar legenda' }}
                        </button>
                    </div>
                </div>
            </div>

            <!-- Results gallery -->
            <div class="lg:col-span-3 space-y-6">
                <div v-if="generations.length === 0 && !generating" class="text-center py-20 glass-panel rounded-2xl">
                    <Film class="w-12 h-12 text-gray-600 mx-auto mb-4" />
                    <p class="text-gray-400">Nenhum conteúdo gerado ainda.</p>
                    <p class="text-sm text-gray-500 mt-1">Use o painel ao lado para gerar imagens, vídeos ou legendas.</p>
                </div>

                <!-- Skeleton durante geração de vídeo -->
                <div v-if="generating && genForm.type === 'video'" class="glass-panel rounded-2xl overflow-hidden">
                    <div class="aspect-video bg-gray-800 flex flex-col items-center justify-center gap-4">
                        <div class="w-16 h-16 rounded-full bg-violet-500/20 flex items-center justify-center">
                            <Film class="w-8 h-8 text-violet-400 animate-pulse" />
                        </div>
                        <div class="text-center">
                            <p class="text-white font-medium">Gerando vídeo com Veo</p>
                            <p class="text-sm text-gray-400 mt-1">Processamento em curso...</p>
                        </div>
                        <div class="w-48 h-1.5 bg-gray-700 rounded-full overflow-hidden">
                            <div class="h-full bg-violet-500 rounded-full animate-[progress_3s_ease-in-out_infinite]" style="width: 60%"></div>
                        </div>
                    </div>
                </div>

                <template v-for="gen in generations" :key="gen.id">
                    <div class="space-y-3">
                        <div class="flex items-center gap-2">
                            <StatusBadge :status="gen.status" />
                            <span class="text-xs text-gray-500">{{ formatDate(gen.created_at) }}</span>
                            <span class="text-xs text-gray-600">— {{ gen.model_used }}</span>
                            <button
                                @click="deleteGeneration(gen)"
                                class="ml-auto p-1 text-gray-600 hover:text-red-400 hover:bg-red-500/10 rounded-lg transition-colors"
                                :title="gen.session_id ? 'Remover da ação (mantém no chat)' : 'Excluir'"
                            >
                                <X class="w-3.5 h-3.5" />
                            </button>
                        </div>

                        <!-- Erro -->
                        <div v-if="gen.status === 'failed'" class="bg-red-500/10 border border-red-500/30 rounded-xl p-4 text-sm text-red-400">
                            {{ gen.error_message }}
                        </div>

                        <!-- Assets: imagens -->
                        <div v-if="imageAssets(gen).length"
                             :class="imageAssets(gen).length === 1 ? 'grid grid-cols-1' : 'grid grid-cols-2'"
                             class="gap-3">
                            <div
                                v-for="asset in imageAssets(gen)"
                                :key="asset.id"
                                class="relative group rounded-xl overflow-hidden bg-gray-800 aspect-square"
                            >
                                <img :src="asset.url" class="w-full h-full object-cover" />
                                <div class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity flex items-center justify-center gap-2">
                                    <button @click="downloadAsset(asset.url)" class="p-2 bg-white/10 hover:bg-white/20 rounded-lg">
                                        <Download class="w-4 h-4 text-white" />
                                    </button>
                                    <button @click="deleteAsset(asset)" class="p-2 bg-red-500/20 hover:bg-red-500/40 rounded-lg">
                                        <Trash2 class="w-4 h-4 text-red-400" />
                                    </button>
                                </div>
                                <div v-if="asset.metadata?.slide" class="absolute top-2 left-2 bg-black/70 text-xs text-white px-2 py-0.5 rounded-full">
                                    Slide {{ asset.metadata.slide }}
                                </div>
                            </div>
                        </div>

                        <!-- Assets: vídeos -->
                        <div v-for="asset in videoAssets(gen)" :key="asset.id" class="rounded-xl overflow-hidden bg-gray-900 border border-gray-800">
                            <video
                                :src="asset.url"
                                controls
                                playsinline
                                class="w-full max-h-96 bg-black"
                                :poster="null"
                            ></video>
                            <div class="flex items-center justify-between px-4 py-3">
                                <div class="flex items-center gap-2 text-sm text-gray-400">
                                    <Film class="w-4 h-4" />
                                    <span>{{ asset.duration ? asset.duration + 's' : '' }}</span>
                                    <span v-if="asset.size" class="text-gray-600">· {{ formatSize(asset.size) }}</span>
                                </div>
                                <div class="flex gap-2">
                                    <button @click="downloadAsset(asset.url)" class="flex items-center gap-1.5 px-3 py-1.5 btn-primary rounded-lg text-xs transition-colors">
                                        <Download class="w-3.5 h-3.5" />
                                        Baixar
                                    </button>
                                    <button @click="deleteAsset(asset)" class="p-1.5 bg-gray-800 hover:bg-red-500/20 rounded-lg text-gray-400 hover:text-red-400 transition-colors">
                                        <Trash2 class="w-3.5 h-3.5" />
                                    </button>
                                </div>
                            </div>
                        </div>

                        <!-- Texto gerado -->
                        <div v-if="gen.result_text && gen.type === 'text'" class="bg-gray-800 rounded-xl p-4 text-sm text-gray-300 whitespace-pre-wrap leading-relaxed">
                            {{ gen.result_text }}
                        </div>
                    </div>
                </template>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted } from 'vue'
import { useRoute, RouterLink } from 'vue-router'
import { Sparkles, Loader2, Download, Trash2, Copy, Film, Clock, Package, X, Pencil } from 'lucide-vue-next'
import api from '@/services/api'
import { downloadAsset } from '@/utils/download'
import StatusBadge from '@/components/StatusBadge.vue'
import PlatformIcon from '@/components/PlatformIcon.vue'
import TypeBadge from '@/components/TypeBadge.vue'

const route        = useRoute()
const campaignId   = computed(() => route.params.campaignId)
const actionId     = computed(() => route.params.actionId)
const action       = ref(null)
const generations  = ref([])
const generating   = ref(false)
const copied       = ref(false)
const elapsed      = ref(0)
let   timer        = null

const editingBrief = ref(false)
const briefDraft   = ref('')
const savingBrief  = ref(false)

function startEditBrief() {
    briefDraft.value = action.value.brief || ''
    editingBrief.value = true
}

function cancelEditBrief() {
    editingBrief.value = false
}

async function saveBrief() {
    savingBrief.value = true
    try {
        await api.put(`/actions/${actionId.value}`, { brief: briefDraft.value })
        action.value.brief = briefDraft.value
        editingBrief.value = false
    } finally {
        savingBrief.value = false
    }
}

const genForm = ref({ type: 'image', prompt: '', videoModel: 'veo-2.0-generate-001' })

const genTypes = [
    { value: 'image',    emoji: '🖼️',  label: 'Imagem' },
    { value: 'video',    emoji: '🎬',  label: 'Vídeo' },
    { value: 'carousel', emoji: '🎠',  label: 'Carrossel' },
    { value: 'text',     emoji: '✍️',  label: 'Legenda' },
]

const allAssets = computed(() => generations.value.flatMap(g => g.assets || []))

const promptPlaceholder = computed(() => {
    const map = {
        image:    'Ex: fundo branco, luz natural, foco no produto...',
        video:    'Ex: câmera lenta, música animada, transição suave...',
        carousel: 'Ex: 5 slides mostrando os benefícios do produto...',
        text:     'Ex: tom descontraído, foco no desconto, emoji...',
    }
    return map[genForm.value.type] || ''
})

const generatingLabel = computed(() => {
    if (genForm.value.type === 'video') return 'Aguardando Veo...'
    return 'Gerando...'
})

const genTypeIcon = computed(() => {
    return genForm.value.type === 'video' ? Film : Sparkles
})

const imageAssets = (gen) => (gen.assets || []).filter(a => a.type === 'image')
const videoAssets = (gen) => (gen.assets || []).filter(a => a.type === 'video')

async function fetchAction() {
    const { data } = await api.get(`/actions/${actionId.value}`)
    action.value     = data
    generations.value = data.generations || []
}

async function generate() {
    generating.value = true
    elapsed.value    = 0

    if (genForm.value.type === 'video') {
        timer = setInterval(() => elapsed.value++, 1000)
    }

    try {
        const payload = { type: genForm.value.type, prompt: genForm.value.prompt }
        if (genForm.value.type === 'video') {
            payload.video_model = genForm.value.videoModel
        }

        const { data } = await api.post(`/actions/${actionId.value}/generate`, payload)
        generations.value.unshift(data)
        await fetchAction()
    } catch (e) {
        await fetchAction()
        alert(e.response?.data?.message || 'Erro na geração.')
    } finally {
        generating.value = false
        clearInterval(timer)
        timer = null
    }
}

async function deleteAsset(asset) {
    if (!confirm('Excluir este asset?')) return
    await api.delete(`/assets/${asset.id}`)
    await fetchAction()
}

async function deleteGeneration(gen) {
    try {
        if (gen.session_id) {
            // Veio do chat — desassocia da ação sem excluir
            await api.patch(`/generations/${gen.id}/detach`)
        } else {
            await api.delete(`/generations/${gen.id}`)
        }
        generations.value = generations.value.filter(g => g.id !== gen.id)
    } catch {
        alert('Não foi possível remover. Tente novamente.')
    }
}

async function copyCaption() {
    await navigator.clipboard.writeText(action.value.caption)
    copied.value = true
    setTimeout(() => (copied.value = false), 2000)
}

function formatDate(d) {
    return d ? new Date(d).toLocaleString('pt-BR') : ''
}

function formatTimer(s) {
    const m = Math.floor(s / 60).toString().padStart(2, '0')
    const sec = (s % 60).toString().padStart(2, '0')
    return `${m}:${sec}`
}

function formatSize(bytes) {
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(0) + ' KB'
    return (bytes / (1024 * 1024)).toFixed(1) + ' MB'
}

onMounted(fetchAction)
onUnmounted(() => clearInterval(timer))
</script>

<style scoped>
@reference "tailwindcss";

@keyframes progress {
    0%   { transform: translateX(-100%); }
    50%  { transform: translateX(50%); }
    100% { transform: translateX(200%); }
}
</style>

