<template>
    <div class="flex h-full min-h-0">

        <!-- Conversas -->
        <aside class="hidden md:flex w-64 flex-shrink-0 flex-col" style="background: rgba(255,255,255,0.02); border-right: 1px solid var(--border-subtle)">
            <div class="p-3 pt-4">
                <button type="button" @click="newChat" class="btn-primary w-full inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold text-white">
                    <Plus class="w-4 h-4" /> Nova conversa
                </button>
            </div>
            <p class="tech-label px-5 pt-2 pb-2 !text-[var(--text-muted)]">Conversas</p>

            <ul class="flex-1 overflow-y-auto px-2 pb-3 flex flex-col gap-0.5">
                <li v-for="s in sessions" :key="s.id" class="session group" :class="{ active: currentSessionId === s.id }">
                    <!-- Renomear -->
                    <div v-if="editingSessionId === s.id" class="flex items-center gap-1 p-1.5">
                        <input
                            v-model="editingTitle"
                            @keydown.enter.prevent="saveSessionTitle(s)"
                            @keydown.esc="cancelEdit"
                            v-focus
                            aria-label="Nome da conversa"
                            class="input !py-1.5 !text-sm flex-1 min-w-0"
                            maxlength="60"
                        />
                        <button type="button" @click="saveSessionTitle(s)" class="icon-btn !w-8 !h-8 !text-emerald-400" aria-label="Salvar nome"><Check class="w-4 h-4" /></button>
                        <button type="button" @click="cancelEdit" class="icon-btn !w-8 !h-8" aria-label="Cancelar"><X class="w-4 h-4" /></button>
                    </div>

                    <template v-else>
                        <button type="button" @click="selectSession(s.id)" class="flex-1 min-w-0 flex items-start gap-2.5 px-3 py-2.5 text-left" :aria-current="currentSessionId === s.id ? 'true' : null">
                            <MessageSquare class="w-4 h-4 flex-shrink-0 mt-0.5" :style="{ color: currentSessionId === s.id ? 'color-mix(in srgb, var(--brand) 55%, white)' : 'var(--text-muted)' }" />
                            <span class="min-w-0">
                                <span class="block text-sm truncate" :class="currentSessionId === s.id ? 'text-white' : 'text-gray-300'">{{ s.title }}</span>
                                <span class="block text-xs mt-0.5" style="color: var(--text-muted)">
                                    {{ formatSessionDate(s.createdAt) }}<template v-if="s.count"> · {{ s.count }} {{ s.count === 1 ? 'criação' : 'criações' }}</template>
                                </span>
                            </span>
                        </button>
                        <div class="session-actions">
                            <button v-if="auth.can('generate', 'edit')" type="button" @click="startEdit(s)" class="icon-btn !w-8 !h-8" :aria-label="`Renomear ${s.title}`"><Pencil class="w-3.5 h-3.5" /></button>
                            <button v-if="auth.can('generate', 'delete')" type="button" @click="deleteSession(s)" class="icon-btn !w-8 !h-8 hover:!text-red-400" :aria-label="`Excluir ${s.title}`"><Trash2 class="w-3.5 h-3.5" /></button>
                        </div>
                    </template>
                </li>
            </ul>
        </aside>

        <!-- Chat -->
        <div class="flex-1 flex flex-col min-w-0 min-h-0">

            <header class="flex items-center gap-3 px-6 py-4 flex-shrink-0" style="border-bottom: 1px solid var(--border-subtle)">
                <span class="panel-icon"><Sparkles class="w-4 h-4" /></span>
                <div class="min-w-0">
                    <h1 class="page-hero-title text-base leading-tight">Motor de Criação</h1>
                    <p class="text-xs truncate" style="color: var(--text-muted)">{{ currentSession?.count ? currentSession.title : 'Imagens, vídeos, carrosséis e legendas com IA' }}</p>
                </div>
                <button type="button" @click="newChat" class="md:hidden icon-btn ml-auto" aria-label="Nova conversa"><Plus class="w-4 h-4" /></button>
            </header>

            <!-- Mensagens -->
            <div ref="messagesEl" class="flex-1 overflow-y-auto min-h-0">

                <!-- Boas-vindas -->
                <div v-if="!currentGenerations.length" class="min-h-full flex flex-col items-center justify-center gap-8 px-6 py-10">
                    <div class="flex flex-col items-center text-center gap-3">
                        <span class="panel-icon !w-14 !h-14 !rounded-2xl"><Sparkles class="w-6 h-6" /></span>
                        <h2 class="page-hero-title text-2xl">O que vamos criar hoje?</h2>
                        <p class="text-sm max-w-md" style="color: var(--text-secondary)">Descreva a peça abaixo ou comece por uma ideia. Você pode escolher o formato, a plataforma e os produtos antes de gerar.</p>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3 w-full max-w-2xl">
                        <button v-for="idea in IDEAS" :key="idea.title" type="button" @click="useIdea(idea)" class="action-card text-left !items-start">
                            <span class="panel-icon"><component :is="idea.icon" class="w-4 h-4" /></span>
                            <span class="min-w-0">
                                <span class="block text-sm font-semibold text-white">{{ idea.title }}</span>
                                <span class="block text-xs mt-0.5 line-clamp-2" style="color: var(--text-muted)">{{ idea.brief }}</span>
                            </span>
                        </button>
                    </div>
                </div>

                <!-- Conversa -->
                <div v-else class="max-w-3xl mx-auto px-4 sm:px-6 py-6 flex flex-col gap-8">
                    <div v-for="gen in currentGenerations" :key="gen.id" class="flex flex-col gap-3">
                        <!-- Pedido -->
                        <div class="flex justify-end">
                            <div class="user-bubble">
                                <p class="text-sm text-white leading-relaxed whitespace-pre-wrap">{{ gen.prompt }}</p>
                            </div>
                        </div>

                        <!-- Resposta -->
                        <div class="flex gap-3">
                            <span class="panel-icon flex-shrink-0 mt-0.5"><component :is="typeOf(gen.type).icon" class="w-4 h-4" /></span>
                            <div class="flex-1 min-w-0 flex flex-col gap-3">

                                <div v-if="isRunning(gen)" class="running">
                                    <Loader2 class="w-4 h-4 animate-spin" style="color: color-mix(in srgb, var(--brand) 55%, white)" />
                                    <span class="text-sm text-white">Gerando {{ typeOf(gen.type).label.toLowerCase() }}...</span>
                                    <span class="text-xs tabular-nums ml-auto" style="color: var(--text-muted)">{{ elapsed(gen) }}</span>
                                </div>

                                <div v-else-if="gen.status === 'failed'" class="flex items-start gap-2 rounded-xl p-4 text-sm text-red-300 bg-red-500/10 border border-red-500/30" role="alert">
                                    <AlertCircle class="w-4 h-4 mt-0.5 flex-shrink-0" />
                                    <span>{{ gen.error_message || 'A geração falhou.' }}</span>
                                </div>

                                <!-- Imagens -->
                                <div v-if="imageAssets(gen).length" class="grid gap-2" :class="imageAssets(gen).length === 1 ? 'max-w-sm' : 'grid-cols-2 max-w-lg'">
                                    <div v-for="asset in imageAssets(gen)" :key="asset.id" class="asset group">
                                        <img :src="asset.url" alt="Criativo gerado" loading="lazy" class="w-full h-full object-cover" />
                                        <span v-if="asset.metadata?.slide" class="absolute top-2 left-2 text-[11px] text-white px-2 py-0.5 rounded-full bg-black/70">Slide {{ asset.metadata.slide }}</span>
                                        <div class="asset-actions">
                                            <button type="button" @click="downloadAsset(asset.url)" class="tile-btn" aria-label="Baixar"><Download class="w-4 h-4" /></button>
                                            <button v-if="auth.can('gallery', 'delete')" type="button" @click="deleteAsset(asset, gen)" class="tile-btn hover:!text-red-400" aria-label="Excluir"><Trash2 class="w-4 h-4" /></button>
                                        </div>
                                    </div>
                                </div>

                                <!-- Vídeo -->
                                <div v-for="asset in videoAssets(gen)" :key="asset.id" class="rounded-xl overflow-hidden max-w-md" style="border: 1px solid var(--border-subtle)">
                                    <video :src="asset.url" controls playsinline class="w-full max-h-80 bg-black"></video>
                                    <div class="flex items-center justify-between gap-3 px-3 py-2">
                                        <span class="text-xs" style="color: var(--text-muted)">{{ asset.duration ? asset.duration + 's' : '' }}{{ asset.size ? ' · ' + formatSize(asset.size) : '' }}</span>
                                        <button type="button" @click="downloadAsset(asset.url)" class="btn-secondary !py-1.5 !px-3 !text-xs"><Download class="w-3.5 h-3.5" /> Baixar</button>
                                    </div>
                                </div>

                                <!-- Legenda -->
                                <div v-if="gen.type === 'text' && gen.result_text" class="rounded-2xl rounded-tl-sm px-4 py-3 max-w-xl" style="background: var(--surface-1); border: 1px solid var(--border-subtle)">
                                    <p class="text-sm leading-relaxed whitespace-pre-wrap" style="color: var(--text-secondary)">{{ parsedCaption(gen).caption }}</p>
                                    <div v-if="parsedCaption(gen).hashtags?.length" class="flex flex-wrap gap-1.5 mt-3">
                                        <span v-for="tag in parsedCaption(gen).hashtags" :key="tag" class="text-xs px-2 py-0.5 rounded-full" style="color: color-mix(in srgb, var(--brand) 55%, white); background: color-mix(in srgb, var(--brand) 12%, transparent)">
                                            #{{ tag.replace('#', '') }}
                                        </span>
                                    </div>
                                </div>

                                <!-- Rodapé da resposta -->
                                <div v-if="!isRunning(gen)" class="flex items-center gap-1 -ml-2">
                                    <button v-if="gen.type === 'text' && gen.result_text" type="button" @click="copyCaption(gen)" class="icon-btn !w-8 !h-8" aria-label="Copiar legenda" title="Copiar legenda"><Copy class="w-3.5 h-3.5" /></button>
                                    <button type="button" @click="reuse(gen)" class="icon-btn !w-8 !h-8" aria-label="Reaproveitar pedido" title="Reaproveitar pedido"><RotateCcw class="w-3.5 h-3.5" /></button>
                                    <button v-if="auth.can('generate', 'delete')" type="button" @click="deleteGeneration(gen)" class="icon-btn !w-8 !h-8 hover:!text-red-400" aria-label="Excluir geração" title="Excluir geração"><Trash2 class="w-3.5 h-3.5" /></button>
                                    <span class="text-xs ml-1" style="color: var(--text-muted)">{{ formatTime(gen.created_at) }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Composer -->
            <div class="px-4 sm:px-6 pb-4 pt-2 flex-shrink-0">
                <form @submit.prevent="generate" class="composer max-w-3xl mx-auto">

                    <!-- Tipo -->
                    <div class="flex flex-wrap gap-1.5 px-3 pt-3" role="radiogroup" aria-label="O que gerar">
                        <button v-for="t in GEN_TYPES" :key="t.value" type="button" role="radio" :aria-checked="form.type === t.value" @click="form.type = t.value" class="filter-chip !min-h-8 !py-1 !px-3" :class="{ active: form.type === t.value }">
                            <component :is="t.icon" class="w-3.5 h-3.5" /> {{ t.label }}
                        </button>
                    </div>

                    <!-- Produtos selecionados -->
                    <div v-if="selectedProducts.length" class="flex flex-wrap gap-1.5 px-3 pt-2">
                        <span v-for="p in selectedProducts" :key="p.id" class="inline-flex items-center gap-1.5 pl-1 pr-1 py-0.5 rounded-full text-xs text-white" style="background: color-mix(in srgb, var(--brand) 16%, transparent); border: 1px solid color-mix(in srgb, var(--brand) 40%, transparent)">
                            <img v-if="p.images?.length" :src="p.images[0]" alt="" class="w-5 h-5 rounded-full object-cover bg-white" />
                            <Package v-else class="w-3.5 h-3.5 ml-1" />
                            <span class="max-w-[10rem] truncate">{{ p.name }}</span>
                            <button type="button" @click="toggleProduct(p.id)" class="w-5 h-5 rounded-full inline-flex items-center justify-center hover:bg-white/10" :aria-label="`Remover ${p.name}`"><X class="w-3 h-3" /></button>
                        </span>
                    </div>

                    <textarea
                        ref="briefEl"
                        v-model="form.brief"
                        rows="3"
                        class="w-full bg-transparent px-4 pt-3 pb-1 text-sm text-white leading-relaxed resize-none focus:outline-none placeholder:text-gray-500"
                        :placeholder="PLACEHOLDERS[form.type]"
                        aria-label="Descreva o que gerar"
                        @keydown.enter.exact.prevent="generate"
                    ></textarea>

                    <!-- Produtos (seletor) -->
                    <div v-if="showProducts" class="mx-3 mb-2 rounded-xl p-3 flex flex-col gap-2" style="background: var(--surface-1); border: 1px solid var(--border-subtle)">
                        <label class="relative">
                            <span class="sr-only">Buscar produto</span>
                            <Search class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" style="color: var(--text-muted)" />
                            <input v-model="productSearch" v-focus type="search" placeholder="Buscar produto..." class="input pl-9 !py-2 text-sm" />
                        </label>
                        <div class="flex flex-wrap gap-1.5 max-h-36 overflow-y-auto p-0.5">
                            <button v-for="p in filteredProducts" :key="p.id" type="button" @click="toggleProduct(p.id)" :aria-pressed="form.productIds.includes(p.id)" class="filter-chip !min-h-8 !py-1 !pl-1" :class="{ active: form.productIds.includes(p.id) }">
                                <img v-if="p.images?.length" :src="p.images[0]" alt="" class="w-6 h-6 rounded-full object-cover bg-white" />
                                <Package v-else class="w-4 h-4 mx-1" />
                                {{ p.name }}
                            </button>
                            <p v-if="!filteredProducts.length" class="text-xs py-2" style="color: var(--text-muted)">Nenhum produto encontrado.</p>
                        </div>
                    </div>

                    <!-- Resolução personalizada -->
                    <div v-if="resolutionSelect === 'custom'" class="flex items-center gap-2 px-3 pb-2">
                        <input v-model="form.resolution" class="input !py-2 text-sm font-mono flex-1" placeholder="Ex: 1200x628" aria-label="Resolução personalizada" />
                        <p v-if="form.resolution && !validResolution" class="text-xs text-red-400 whitespace-nowrap">use LarguraxAltura</p>
                    </div>

                    <!-- Ajustes + enviar -->
                    <div class="flex flex-wrap items-center gap-2 px-3 pb-3">
                        <select v-model="form.platform" @change="onPlatformChange" class="select-chip" aria-label="Plataforma">
                            <option v-for="p in PLATFORMS" :key="p.value" :value="p.value">{{ p.label }}</option>
                        </select>
                        <select v-model="form.contentType" @change="applySuggestedResolution" class="select-chip" aria-label="Formato da peça">
                            <option v-for="ct in currentContentTypes" :key="ct.value" :value="ct.value">{{ ct.label }}</option>
                        </select>
                        <select v-model="resolutionSelect" @change="onResolutionSelectChange" class="select-chip" aria-label="Resolução">
                            <option value="1080x1080">1:1 · 1080×1080</option>
                            <option value="1080x1350">4:5 · 1080×1350</option>
                            <option value="1080x1920">9:16 · 1080×1920</option>
                            <option value="1920x1080">16:9 · 1920×1080</option>
                            <option value="1280x720">HD · 1280×720</option>
                            <option value="custom">Personalizada...</option>
                        </select>
                        <select v-if="form.type === 'image' || form.type === 'carousel'" v-model.number="form.quantity" class="select-chip" aria-label="Variações">
                            <option v-for="n in 4" :key="n" :value="n">{{ n }} {{ n === 1 ? 'variação' : 'variações' }}</option>
                        </select>
                        <button v-if="products.length" type="button" @click="showProducts = !showProducts" :aria-expanded="showProducts" class="select-chip !pr-3 inline-flex items-center gap-1.5" :class="{ 'is-set': form.productIds.length }">
                            <Package class="w-3.5 h-3.5" />
                            {{ form.productIds.length ? `${form.productIds.length} produto${form.productIds.length > 1 ? 's' : ''}` : 'Produtos' }}
                        </button>

                        <span class="hidden lg:inline text-[11px] ml-auto" style="color: var(--text-muted)">Enter envia · Shift+Enter quebra linha</span>
                        <button
                            v-if="auth.can('generate', 'create')"
                            type="submit"
                            :disabled="sending || !form.brief.trim() || !validResolution"
                            class="btn-primary inline-flex items-center gap-1.5 px-4 py-2 rounded-xl text-sm font-semibold text-white disabled:opacity-40 ml-auto lg:ml-0"
                        >
                            <Loader2 v-if="sending" class="w-4 h-4 animate-spin" />
                            <ArrowUp v-else class="w-4 h-4" />
                            Gerar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, watch, nextTick, onMounted, onUnmounted } from 'vue'
