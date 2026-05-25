<template>
    <div class="flex h-full" style="background: var(--bg-base)">

        <!-- Sidebar de sessões -->
        <aside class="w-56 flex-shrink-0 flex flex-col" style="background: rgba(255,255,255,0.025); border-right: 1px solid var(--border-subtle)">
            <div class="p-3 pt-4">
                <button
                    @click="newChat"
                    class="w-full flex items-center justify-center gap-2 px-4 py-2.5 btn-primary text-xs font-semibold rounded-xl transition-colors"
                >
                    <Plus class="w-3.5 h-3.5" />
                    Novo Chat
                </button>
            </div>

            <div class="flex-1 overflow-y-auto px-2 pb-2 space-y-0.5">
                <div
                    v-for="s in sessions"
                    :key="s.id"
                    class="flex items-start gap-1 rounded-xl group transition-all"
                    :style="currentSessionId === s.id
                        ? 'background: color-mix(in srgb, var(--brand) 14%, transparent); border: 1px solid color-mix(in srgb, var(--brand) 30%, transparent)'
                        : 'border: 1px solid transparent'"
                    @mouseenter="e => { if (currentSessionId !== s.id) e.currentTarget.style.background = 'rgba(255,255,255,0.04)' }"
                    @mouseleave="e => { if (currentSessionId !== s.id) e.currentTarget.style.background = 'transparent' }"
                >
                    <!-- Modo edição -->
                    <div v-if="editingSessionId === s.id" class="flex-1 flex items-center gap-1 px-2 py-1.5">
                        <input
                            v-model="editingTitle"
                            @keydown.enter.prevent="saveSessionTitle(s)"
                            @keydown.esc="cancelEdit"
                            ref="editInput"
                            class="flex-1 rounded-lg px-2 py-1 text-sm text-white focus:outline-none min-w-0"
                            style="background: rgba(255,255,255,0.07); border: 1px solid rgba(255,255,255,0.15)"
                            maxlength="60"
                        />
                        <button @click="saveSessionTitle(s)" class="p-1 text-green-400 hover:text-green-300 flex-shrink-0">
                            <Check class="w-3.5 h-3.5" />
                        </button>
                        <button @click="cancelEdit" class="p-1 flex-shrink-0" style="color: var(--text-muted)">
                            <X class="w-3.5 h-3.5" />
                        </button>
                    </div>

                    <!-- Modo normal -->
                    <template v-else>
                        <button
                            @click="selectSession(s.id)"
                            class="flex-1 flex items-start gap-2 px-2.5 py-2 text-left min-w-0"
                        >
                            <MessageSquare class="w-3.5 h-3.5 flex-shrink-0 mt-0.5 transition-colors" :style="currentSessionId === s.id ? 'color: #a78bfa' : 'color: var(--text-muted)'" />
                            <div class="min-w-0">
                                <p class="text-xs font-medium truncate leading-snug" :style="currentSessionId === s.id ? 'color: #fff' : 'color: #d1d5db'">{{ s.title }}</p>
                                <p class="text-xs mt-0.5 truncate" style="color: var(--text-muted)">
                                    {{ formatSessionDate(s.createdAt) }}
                                </p>
                            </div>
                        </button>
                        <div class="opacity-0 group-hover:opacity-100 flex items-center gap-0.5 mt-1.5 mr-1.5 transition-all flex-shrink-0">
                            <button
                                @click.stop="startEdit(s)"
                                class="p-1.5 rounded-lg transition-colors"
                                style="color: var(--text-muted)"
                                title="Renomear"
                            >
                                <Pencil class="w-3 h-3" />
                            </button>
                            <button
                                @click.stop="deleteSession(s)"
                                class="p-1.5 rounded-lg transition-colors hover:text-red-400"
                                style="color: var(--text-muted)"
                                title="Excluir chat"
                            >
                                <Trash2 class="w-3 h-3" />
                            </button>
                        </div>
                    </template>
                </div>
            </div>
        </aside>

        <!-- Área principal do chat -->
        <div class="flex-1 flex flex-col min-w-0">

            <!-- Header -->
            <div class="flex items-center gap-3 px-6 py-4 flex-shrink-0" style="border-bottom: 1px solid var(--border-subtle)">
                <div class="w-9 h-9 rounded-xl flex items-center justify-center flex-shrink-0" style="background: var(--brand); box-shadow: 0 0 20px color-mix(in srgb, var(--brand) 40%, transparent)">
                    <Sparkles class="w-4 h-4 text-white" />
                </div>
                <div>
                    <h1 class="text-sm font-semibold text-white leading-tight">Motor de Criação com IA</h1>
                    <p class="text-xs" style="color: var(--text-muted)">Crie vídeos e imagens para o seu negócio</p>
                </div>
            </div>

            <!-- Mensagens -->
            <div ref="messagesEl" class="flex-1 overflow-y-auto">
                <!-- Boas-vindas -->
                <div
                    v-if="currentGenerations.length === 0 && !generating"
                    class="flex flex-col items-center justify-center h-full gap-5 text-center px-8"
                >
                    <div class="w-16 h-16 rounded-2xl bg-violet-600 flex items-center justify-center shadow-lg shadow-violet-900/40">
                        <Sparkles class="w-8 h-8 text-white" />
                    </div>
                    <div>
                        <h2 class="text-xl font-semibold text-white">Bem-vindo ao Motor de Criação IA</h2>
                        <p class="text-gray-500 mt-2 text-sm">Configure as opções abaixo e descreva o conteúdo que deseja gerar</p>
                    </div>
                </div>

                <!-- Chat -->
                <div v-else class="max-w-3xl mx-auto px-6 py-6 space-y-8">
                    <template v-for="gen in currentGenerations" :key="gen.id">
                        <!-- Mensagem do usuário -->
                        <div class="flex justify-end">
                            <div class="bg-gray-800 rounded-2xl rounded-tr-sm px-4 py-3 max-w-lg">
                                <p class="text-sm text-gray-200 leading-relaxed">{{ gen.prompt }}</p>
                            </div>
                        </div>

                        <!-- Resposta da IA -->
                        <div class="flex gap-3">
                            <div class="w-8 h-8 rounded-lg bg-violet-600 flex items-center justify-center flex-shrink-0 mt-0.5">
                                <Sparkles class="w-4 h-4 text-white" />
                            </div>
                            <div class="flex-1 min-w-0 space-y-3">
                                <div v-if="gen.status === 'failed'" class="bg-red-500/10 border border-red-500/30 rounded-xl p-4 text-sm text-red-400">
                                    {{ gen.error_message }}
                                </div>

                                <!-- Imagens -->
                                <div
                                    v-if="imageAssets(gen).length"
                                    :class="imageAssets(gen).length === 1 ? 'max-w-xs' : 'grid grid-cols-2 gap-2 max-w-md'"
                                >
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
                                            <button @click="deleteAsset(asset, gen)" class="p-2 bg-red-500/20 hover:bg-red-500/40 rounded-lg">
                                                <Trash2 class="w-4 h-4 text-red-400" />
                                            </button>
                                        </div>
                                        <div v-if="asset.metadata?.slide" class="absolute top-2 left-2 bg-black/70 text-xs text-white px-2 py-0.5 rounded-full">
                                            Slide {{ asset.metadata.slide }}
                                        </div>
                                    </div>
                                </div>

                                <!-- Vídeo -->
                                <div v-for="asset in videoAssets(gen)" :key="asset.id" class="rounded-xl overflow-hidden bg-gray-900 border border-gray-800 max-w-md">
                                    <video :src="asset.url" controls playsinline class="w-full max-h-72 bg-black"></video>
                                    <div class="flex items-center justify-between px-3 py-2">
                                        <span class="text-xs text-gray-500">{{ asset.duration ? asset.duration + 's' : '' }} {{ asset.size ? '· ' + formatSize(asset.size) : '' }}</span>
                                        <button @click="downloadAsset(asset.url)" class="flex items-center gap-1 text-xs brand-text hover:opacity-80 transition-opacity">
                                            <Download class="w-3.5 h-3.5" />
                                            Baixar
                                        </button>
                                    </div>
                                </div>

                                <!-- Legenda/texto -->
                                <div v-if="gen.type === 'text' && gen.result_text" class="bg-gray-800 rounded-2xl rounded-tl-sm px-4 py-3 max-w-lg">
                                    <p class="text-sm text-gray-200 whitespace-pre-wrap leading-relaxed">{{ parsedCaption(gen).caption }}</p>
                                    <div v-if="parsedCaption(gen).hashtags?.length" class="flex flex-wrap gap-1 mt-2">
                                        <span v-for="tag in parsedCaption(gen).hashtags" :key="tag"
                                              class="text-xs text-violet-400 bg-violet-500/10 px-2 py-0.5 rounded-full">
                                            #{{ tag.replace('#', '') }}
                                        </span>
                                    </div>
                                    <button @click="copyText(parsedCaption(gen).caption)" class="mt-2 text-xs text-gray-500 hover:text-white flex items-center gap-1 transition-colors">
                                        <Copy class="w-3 h-3" />
                                        {{ copied ? 'Copiado!' : 'Copiar' }}
                                    </button>
                                </div>

                                <button @click="deleteGeneration(gen)" class="text-xs text-gray-700 hover:text-red-400 flex items-center gap-1 transition-colors">
                                    <Trash2 class="w-3 h-3" />
                                    Excluir
                                </button>
                            </div>
                        </div>
                    </template>

                    <!-- Gerando... -->
                    <div v-if="generating" class="flex gap-3">
                        <div class="w-8 h-8 rounded-lg bg-violet-600 flex items-center justify-center flex-shrink-0">
                            <Loader2 class="w-4 h-4 text-white animate-spin" />
                        </div>
                        <div class="bg-gray-800 rounded-2xl rounded-tl-sm px-4 py-3 flex items-center gap-3">
                            <span class="text-sm text-gray-400">{{ generatingLabel }}</span>
                            <span v-if="form.type === 'video'" class="font-mono text-violet-400 text-sm">{{ formatTimer(elapsed) }}</span>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Input area -->
            <div class="border-t border-gray-800 px-4 pt-3 pb-4 flex-shrink-0">

                <!-- Toolbar -->
                <div class="flex items-center gap-1.5 mb-3 flex-wrap">
                    <!-- Tipo de conteúdo -->
                    <button
                        v-for="t in genTypes"
                        :key="t.value"
                        type="button"
                        @click="form.type = t.value"
                        :class="form.type === t.value
                            ? 'brand-active'
                            : 'bg-gray-800 text-gray-400 hover:text-white hover:bg-gray-700'"
                        class="flex items-center gap-1.5 px-3 py-1.5 rounded-lg text-xs font-bold uppercase tracking-wider transition-colors"
                    >
                        <component :is="t.icon" class="w-3.5 h-3.5" />
                        {{ t.label }}
                    </button>

                    <div class="w-px h-5 bg-gray-800 mx-0.5"></div>

                    <!-- Plataforma -->
                    <button
                        v-for="p in platforms"
                        :key="p.value"
                        type="button"
                        @click="selectPlatform(p.value)"
                        :class="form.platform === p.value
                            ? 'brand-active'
                            : 'bg-gray-800 text-gray-400 hover:text-white hover:bg-gray-700'"
                        class="px-3 py-1.5 rounded-lg text-xs font-bold uppercase tracking-wider transition-colors"
                    >
                        {{ p.label }}
                    </button>

                    <!-- Dropdowns à direita -->
                    <div class="ml-auto flex items-center gap-2">
                        <select v-model="form.contentType" @change="onContentTypeChange" class="select-sm">
                            <option v-for="ct in currentContentTypes" :key="ct.value" :value="ct.value">{{ ct.label }}</option>
                        </select>

                        <select v-model="resolutionSelect" @change="onResolutionSelectChange" class="select-sm">
                            <option value="1080x1080">1080×1080 (1:1)</option>
                            <option value="1080x1920">1080×1920 (9:16)</option>
                            <option value="1920x1080">1920×1080 (16:9)</option>
                            <option value="1080x1350">1080×1350 (4:5)</option>
                            <option value="1280x720">1280×720 (HD)</option>
                            <option value="custom">✏ Personalizada</option>
                        </select>

                        <select v-if="form.type === 'image' || form.type === 'carousel'" v-model.number="form.quantity" class="select-sm">
                            <option :value="1">1×</option>
                            <option :value="2">2×</option>
                            <option :value="3">3×</option>
                            <option :value="4">4×</option>
                        </select>

                        <select v-if="form.type === 'video'" v-model="form.videoModel" class="select-sm">
                            <option value="veo-2.0-generate-001">Veo 2</option>
                            <option value="veo-3.0-generate-001">Veo 3</option>
                            <option value="veo-3.0-fast-generate-001">Veo 3 Fast</option>
                        </select>

                        <button
                            v-if="products.length"
                            type="button"
                            @click="showProducts = !showProducts"
                            class="select-sm flex items-center gap-1"
                            :class="form.productIds.length ? 'text-violet-300 border-violet-600/50 bg-violet-600/10' : ''"
                        >
                            <Package class="w-3 h-3" />
                            {{ form.productIds.length ? `${form.productIds.length} prod.` : 'Produtos' }}
                        </button>
                    </div>
                </div>

                <!-- Resolução personalizada -->
                <div v-if="resolutionSelect === 'custom'" class="flex items-center gap-2 mb-2">
                    <input v-model="form.resolution" class="input flex-1 text-sm font-mono" placeholder="Ex: 1200x628" />
                    <p v-if="form.resolution && !validResolution" class="text-xs text-red-400 whitespace-nowrap">use LarguraxAltura</p>
                </div>

                <!-- Produtos -->
                <div v-if="showProducts && products.length" class="flex flex-wrap gap-1.5 mb-2">
                    <label v-for="p in products" :key="p.id"
                           class="flex items-center gap-1.5 px-2.5 py-1 bg-gray-800 rounded-lg cursor-pointer hover:bg-gray-700 transition-colors">
                        <input type="checkbox" :value="p.id" v-model="form.productIds" class="accent-violet-500" />
                        <span class="text-xs text-gray-300">{{ p.name }}</span>
                    </label>
                </div>

                <!-- Textarea + botão -->
                <form @submit.prevent="generate" class="relative">
                    <textarea
                        v-model="form.brief"
                        rows="3"
                        class="w-full bg-gray-800/60 border border-gray-700 rounded-xl px-4 py-3 pr-24 text-white placeholder-gray-600 focus:outline-none focus:border-violet-500 transition-colors text-sm resize-none leading-relaxed"
                        :placeholder="chatPlaceholder"
                        @keydown.ctrl.enter.prevent="generate"
                    ></textarea>
                    <button
                        type="submit"
                        :disabled="generating || !form.brief.trim() || !validResolution"
                        class="absolute bottom-3 right-3 flex items-center gap-1.5 px-3 py-1.5 btn-primary disabled:opacity-40 text-sm font-semibold rounded-lg transition-colors"
                    >
                        <Loader2 v-if="generating" class="w-3.5 h-3.5 animate-spin" />
                        <Sparkles v-else class="w-3.5 h-3.5" />
                        Gerar
                    </button>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch, nextTick, onMounted, onUnmounted } from 'vue'
