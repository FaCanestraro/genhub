<template>
    <div class="page flex flex-col gap-6">

        <!-- Header -->
        <header class="flex flex-col gap-4">
            <nav aria-label="Navegação" class="flex items-center gap-1.5 text-sm min-w-0" style="color: var(--text-muted)">
                <RouterLink to="/campaigns" class="hover:text-white transition-colors">Campanhas</RouterLink>
                <ChevronRight class="w-3.5 h-3.5 flex-shrink-0" />
                <RouterLink v-if="action" :to="`/campaigns/${action.campaign_id}`" class="hover:text-white transition-colors truncate">{{ action.campaign?.name }}</RouterLink>
                <ChevronRight class="w-3.5 h-3.5 flex-shrink-0" />
                <span class="text-white truncate">{{ action?.title ?? '...' }}</span>
            </nav>

            <div v-if="!action" class="shimmer h-9 w-80 max-w-full rounded-lg"></div>
            <div v-else class="flex flex-col gap-3">
                <h1 class="page-hero-title text-3xl leading-tight">{{ action.title }}</h1>
                <div class="flex flex-wrap items-center gap-2">
                    <PlatformIcon :platform="action.platform" />
                    <TypeBadge :type="action.type" />
                    <span v-if="action.resolution" class="text-xs px-2.5 py-1 rounded-full tabular-nums" style="color: var(--text-secondary); background: var(--surface-2); border: 1px solid var(--border-subtle)">
                        {{ action.resolution.replace('x', '×') }}
                    </span>
                    <StatusBadge :status="action.status" />
                </div>
            </div>
        </header>

        <!-- Context: brief + products -->
        <div v-if="action" class="grid grid-cols-1 lg:grid-cols-3 gap-4">
            <Panel title="Brief" :icon="FileText" :class="action.products?.length ? 'lg:col-span-2' : 'lg:col-span-3'">
                <template #actions>
                    <button v-if="!editingBrief && auth.can('campaigns', 'edit') && action.brief" type="button" @click="startEditBrief" class="icon-btn -my-2" aria-label="Editar brief">
                        <Pencil class="w-4 h-4" />
                    </button>
                </template>

                <div v-if="editingBrief" class="flex flex-col gap-3">
                    <textarea
                        v-model="briefDraft"
                        rows="4"
                        class="input resize-none"
                        placeholder="Ex: oferta de fim de semana, destaque no preço, tom alegre, público família..."
                        aria-label="Brief"
                        @keydown.meta.enter="saveBrief"
                        @keydown.ctrl.enter="saveBrief"
                        v-focus
                    ></textarea>
                    <div class="flex items-center justify-end gap-2">
                        <span class="text-[11px] mr-auto" style="color: var(--text-muted)">Ctrl + Enter para salvar</span>
                        <button type="button" @click="editingBrief = false" class="btn-secondary !py-2">Cancelar</button>
                        <button type="button" @click="saveBrief" :disabled="savingBrief" class="btn-primary px-4 py-2 rounded-xl text-sm font-semibold text-white">
                            {{ savingBrief ? 'Salvando...' : 'Salvar' }}
                        </button>
                    </div>
                </div>
                <p v-else-if="action.brief" class="text-sm leading-relaxed whitespace-pre-wrap" style="color: var(--text-secondary)">{{ action.brief }}</p>
                <div v-else class="flex flex-wrap items-center justify-between gap-3">
                    <p class="text-sm" style="color: var(--text-muted)">Sem brief. Descreva o objetivo da peça para a IA acertar de primeira.</p>
                    <button v-if="auth.can('campaigns', 'edit')" type="button" @click="startEditBrief" class="btn-secondary !py-2">
                        <Pencil class="w-3.5 h-3.5" /> Escrever brief
                    </button>
                </div>
            </Panel>

            <Panel v-if="action.products?.length" title="Produtos" :icon="Package">
                <ul class="flex flex-col gap-2">
                    <li v-for="p in action.products" :key="p.id" class="flex items-center gap-3">
                        <span class="w-10 h-10 rounded-lg overflow-hidden flex-shrink-0 flex items-center justify-center bg-white">
                            <img v-if="p.images?.length" :src="p.images[0]" :alt="p.name" class="w-full h-full object-contain" />
                            <Package v-else class="w-4 h-4 text-gray-400" />
                        </span>
                        <span class="min-w-0 flex-1">
                            <span class="block text-sm text-white leading-snug line-clamp-2" :title="p.name">{{ p.name }}</span>
                            <span v-if="p.price" class="block text-xs font-semibold mt-0.5" style="color: color-mix(in srgb, var(--brand) 55%, white)">R$ {{ numberToCurrency(p.price) }}</span>
                        </span>
                    </li>
                </ul>
            </Panel>
        </div>

        <!-- Generator + results -->
        <div class="grid grid-cols-1 lg:grid-cols-[380px_minmax(0,1fr)] gap-6 items-start">

            <!-- Generator -->
            <section class="panel lg:sticky lg:top-6 flex flex-col gap-5" aria-labelledby="gen-title">
                <h2 id="gen-title" class="panel-title flex items-center gap-2.5">
                    <span class="panel-icon"><Sparkles class="w-4 h-4" /></span>
                    Gerar com IA
                </h2>

                <form @submit.prevent="generate" class="flex flex-col gap-5">
                    <div>
                        <p class="field-label">O que gerar</p>
                        <div class="grid grid-cols-2 gap-2" role="radiogroup" aria-label="Tipo de geração">
                            <button
                                v-for="t in GEN_TYPES"
                                :key="t.value"
                                type="button"
                                role="radio"
                                :aria-checked="genForm.type === t.value"
                                @click="genForm.type = t.value"
                                class="gen-option"
                                :class="{ active: genForm.type === t.value }"
                            >
                                <component :is="t.icon" class="w-4 h-4 flex-shrink-0" />
                                <span class="min-w-0">
                                    <span class="block text-sm font-medium text-white">{{ t.label }}</span>
                                    <span class="block text-[11px] leading-tight" style="color: var(--text-muted)">{{ t.hint }}</span>
                                </span>
                            </button>
                        </div>
                    </div>

                    <div v-if="countOptions">
                        <p class="field-label">{{ genForm.type === 'carousel' ? 'Slides' : 'Quantas imagens' }}</p>
                        <div class="flex flex-wrap gap-1.5" role="radiogroup" :aria-label="genForm.type === 'carousel' ? 'Slides' : 'Quantidade de imagens'">
                            <button v-for="n in countOptions" :key="n" type="button" role="radio" :aria-checked="count === n" @click="count = n" class="filter-chip !min-h-9 !px-3.5 tabular-nums" :class="{ active: count === n }">{{ n }}</button>
                        </div>
                    </div>

                    <div>
                        <label for="gen-prompt" class="field-label">Instruções adicionais <span style="color: var(--text-muted)">(opcional)</span></label>
                        <textarea id="gen-prompt" v-model="genForm.prompt" rows="3" class="input resize-none text-sm" :placeholder="PLACEHOLDERS[genForm.type]"></textarea>
                        <div class="flex flex-wrap gap-1.5 mt-2" aria-label="Sugestões">
                            <button v-for="s in SUGGESTIONS[genForm.type]" :key="s" type="button" @click="addSuggestion(s)" class="suggestion">
                                <Plus class="w-3 h-3" /> {{ s }}
                            </button>
                        </div>
                    </div>

                    <button v-if="auth.can('generate', 'create')" type="submit" :disabled="busy" class="btn-primary w-full inline-flex items-center justify-center gap-2 min-h-12 rounded-xl text-sm font-semibold text-white disabled:cursor-not-allowed">
                        <Loader2 v-if="busy" class="w-4 h-4 animate-spin" />
                        <component :is="currentType.icon" v-else class="w-4 h-4" />
                        {{ sending ? 'Enviando...' : busy ? 'Gerando, aguarde...' : `Gerar ${currentType.label.toLowerCase()}` }}
                    </button>
                    <p v-if="busy && !sending" class="-mt-2 text-xs text-center" style="color: var(--text-muted)">Você poderá gerar de novo assim que a geração atual terminar.</p>
                    <p v-else class="text-xs text-center" style="color: var(--text-muted)">Você não tem permissão para gerar conteúdo.</p>
                </form>
            </section>

            <!-- Results -->
            <div class="flex flex-col gap-4 min-w-0">

                <!-- Caption -->
                <Panel v-if="action?.caption" title="Legenda" :icon="Type">
                    <template #actions>
                        <button type="button" @click="copyCaption" class="panel-link">
                            <component :is="copied ? Check : Copy" class="w-3.5 h-3.5" /> {{ copied ? 'Copiada' : 'Copiar' }}
                        </button>
                    </template>
                    <p class="text-sm leading-relaxed whitespace-pre-wrap" style="color: var(--text-secondary)">{{ action.caption }}</p>
                    <div v-if="action.hashtags?.length" class="flex flex-wrap gap-1.5 mt-3">
                        <span v-for="tag in action.hashtags" :key="tag" class="text-xs px-2 py-0.5 rounded-full" style="color: color-mix(in srgb, var(--brand) 55%, white); background: color-mix(in srgb, var(--brand) 12%, transparent)">
                            #{{ tag.replace('#', '') }}
                        </span>
                    </div>
                </Panel>

                <div v-if="!action" class="shimmer h-72 rounded-2xl"></div>

                <EmptyState
                    v-else-if="!generations.length"
                    class="panel"
                    :icon="Sparkles"
                    title="Nenhum criativo ainda"
                    text="Escolha o que gerar no painel e clique em Gerar. Os resultados aparecem aqui."
                />

                <!-- Generations -->
                <article v-for="gen in generations" :key="gen.id" class="panel !p-4 flex flex-col gap-4">
                    <header class="flex flex-wrap items-center gap-2">
                        <span class="flex items-center gap-1.5 text-sm font-medium text-white">
                            <component :is="typeOf(gen.type).icon" class="w-4 h-4" style="color: var(--text-secondary)" />
                            {{ typeOf(gen.type).label }}
                        </span>
                        <StatusBadge :status="gen.status" />
                        <span class="text-xs" style="color: var(--text-muted)" :title="gen.model_used">{{ formatDate(gen.created_at) }}</span>
                        <button
                            v-if="gen.session_id ? auth.can('generate', 'edit') : auth.can('generate', 'delete')"
                            type="button"
                            @click="deleteGeneration(gen)"
                            class="icon-btn ml-auto hover:!text-red-400"
                            :aria-label="gen.session_id ? 'Remover da ação (continua no histórico)' : 'Excluir geração'"
                            :title="gen.session_id ? 'Remover da ação (continua no histórico)' : 'Excluir geração'"
                        >
                            <Trash2 class="w-4 h-4" />
                        </button>
                    </header>

                    <!-- Running -->
                    <div v-if="isRunning(gen)" class="rounded-xl flex flex-col items-center justify-center gap-3 py-12" style="background: var(--surface-1); border: 1px dashed var(--border-soft)">
                        <Loader2 class="w-7 h-7 animate-spin" style="color: color-mix(in srgb, var(--brand) 55%, white)" />
                        <p class="text-sm text-white">Gerando {{ typeOf(gen.type).label.toLowerCase() }}...</p>
                        <p class="text-xs tabular-nums" style="color: var(--text-muted)">
                            {{ elapsed(gen) }}<template v-if="gen.type === 'video'"> · vídeos levam de 1 a 3 minutos</template>
                        </p>
                    </div>

                    <!-- Failed -->
                    <div v-else-if="gen.status === 'failed'" class="flex items-start gap-2 rounded-xl p-4 text-sm text-red-300 bg-red-500/10 border border-red-500/30" role="alert">
                        <AlertCircle class="w-4 h-4 mt-0.5 flex-shrink-0" />
                        <span>{{ gen.error_message || 'A geração falhou.' }}</span>
                    </div>

                    <!-- Images -->
                    <div v-if="imageAssets(gen).length" class="grid gap-3" :class="imageAssets(gen).length === 1 ? 'grid-cols-1 sm:max-w-md' : 'grid-cols-2'">
                        <div v-for="(asset, i) in imageAssets(gen)" :key="asset.id" class="asset group">
                            <button type="button" @click="viewer = { assets: imageAssets(gen), start: i }" class="block w-full h-full cursor-zoom-in" aria-label="Ver em tela cheia">
                                <img :src="asset.url" :alt="`Criativo ${asset.metadata?.slide ? 'slide ' + asset.metadata.slide : ''}`" loading="lazy" class="w-full h-full object-cover" />
                            </button>
                            <span v-if="asset.metadata?.slide" class="absolute top-2 left-2 text-[11px] text-white px-2 py-0.5 rounded-full bg-black/70">Slide {{ asset.metadata.slide }}</span>
                            <div class="asset-actions">
                                <button type="button" @click="downloadAsset(asset.url)" class="tile-btn" aria-label="Baixar"><Download class="w-4 h-4" /></button>
                                <button v-if="auth.can('gallery', 'delete')" type="button" @click="deleteAsset(asset)" class="tile-btn hover:!text-red-400" aria-label="Excluir"><Trash2 class="w-4 h-4" /></button>
                            </div>
                        </div>
                    </div>

                    <!-- Videos -->
                    <div v-for="asset in videoAssets(gen)" :key="asset.id" class="rounded-xl overflow-hidden" style="border: 1px solid var(--border-subtle)">
                        <video :src="asset.url" controls playsinline class="w-full max-h-[28rem] bg-black"></video>
                        <div class="flex items-center justify-between gap-3 px-4 py-3">
                            <span class="flex items-center gap-2 text-xs" style="color: var(--text-muted)">
                                <Film class="w-4 h-4" />
                                <span v-if="asset.duration">{{ asset.duration }}s</span>
                                <span v-if="asset.size">· {{ formatSize(asset.size) }}</span>
                            </span>
                            <div class="flex gap-2">
                                <button type="button" @click="downloadAsset(asset.url)" class="btn-secondary !py-1.5 !px-3 !text-xs"><Download class="w-3.5 h-3.5" /> Baixar</button>
                                <button v-if="auth.can('gallery', 'delete')" type="button" @click="deleteAsset(asset)" class="icon-btn hover:!text-red-400" aria-label="Excluir vídeo"><Trash2 class="w-4 h-4" /></button>
                            </div>
                        </div>
                    </div>

                    <!-- Text -->
                    <div v-if="gen.result_text && gen.type === 'text'" class="relative rounded-xl p-4 pr-12 text-sm leading-relaxed whitespace-pre-wrap" style="background: var(--surface-1); color: var(--text-secondary)">
                        {{ gen.result_text }}
                        <button type="button" @click="copyText(gen.result_text)" class="icon-btn absolute top-2 right-2" aria-label="Copiar texto"><Copy class="w-4 h-4" /></button>
                    </div>
                </article>
            </div>
        </div>

        <MediaViewer v-if="viewer" :assets="viewer.assets" :start="viewer.start" @close="viewer = null" />
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { useRoute, RouterLink } from 'vue-router'
import { Sparkles, Loader2, Download, Trash2, Copy, Check, Film, Package, Pencil, ChevronRight, FileText, Type, Image as ImageIcon, GalleryHorizontal, Clapperboard, Plus, AlertCircle } from 'lucide-vue-next'
import api from '@/services/api'
import { downloadAsset } from '@/utils/download'
import { pollGeneration } from '@/utils/pollGeneration'
import { numberToCurrency } from '@/utils/mask'
import StatusBadge from '@/components/StatusBadge.vue'
import PlatformIcon from '@/components/PlatformIcon.vue'
import TypeBadge from '@/components/TypeBadge.vue'
import Panel from '@/components/ui/Panel.vue'
import EmptyState from '@/components/ui/EmptyState.vue'
import MediaViewer from '@/components/ui/MediaViewer.vue'
import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toast'