import { useRoute } from 'vue-router'
import { Sparkles, Loader2, Download, Trash2, Copy, Package, MessageSquare, Plus, Pencil, Check, X, Search, ArrowUp, AlertCircle, RotateCcw, Image as ImageIcon, Clapperboard, GalleryHorizontal, Type, Tag, Croissant } from 'lucide-vue-next'
import api from '@/services/api'
import { downloadAsset } from '@/utils/download'
import { pollGeneration } from '@/utils/pollGeneration'
import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toast'

const auth = useAuthStore()
const toast = useToastStore()
const route = useRoute()

const vFocus = { mounted: el => el.focus() }

// ─── State ───────────────────────────────────────────────────────────────────

const products = ref([])
const currentGenerations = ref([])
const sending = ref(false)        // only while the request is being sent
const showProducts = ref(false)
const productSearch = ref('')
const messagesEl = ref(null)
const briefEl = ref(null)
const sessions = ref([])
const currentSessionId = ref(null)
const editingSessionId = ref(null)
const editingTitle = ref('')

// Kept as-is so existing users keep their saved conversations after the rebrand.
const SESSIONS_KEY = 'genhub_gen_sessions'

const form = ref({
    brief: '',
    platform: 'instagram',
    contentType: 'post',
    type: 'image',
    quantity: 1,
    productIds: [],
    resolution: '1080x1080',
})
const resolutionSelect = ref('1080x1080')