import { useRoute } from 'vue-router'
import { Sparkles, Loader2, Download, Trash2, Copy, Film, Image, Package, MessageSquare, Plus, AlertTriangle, Pencil, Check, X } from 'lucide-vue-next'
import api from '@/services/api'
import { downloadAsset } from '@/utils/download'

const route = useRoute()

// ─── State ───────────────────────────────────────────────────────────────────

const products         = ref([])
const currentGenerations = ref([])
const generating       = ref(false)
const copied           = ref(false)
const elapsed          = ref(0)
const showProducts     = ref(false)
const messagesEl       = ref(null)
const sessions         = ref([])
const currentSessionId = ref(null)
const editingSessionId = ref(null)
const editingTitle     = ref('')
const editInput        = ref(null)
let   timer            = null

const SESSIONS_KEY = 'genhub_gen_sessions'

const form = ref({
    brief:       '',
    platform:    'instagram',
    contentType: 'post',
    type:        'image',
    quantity:    1,
    videoModel:  'veo-2.0-generate-001',
    productIds:  [],
    resolution:  '1080x1080',
})

const resolutionSelect = ref('1080x1080')

// ─── Config ──────────────────────────────────────────────────────────────────

const platforms = [
    { value: 'instagram', label: 'Instagram' },
    { value: 'tiktok',    label: 'TikTok' },
    { value: 'facebook',  label: 'Facebook' },
    { value: 'youtube',   label: 'YouTube' },
]