const auth = useAuthStore()
const toast = useToastStore()
const route = useRoute()
const actionId = computed(() => route.params.actionId)

const action = ref(null)
const generations = ref([])
const sending = ref(false)
const copied = ref(false)

const vFocus = { mounted: el => el.focus() }

// ── Brief ───────────────────────────────────────────────────────
const editingBrief = ref(false)
const briefDraft = ref('')
const savingBrief = ref(false)

function startEditBrief() {
    briefDraft.value = action.value.brief || ''
    editingBrief.value = true
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

// ── Generator ───────────────────────────────────────────────────
const GEN_TYPES = [
    { value: 'image', label: 'Imagem', hint: 'Arte única', icon: ImageIcon },
    { value: 'carousel', label: 'Carrossel', hint: 'Sequência de slides', icon: GalleryHorizontal },
    { value: 'video', label: 'Vídeo', hint: 'De 1 a 3 minutos', icon: Clapperboard },
    { value: 'text', label: 'Legenda', hint: 'Texto + hashtags', icon: Type },
]
const typeOf = (value) => GEN_TYPES.find(t => t.value === value) ?? { label: value, icon: Sparkles }

const PLACEHOLDERS = {
    image: 'Ex: fundo branco, luz natural, foco no produto...',
    carousel: 'Ex: 5 slides, um produto por slide...',
    video: 'Ex: câmera lenta, close no produto, clima festivo...',
    text: 'Ex: tom descontraído, foco no desconto...',
}
const SUGGESTIONS = {
    image: ['Fundo branco', 'Luz natural', 'Destaque no preço', 'Clima de fim de semana'],
    carousel: ['5 slides', 'Um produto por slide', 'Último slide com chamada para a loja'],
    video: ['Câmera lenta', 'Close no produto', 'Clima festivo'],
    text: ['Tom descontraído', 'Foco no desconto', 'Com emojis', 'Chamada para visitar a loja'],
}

const genForm = ref({ type: 'image', prompt: '' })
const currentType = computed(() => typeOf(genForm.value.type))

// Images per run / slides per carousel. Sent with each generation so what you see is what runs.
const COUNT_OPTIONS = { image: [1, 2, 3, 4], carousel: [3, 4, 5, 6, 7, 8] }
const DEFAULT_COUNT = { image: 1, carousel: 5 }
const count = ref(DEFAULT_COUNT.image)
const countOptions = computed(() => COUNT_OPTIONS[genForm.value.type])
watch(() => genForm.value.type, (type) => { count.value = DEFAULT_COUNT[type] })

const viewer = ref(null)

function addSuggestion(s) {
    const p = genForm.value.prompt.trim()
    genForm.value.prompt = p ? `${p}, ${s.toLowerCase()}` : s
}

// ── Results ─────────────────────────────────────────────────────
const imageAssets = (gen) => (gen.assets || []).filter(a => a.type === 'image')
const videoAssets = (gen) => (gen.assets || []).filter(a => a.type === 'video')
const isRunning = (gen) => ['pending', 'processing'].includes(gen.status)
// One generation at a time per action: blocked while sending or while any job is still running.
const busy = computed(() => sending.value || generations.value.some(isRunning))

// A 1s clock only while something is generating, for the elapsed timers.
const now = ref(Date.now())
let clock = null
watch(() => generations.value.some(isRunning), (running) => {
    clearInterval(clock)
    clock = running ? setInterval(() => (now.value = Date.now()), 1000) : null
})
function elapsed(gen) {
    const s = Math.max(0, Math.floor((now.value - new Date(gen.created_at)) / 1000))
    return `${String(Math.floor(s / 60)).padStart(2, '0')}:${String(s % 60).padStart(2, '0')}`
}

async function fetchAction() {
    const { data } = await api.get(`/actions/${actionId.value}`)
    action.value = data
    generations.value = data.generations || []
}

// The backend queues the job: send it, show it as running and keep polling in the background,
// so the user can queue more generations meanwhile.
async function generate() {
    if (busy.value) return
    sending.value = true
    let data
    try {
        ;({ data } = await api.post(`/actions/${actionId.value}/generate`, { type: genForm.value.type, prompt: genForm.value.prompt, quantity: countOptions.value ? count.value : undefined }))
        generations.value.unshift(data)
    } catch {
        return // already toasted by the api interceptor
    } finally {
        sending.value = false
    }
    track(data.id)
}

// Polls one queued generation until it finishes and swaps the result into the list.
async function track(id) {
    try {
        const final = await pollGeneration(id)
        const idx = generations.value.findIndex(g => g.id === id)
        if (idx !== -1) generations.value[idx] = final
        if (final.type === 'text') await fetchAction() // caption/hashtags live on the action
    } catch (e) {
        if (!e.response) toast.push(e.message || 'Erro na geração.', 'error')
        await fetchAction()
    }
}

async function deleteAsset(asset) {
    if (!confirm('Excluir este criativo?')) return
    await api.delete(`/assets/${asset.id}`)
    await fetchAction()
}

async function deleteGeneration(gen) {
    if (gen.session_id) {
        // Came from the chat: detach from the action without deleting it.
        await api.patch(`/generations/${gen.id}/detach`)
    } else {
        if (!confirm('Excluir esta geração e seus criativos?')) return
        await api.delete(`/generations/${gen.id}`)
    }
    generations.value = generations.value.filter(g => g.id !== gen.id)
}

async function copyCaption() {
    const tags = action.value.hashtags?.length ? '\n\n' + action.value.hashtags.map(t => `#${t.replace('#', '')}`).join(' ') : ''
    await navigator.clipboard.writeText(action.value.caption + tags)
    copied.value = true
    setTimeout(() => (copied.value = false), 2000)
}

async function copyText(text) {
    await navigator.clipboard.writeText(text)
    toast.push('Texto copiado.', 'success')
}

function formatDate(d) {
    return d ? new Date(d).toLocaleString('pt-BR', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' }) : ''
}

function formatSize(bytes) {
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(0) + ' KB'
    return (bytes / (1024 * 1024)).toFixed(1) + ' MB'
}

onMounted(async () => {
    await fetchAction()
    // Resume tracking anything still running from before (e.g. after a reload).
    generations.value.filter(isRunning).forEach(g => track(g.id))
})
onUnmounted(() => clearInterval(clock))
</script>

<style scoped>
.gen-option {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px 12px;
    min-height: 56px;
    border-radius: 12px;
    text-align: left;
    color: var(--text-secondary);
    border: 1px solid var(--border-subtle);
    background: var(--surface-1);
    transition: border-color 0.15s, background-color 0.15s, color 0.15s;
}
.gen-option:hover { border-color: var(--border-soft); color: #fff; }
.gen-option.active {
    color: #fff;
    border-color: color-mix(in srgb, var(--brand) 60%, transparent);
    background: color-mix(in srgb, var(--brand) 16%, transparent);
}

.suggestion {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 4px 10px;
    border-radius: 9999px;
    font-size: 11px;
    color: var(--text-secondary);
    border: 1px dashed var(--border-soft);
    transition: color 0.15s, border-color 0.15s;
}
.suggestion:hover { color: #fff; border-color: color-mix(in srgb, var(--brand) 55%, transparent); }

.asset {
    position: relative;
    aspect-ratio: 1 / 1;
    border-radius: 12px;
    overflow: hidden;
    background: var(--surface-2);
}
.asset-actions {
    position: absolute;
    top: 8px;
    right: 8px;
    display: flex;
    gap: 6px;
    opacity: 0;
    transition: opacity 0.18s;
}
.asset:hover .asset-actions,
.asset:focus-within .asset-actions { opacity: 1; }
@media (hover: none) { .asset-actions { opacity: 1; } }
.tile-btn {
    width: 34px;
    height: 34px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 9px;
    color: #fff;
    background: rgba(3, 2, 18, 0.65);
    backdrop-filter: blur(8px);
    border: 1px solid rgba(255, 255, 255, 0.14);
    transition: color 0.15s;
}
</style>