// ─── Config ──────────────────────────────────────────────────────────────────

const PLATFORMS = [
    { value: 'instagram', label: 'Instagram' },
    { value: 'facebook', label: 'Facebook' },
    { value: 'tiktok', label: 'TikTok' },
    { value: 'youtube', label: 'YouTube' },
]

const GEN_TYPES = [
    { value: 'image', label: 'Imagem', icon: ImageIcon },
    { value: 'carousel', label: 'Carrossel', icon: GalleryHorizontal },
    { value: 'video', label: 'Vídeo', icon: Clapperboard },
    { value: 'text', label: 'Legenda', icon: Type },
]
const typeOf = (value) => GEN_TYPES.find(t => t.value === value) ?? { label: value, icon: Sparkles }

const PLACEHOLDERS = {
    image: 'Descreva a imagem. Ex: oferta de picanha a R$ 59,90 o kg, clima de churrasco...',
    carousel: 'Descreva o carrossel. Ex: 5 ofertas do hortifruti, um produto por slide...',
    video: 'Descreva o vídeo. Ex: pão francês saindo do forno, close no pão crocante...',
    text: 'Descreva o post para gerar a legenda. Ex: promoção de bebidas no fim de semana...',
}

const IDEAS = [
    { type: 'image', icon: Tag, title: 'Oferta da semana', brief: 'Arte de oferta para picanha a R$ 59,90 o kg, fundo de churrasco, preço em grande destaque, clima de fim de semana' },
    { type: 'carousel', icon: GalleryHorizontal, title: 'Encarte em carrossel', brief: 'Carrossel com 5 ofertas do hortifruti, um produto por slide, último slide com chamada para visitar a loja' },
    { type: 'video', icon: Croissant, title: 'Reel da padaria', brief: 'Vídeo curto do pão francês saindo do forno, close no pão crocante, clima de café da manhã' },
    { type: 'text', icon: Type, title: 'Legenda de promoção', brief: 'Legenda para post de promoção de bebidas no fim de semana, tom animado, com chamada para a loja' },
]