const genTypes = [
    { value: 'image',    label: 'Fotos',    icon: Image },
    { value: 'video',    label: 'Vídeos',   icon: Film },
    { value: 'carousel', label: 'Carrossel', icon: Image },
    { value: 'text',     label: 'Legenda',  icon: Sparkles },
]

const contentTypesByPlatform = {
    instagram: [
        { value: 'post',        label: 'Post' },
        { value: 'reel',        label: 'Reel' },
        { value: 'story',       label: 'Story' },
        { value: 'carousel',    label: 'Carrossel' },
    ],
    tiktok:   [{ value: 'tiktok_video', label: 'TikTok Video' }],
    facebook: [
        { value: 'post',        label: 'Post' },
        { value: 'story',       label: 'Story' },
        { value: 'carousel',    label: 'Carrossel' },
    ],
    youtube:  [{ value: 'post', label: 'Thumbnail' }],
}

const currentContentTypes = computed(() => contentTypesByPlatform[form.value.platform])

// ─── Resolution ──────────────────────────────────────────────────────────────

const validResolution = computed(() =>
    resolutionSelect.value !== 'custom' || /^\d+x\d+$/.test(form.value.resolution)
)

const activeResolution = computed(() =>
    resolutionSelect.value === 'custom' ? form.value.resolution : resolutionSelect.value
)