const contentTypesByPlatform = {
    instagram: [
        { value: 'post', label: 'Post' },
        { value: 'reel', label: 'Reel' },
        { value: 'story', label: 'Story' },
        { value: 'carousel', label: 'Carrossel' },
    ],
    tiktok: [{ value: 'tiktok_video', label: 'Vídeo TikTok' }],
    facebook: [
        { value: 'post', label: 'Post' },
        { value: 'story', label: 'Story' },
        { value: 'carousel', label: 'Carrossel' },
    ],
    youtube: [{ value: 'post', label: 'Thumbnail' }],
}
const currentContentTypes = computed(() => contentTypesByPlatform[form.value.platform])

// What each generation type naturally maps to, so picking "Vídeo" doesn't leave "Post 1:1" selected.
const PREFERRED_CONTENT = { image: ['post'], text: ['post'], carousel: ['carousel', 'post'], video: ['reel', 'tiktok_video', 'story', 'post'] }

// ─── Resolution / format ─────────────────────────────────────────────────────

const validResolution = computed(() => resolutionSelect.value !== 'custom' || /^\d+x\d+$/.test(form.value.resolution))
const activeResolution = computed(() => resolutionSelect.value === 'custom' ? form.value.resolution : resolutionSelect.value)

function applySuggestedResolution() {
    const s = form.value.platform === 'youtube' ? '1280x720'
        : ['reel', 'story', 'tiktok_video'].includes(form.value.contentType) ? '1080x1920' : '1080x1080'
    resolutionSelect.value = s
    form.value.resolution = s
}

function onResolutionSelectChange() {
    if (resolutionSelect.value !== 'custom') form.value.resolution = resolutionSelect.value
}

function pickContentTypeForType() {
    const available = currentContentTypes.value.map(c => c.value)
    const preferred = PREFERRED_CONTENT[form.value.type].find(v => available.includes(v)) ?? available[0]
    if (preferred !== form.value.contentType) {
        form.value.contentType = preferred
        applySuggestedResolution()
    }
}

function onPlatformChange() {
    form.value.contentType = currentContentTypes.value[0].value
    pickContentTypeForType()
    applySuggestedResolution()
}

watch(() => form.value.type, pickContentTypeForType)

// ─── Products ────────────────────────────────────────────────────────────────

const filteredProducts = computed(() => {
    const q = productSearch.value.trim().toLowerCase()
    return q ? products.value.filter(p => `${p.name} ${p.category ?? ''}`.toLowerCase().includes(q)) : products.value
})
const selectedProducts = computed(() => products.value.filter(p => form.value.productIds.includes(p.id)))

function toggleProduct(id) {
    const ids = form.value.productIds
    const idx = ids.indexOf(id)
    idx > -1 ? ids.splice(idx, 1) : ids.push(id)
}

// ─── Sessions (localStorage) ─────────────────────────────────────────────────