function suggestedResolution() {
    if (form.value.platform === 'youtube') return '1280x720'
    return ['reel', 'story', 'tiktok_video'].includes(form.value.contentType) ? '1080x1920' : '1080x1080'
}

function onResolutionSelectChange() {
    if (resolutionSelect.value !== 'custom') {
        form.value.resolution = resolutionSelect.value
    }
}

function selectPlatform(p) {
    form.value.platform = p
    form.value.contentType = currentContentTypes.value[0].value
    const s = suggestedResolution()
    resolutionSelect.value = s
    form.value.resolution  = s
}

function onContentTypeChange() {
    const s = suggestedResolution()
    resolutionSelect.value = s
    form.value.resolution  = s
}

watch(currentContentTypes, (types) => {
    if (!types.find(t => t.value === form.value.contentType)) {
        form.value.contentType = types[0].value
        onContentTypeChange()
    }
})

// ─── Sessions (localStorage) ─────────────────────────────────────────────────

function loadSessions() {
    try { return JSON.parse(localStorage.getItem(SESSIONS_KEY) || '[]') } catch { return [] }
}

function saveSessions(list) {
    localStorage.setItem(SESSIONS_KEY, JSON.stringify(list))
}

function newChat() {
    const id = crypto.randomUUID()
    const session = { id, title: 'Novo Chat', count: 0, createdAt: new Date().toISOString() }
    sessions.value = [session, ...sessions.value]
    saveSessions(sessions.value)
    currentSessionId.value = id
    currentGenerations.value = []
}