const currentSession = computed(() => sessions.value.find(s => s.id === currentSessionId.value))

function loadSessions() {
    try { return JSON.parse(localStorage.getItem(SESSIONS_KEY) || '[]') } catch { return [] }
}

function saveSessions(list) {
    localStorage.setItem(SESSIONS_KEY, JSON.stringify(list))
}

function newChat() {
    // Reuse an untouched conversation instead of piling up empty ones.
    const empty = sessions.value.find(s => !s.count)
    if (empty) {
        currentSessionId.value = empty.id
        currentGenerations.value = []
        return
    }
    const session = { id: crypto.randomUUID(), title: 'Nova conversa', count: 0, createdAt: new Date().toISOString() }
    sessions.value = [session, ...sessions.value]
    saveSessions(sessions.value)
    currentSessionId.value = session.id
    currentGenerations.value = []
}

async function selectSession(id) {
    currentSessionId.value = id
    const { data } = await api.get('/generate/history', { params: { session_id: id } })
    currentGenerations.value = data.slice().reverse()
    currentGenerations.value.filter(isRunning).forEach(g => track(g.id))
    scrollToBottom()
}

function updateSessionMeta(sessionId, brief) {
    sessions.value = sessions.value.map(s => s.id !== sessionId ? s : {
        ...s,
        title: s.count === 0 ? brief.slice(0, 40) : s.title,
        count: s.count + 1,
    })
    saveSessions(sessions.value)
}

function formatSessionDate(iso) {
    return new Date(iso).toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit' })
}

async function deleteSession(session) {
    if (!confirm(`Excluir a conversa "${session.title}" e todas as criações dela?`)) return
    try {
        await api.delete(`/generate/session/${session.id}`)
    } catch {
        // the session may have no server-side generations: proceed anyway
    }
    sessions.value = sessions.value.filter(s => s.id !== session.id)
    saveSessions(sessions.value)

    if (currentSessionId.value === session.id) {
        sessions.value.length ? await selectSession(sessions.value[0].id) : newChat()
    }
}

function startEdit(session) {
    editingSessionId.value = session.id
    editingTitle.value = session.title
}

function cancelEdit() {
    editingSessionId.value = null
    editingTitle.value = ''
}

async function saveSessionTitle(session) {
    const title = editingTitle.value.trim()
    if (!title) return cancelEdit()
    sessions.value = sessions.value.map(s => s.id === session.id ? { ...s, title } : s)
    saveSessions(sessions.value)
    cancelEdit()
    try {
        await api.put(`/generate/session/${session.id}`, { title })
    } catch {
        // already saved locally
    }
}