async function selectSession(id) {
    currentSessionId.value = id
    const { data } = await api.get('/generate/history', { params: { session_id: id } })
    currentGenerations.value = data.slice().reverse()
    scrollToBottom()
}

function updateSessionMeta(sessionId, brief) {
    sessions.value = sessions.value.map(s => {
        if (s.id !== sessionId) return s
        return {
            ...s,
            title: s.count === 0 ? brief.slice(0, 40) : s.title,
            count: s.count + 1,
        }
    })
    saveSessions(sessions.value)
}

function formatSessionDate(iso) {
    return new Date(iso).toLocaleDateString('pt-BR')
}

async function deleteSession(session) {
    if (!confirm(`Excluir o chat "${session.title}" e todas as gerações?`)) return

    try {
        await api.delete(`/generate/session/${session.id}`)
    } catch {
        // session may have no server-side generations — proceed anyway
    }

    sessions.value = sessions.value.filter(s => s.id !== session.id)
    saveSessions(sessions.value)

    if (currentSessionId.value === session.id) {
        if (sessions.value.length > 0) {
            await selectSession(sessions.value[0].id)
        } else {
            newChat()
        }
    }
}

// ─── Session rename ───────────────────────────────────────────────────────────

function startEdit(session) {
    editingSessionId.value = session.id
    editingTitle.value     = session.title
    nextTick(() => editInput.value?.focus())
}

function cancelEdit() {
    editingSessionId.value = null
    editingTitle.value     = ''
}

async function saveSessionTitle(session) {
    const title = editingTitle.value.trim()
    if (!title) return cancelEdit()

    sessions.value = sessions.value.map(s =>
        s.id === session.id ? { ...s, title } : s
    )
    saveSessions(sessions.value)
    cancelEdit()

    try {
        await api.put(`/generate/session/${session.id}`, { title })
    } catch {
        // falha silenciosa — título já foi salvo no localStorage
    }
}

// ─── Chat helpers ─────────────────────────────────────────────────────────────

const chatPlaceholder = computed(() => ({
    image:    'Descreva a imagem que você quer gerar...',
    video:    'Descreva o vídeo que você quer gerar...',
    carousel: 'Descreva o carrossel que você quer gerar...',
    text:     'Descreva o post para gerar a legenda...',
}[form.value.type]))

const generatingLabel = computed(() =>
    form.value.type === 'video' ? 'Aguardando Veo...' : 'Gerando...'
)

const imageAssets = (gen) => (gen.assets || []).filter(a => a.type === 'image')
const videoAssets = (gen) => (gen.assets || []).filter(a => a.type === 'video')