// ─── Chat helpers ─────────────────────────────────────────────────────────────

const imageAssets = (gen) => (gen.assets || []).filter(a => a.type === 'image')
const videoAssets = (gen) => (gen.assets || []).filter(a => a.type === 'video')
const isRunning = (gen) => ['pending', 'processing'].includes(gen.status)

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
    if (messagesEl.value) messagesEl.value.scrollTop = messagesEl.value.scrollHeight
}

function useIdea(idea) {
    form.value.type = idea.type
    form.value.brief = idea.brief
    nextTick(() => briefEl.value?.focus())
}

function reuse(gen) {
    form.value.type = gen.type
    form.value.brief = gen.prompt ?? ''
    nextTick(() => briefEl.value?.focus())
}

// A 1s clock only while something is generating, for the elapsed timers.
const now = ref(Date.now())
let clock = null
watch(() => currentGenerations.value.some(isRunning), (running) => {
    clearInterval(clock)
    clock = running ? setInterval(() => (now.value = Date.now()), 1000) : null
})
function elapsed(gen) {
    const s = Math.max(0, Math.floor((now.value - new Date(gen.created_at)) / 1000))
    return `${String(Math.floor(s / 60)).padStart(2, '0')}:${String(s % 60).padStart(2, '0')}`
}

// ─── Generate ────────────────────────────────────────────────────────────────

// The backend queues each job: send it, show it as running and poll in the background,
// so the user can keep asking for more meanwhile.
async function generate() {
    if (sending.value || !form.value.brief.trim() || !validResolution.value) return
    if (!currentSessionId.value) newChat()

    const brief = form.value.brief
    const sessionId = currentSessionId.value
    form.value.brief = ''
    showProducts.value = false
    sending.value = true

    let data
    try {
        ({ data } = await api.post('/generate', {
            type: form.value.type,
            platform: form.value.platform,
            content_type: form.value.contentType,
            brief,
            prompt: null,
            resolution: activeResolution.value,
            quantity: form.value.quantity,
            product_ids: form.value.productIds,
            session_id: sessionId,
        }))
    } catch {
        if (!form.value.brief) form.value.brief = brief // give the text back; error already toasted
        return
    } finally {
        sending.value = false
    }

    if (currentSessionId.value === sessionId) currentGenerations.value.push(data)
    updateSessionMeta(sessionId, brief)
    scrollToBottom()
    track(data.id)
}

const tracking = new Set()
async function track(id) {
    if (tracking.has(id)) return
    tracking.add(id)
    try {
        const final = await pollGeneration(id)
        const idx = currentGenerations.value.findIndex(g => g.id === id)
        if (idx !== -1) {
            currentGenerations.value[idx] = final
            scrollToBottom()
        }
    } catch (e) {
        if (!e.response) toast.push(e.message || 'Erro na geração.', 'error')
    } finally {
        tracking.delete(id)
    }
}

// ─── Asset / generation actions ──────────────────────────────────────────────

async function deleteAsset(asset, gen) {
    if (!confirm('Excluir este criativo?')) return
    await api.delete(`/assets/${asset.id}`)
    gen.assets = gen.assets.filter(a => a.id !== asset.id)
}

async function deleteGeneration(gen) {
    if (!confirm('Excluir esta geração?')) return
    await api.delete(`/generations/${gen.id}`)
    currentGenerations.value = currentGenerations.value.filter(g => g.id !== gen.id)
    sessions.value = sessions.value.map(s => s.id === currentSessionId.value ? { ...s, count: Math.max(0, s.count - 1) } : s)
    saveSessions(sessions.value)
}

async function copyCaption(gen) {
    const { caption, hashtags } = parsedCaption(gen)
    const tags = hashtags?.length ? '\n\n' + hashtags.map(t => `#${t.replace('#', '')}`).join(' ') : ''
    await navigator.clipboard.writeText(caption + tags)
    toast.push('Legenda copiada.', 'success')
}

// ─── Formatters ──────────────────────────────────────────────────────────────

const formatTime = (d) => d ? new Date(d).toLocaleString('pt-BR', { day: '2-digit', month: '2-digit', hour: '2-digit', minute: '2-digit' }) : ''

function formatSize(bytes) {
    if (bytes < 1024 * 1024) return (bytes / 1024).toFixed(0) + ' KB'
    return (bytes / (1024 * 1024)).toFixed(1) + ' MB'
}

// ─── Init ────────────────────────────────────────────────────────────────────

onMounted(async () => {
    if (auth.can('products', 'view')) {
        api.get('/products', { params: { per_page: 100 } })
            .then(({ data }) => { products.value = data.data ?? data })
            .catch(() => {})
    }

    sessions.value = loadSessions()
    const targetSession = route.query.session

    if (targetSession) {
        // The session may be missing locally (cleared storage or another device): rebuild it from the server.
        if (!sessions.value.find(s => s.id === targetSession)) {
            const { data: gens } = await api.get('/generate/history', { params: { session_id: targetSession } })
            if (gens.length) {
                const first = gens[gens.length - 1]
                sessions.value = [{ id: targetSession, title: (first.prompt ?? 'Conversa retomada').slice(0, 40), count: gens.length, createdAt: first.created_at }, ...sessions.value]
                saveSessions(sessions.value)
            }
        }
        await selectSession(targetSession)
    } else if (!sessions.value.length) {
        newChat()
    } else {
        await selectSession(sessions.value[0].id)
    }
})

onUnmounted(() => clearInterval(clock))
</script>

<style scoped>
.session {
    display: flex;
    align-items: flex-start;
    border-radius: 12px;
    border: 1px solid transparent;
    transition: background-color 0.15s, border-color 0.15s;
}
.session:hover { background: rgba(255, 255, 255, 0.04); }
.session.active {
    background: color-mix(in srgb, var(--brand) 12%, transparent);
    border-color: color-mix(in srgb, var(--brand) 30%, transparent);
}
.session-actions {
    display: flex;
    gap: 2px;
    padding: 6px 6px 0 0;
    opacity: 0;
    transition: opacity 0.15s;
}
.session:hover .session-actions,
.session:focus-within .session-actions { opacity: 1; }
@media (hover: none) { .session-actions { opacity: 1; } }

.user-bubble {
    max-width: min(32rem, 85%);
    padding: 12px 16px;
    border-radius: 18px 18px 4px 18px;
    background: color-mix(in srgb, var(--brand) 16%, transparent);
    border: 1px solid color-mix(in srgb, var(--brand) 28%, transparent);
}

.running {
    display: flex;
    align-items: center;
    gap: 10px;
    max-width: 24rem;
    padding: 14px 16px;
    border-radius: 14px;
    background: var(--surface-1);
    border: 1px dashed var(--border-soft);
}

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

.composer {
    border-radius: 20px;
    background: rgba(12, 10, 22, 0.9);
    border: 1px solid var(--border-soft);
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.35);
    transition: border-color 0.15s, box-shadow 0.15s;
}
.composer:focus-within {
    border-color: color-mix(in srgb, var(--brand) 50%, transparent);
    box-shadow: 0 12px 40px rgba(0, 0, 0, 0.35), 0 0 0 3px color-mix(in srgb, var(--brand) 18%, transparent);
}

.select-chip {
    appearance: none;
    -webkit-appearance: none;
    min-height: 32px;
    padding: 4px 28px 4px 12px;
    border-radius: 9999px;
    font-size: 0.75rem;
    color: var(--text-secondary);
    background-color: var(--surface-1);
    border: 1px solid var(--border-soft);
    background-image: url("data:image/svg+xml,%3Csvg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 24 24' fill='none' stroke='%239ca3af' stroke-width='2' stroke-linecap='round' stroke-linejoin='round'%3E%3Cpolyline points='6 9 12 15 18 9'%3E%3C/polyline%3E%3C/svg%3E");
    background-repeat: no-repeat;
    background-position: right 10px center;
    background-size: 12px;
    cursor: pointer;
    transition: color 0.15s, border-color 0.15s;
}
button.select-chip { background-image: none; }
.select-chip:hover { color: #fff; border-color: rgba(255, 255, 255, 0.22); }
.select-chip:focus-visible { outline: 2px solid color-mix(in srgb, var(--brand) 60%, white); outline-offset: 2px; }
.select-chip option { background: #14121d; color: #fff; }
.select-chip.is-set {
    color: #fff;
    border-color: color-mix(in srgb, var(--brand) 55%, transparent);
    background-color: color-mix(in srgb, var(--brand) 16%, transparent);
}
</style>