function parsedCaption(gen) {
    try {
        const j = JSON.parse((gen.result_text || '').replace(/```json|```/g, '').trim())
        return { caption: j.caption ?? gen.result_text, hashtags: j.hashtags ?? [] }
    } catch {
        return { caption: gen.result_text, hashtags: [] }
    }
}

async function scrollToBottom() {
    await nextTick()
    if (messagesEl.value) {
        messagesEl.value.scrollTop = messagesEl.value.scrollHeight
    }
}

// ─── Generate ────────────────────────────────────────────────────────────────

async function generate() {
    if (generating.value || !form.value.brief.trim() || !validResolution.value) return
    if (!currentSessionId.value) newChat()

    generating.value = true
    elapsed.value    = 0

    if (form.value.type === 'video') {
        timer = setInterval(() => elapsed.value++, 1000)
    }

    const brief = form.value.brief
    form.value.brief = ''

    try {
        const { data } = await api.post('/generate', {
            type:         form.value.type,
            platform:     form.value.platform,
            content_type: form.value.contentType,
            brief,
            prompt:       null,
            resolution:   activeResolution.value,
            quantity:     form.value.quantity,
            product_ids:  form.value.productIds,
            video_model:  form.value.videoModel,
            session_id:   currentSessionId.value,
        })

        currentGenerations.value.push(data)
        updateSessionMeta(currentSessionId.value, brief)
        await scrollToBottom()
    } catch (e) {
        alert(e.response?.data?.message || 'Erro na geração.')
        form.value.brief = brief
    } finally {
        generating.value = false
        clearInterval(timer)
        timer = null
    }
}

// ─── Asset / generation actions ───────────────────────────────────────────────

async function deleteAsset(asset, gen) {
    if (!confirm('Excluir este asset?')) return
    await api.delete(`/assets/${asset.id}`)
    gen.assets = gen.assets.filter(a => a.id !== asset.id)
}

async function deleteGeneration(gen) {
    if (!confirm('Excluir esta geração?')) return
    await api.delete(`/generations/${gen.id}`)
    currentGenerations.value = currentGenerations.value.filter(g => g.id !== gen.id)
    // Update count in session
    sessions.value = sessions.value.map(s =>
        s.id === currentSessionId.value ? { ...s, count: Math.max(0, s.count - 1) } : s
    )
    saveSessions(sessions.value)
}

async function copyText(text) {
    await navigator.clipboard.writeText(text)
    copied.value = true
    setTimeout(() => (copied.value = false), 2000)
}

// ─── Formatters ──────────────────────────────────────────────────────────────

function formatTimer(s) {
    const m   = Math.floor(s / 60).toString().padStart(2, '0')
    const sec = (s % 60).toString().padStart(2, '0')
    return `${m}:${sec}`
}

function formatSize(bytes) {
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(0) + ' KB'
    return (bytes / (1024 * 1024)).toFixed(1) + ' MB'
}

// ─── Init ─────────────────────────────────────────────────────────────────────

onMounted(async () => {
    const [prodRes] = await Promise.all([api.get('/products')])
    products.value = prodRes.data.data ?? prodRes.data

    sessions.value = loadSessions()

    const targetSession = route.query.session

    if (targetSession) {
        // Garantir que a sessão existe no localStorage (pode ter sido apagada ou vir de outro dispositivo)
        if (!sessions.value.find(s => s.id === targetSession)) {
            const { data: gens } = await api.get('/generate/history', { params: { session_id: targetSession } })
            if (gens.length > 0) {
                const first = gens[gens.length - 1]
                const session = {
                    id:        targetSession,
                    title:     (first.prompt ?? 'Chat retomado').slice(0, 40),
                    count:     gens.length,
                    createdAt: first.created_at,
                }
                sessions.value = [session, ...sessions.value]
                saveSessions(sessions.value)
            }
        }
        await selectSession(targetSession)
    } else if (sessions.value.length === 0) {
        newChat()
    } else {
        await selectSession(sessions.value[0].id)
    }
})

onUnmounted(() => clearInterval(timer))
</script>

<style scoped>
@reference "tailwindcss";
.input    { @apply w-full bg-gray-800 border border-gray-700 rounded-lg px-3 py-2 text-white placeholder-gray-500 focus:outline-none focus:border-violet-500 transition-colors; }
.select-sm { @apply bg-gray-800 border border-gray-700 text-gray-300 text-xs rounded-lg px-2.5 py-1.5 focus:outline-none focus:border-violet-500 transition-colors cursor-pointer; }
</style>
