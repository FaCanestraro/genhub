<template>
    <div class="page flex flex-col gap-6">

        <!-- Header -->
        <header class="flex flex-wrap items-end justify-between gap-4">
            <div class="flex flex-col gap-1.5">
                <p class="tech-label">Criação</p>
                <h1 class="page-hero-title text-3xl leading-tight">Galeria de modelos</h1>
                <p class="text-sm" style="color: var(--text-secondary)">Escolha um modelo, combine com um produto e gere sua arte.</p>
            </div>
            <div class="flex flex-wrap items-center gap-3 w-full sm:w-auto">
            <RouterLink v-if="pendingCount" to="/history" class="filter-chip active" aria-live="polite">
                <Loader2 class="w-3.5 h-3.5 animate-spin" />
                Gerando {{ pendingCount }} {{ pendingCount > 1 ? 'artes' : 'arte' }}...
            </RouterLink>
            <label class="relative w-full sm:w-72">
                <span class="sr-only">Buscar modelos</span>
                <Search class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" style="color: var(--text-muted)" />
                <input v-model="search" type="search" placeholder="Buscar modelo..." class="input pl-9" />
            </label>
            </div>
        </header>

        <!-- Filters -->
        <div class="flex flex-wrap gap-2" role="group" aria-label="Filtrar modelos">
            <button
                v-for="f in filters"
                :key="f.key"
                type="button"
                @click="filter = f.key"
                :aria-pressed="filter === f.key"
                class="filter-chip"
                :class="{ active: filter === f.key }"
            >
                <component :is="f.icon" class="w-3.5 h-3.5" />
                {{ f.label }}
                <span class="tabular-nums opacity-60">{{ f.count }}</span>
            </button>
        </div>

        <!-- Loading -->
        <div v-if="loading" class="masonry">
            <div v-for="i in 10" :key="i" class="shimmer rounded-2xl aspect-[4/5]"></div>
        </div>

        <!-- Empty -->
        <EmptyState
            v-else-if="!templates.length"
            :icon="LayoutTemplate"
            title="Nenhum modelo disponível ainda"
            text="Os modelos são publicados pela equipe CREATIQ e aparecem aqui assim que liberados."
            class="panel"
        />
        <EmptyState
            v-else-if="!filtered.length"
            :icon="filter === 'favorites' ? Star : Search"
            :title="filter === 'favorites' && !search ? 'Nenhum favorito ainda' : 'Nenhum modelo encontrado'"
            :text="filter === 'favorites' && !search ? 'Toque na estrela de um modelo para guardá-lo aqui.' : 'Tente outro termo ou filtro.'"
            class="panel"
        />

        <!-- Grid -->
        <template v-else>
            <div class="masonry">
                <article
                    v-for="t in visible"
                    :key="t.id"
                    class="masonry-card group"
                >
                    <button type="button" class="block w-full h-full text-left" @click="openDetail(t)" :aria-label="`Abrir modelo ${t.title}`">
                        <video
                            v-if="t.type === 'video' && t.preview_url"
                            :src="t.preview_url"
                            class="card-media"
                            v-play-in-view muted loop playsinline preload="metadata"
                        ></video>
                        <img v-else-if="t.preview_url" :src="t.preview_url" :alt="t.title" loading="lazy" class="card-media" />
                        <span v-else class="card-placeholder">
                            <component :is="t.type === 'video' ? Film : ImageIcon" class="w-10 h-10" style="color: var(--text-muted)" />
                        </span>

                        <span class="card-overlay">
                            <span class="type-pill">
                                <Play v-if="t.type === 'video'" class="w-2.5 h-2.5" />
                                {{ t.type === 'video' ? 'Vídeo' : 'Imagem' }}
                            </span>
                            <span class="block mt-1.5 text-[13px] font-semibold text-white leading-snug">{{ t.title }}</span>
                        </span>
                    </button>

                    <button
                        type="button"
                        @click="toggleFavorite(t)"
                        class="favorite-btn"
                        :class="{ 'is-favorite': t.is_favorite }"
                        :aria-label="t.is_favorite ? `Remover ${t.title} dos favoritos` : `Favoritar ${t.title}`"
                        :aria-pressed="!!t.is_favorite"
                    >
                        <Star class="w-3.5 h-3.5" :fill="t.is_favorite ? 'currentColor' : 'none'" />
                    </button>
                </article>
            </div>

            <!-- Reaching this sentinel loads the next page -->
            <div v-if="filtered.length > visible.length" :key="limit" v-load-more class="h-px" aria-hidden="true"></div>
        </template>

        <!-- Detail modal -->
        <Teleport to="body">
            <Transition name="detail">
                <div v-if="detail" class="detail-backdrop" role="dialog" aria-modal="true" :aria-label="`Modelo ${detail.title}`" @click.self="closeDetail">
                    <div class="detail-card">

                        <!-- Preview -->
                        <div class="detail-media-area">
                            <video v-if="detail.type === 'video' && detail.preview_url" :src="detail.preview_url" class="detail-media" v-autoplay muted loop playsinline controls @loadedmetadata="matchFormat"></video>
                            <img v-else-if="detail.preview_url" :src="detail.preview_url" :alt="detail.title" class="detail-media" @load="matchFormat" />
                            <div v-else class="flex flex-col items-center gap-3" style="color: var(--text-muted)">
                                <component :is="detail.type === 'video' ? Film : ImageIcon" class="w-14 h-14" />
                                <p class="text-sm">Sem prévia</p>
                            </div>
                        </div>

                        <!-- Panel -->
                        <div class="detail-panel">
                            <header class="detail-head">
                                <div class="min-w-0">
                                    <p class="tech-label mb-1.5">Modelo · {{ detail.type === 'video' ? 'Vídeo' : 'Imagem' }}</p>
                                    <h2 class="page-hero-title text-xl leading-tight">{{ detail.title }}</h2>
                                </div>
                                <div class="flex items-center gap-1 flex-shrink-0 -mr-2 -mt-1">
                                    <button
                                        type="button"
                                        @click="toggleFavorite(detail)"
                                        class="icon-btn"
                                        :class="{ '!text-amber-400': detail.is_favorite }"
                                        :aria-label="detail.is_favorite ? 'Remover dos favoritos' : 'Favoritar'"
                                        :aria-pressed="!!detail.is_favorite"
                                    >
                                        <Star class="w-4 h-4" :fill="detail.is_favorite ? 'currentColor' : 'none'" />
                                    </button>
                                    <button type="button" @click="closeDetail" class="icon-btn" aria-label="Fechar">
                                        <X class="w-4 h-4" />
                                    </button>
                                </div>
                            </header>

                            <div class="detail-body">
                                <!-- Produto -->
                                <section v-if="auth.can('products', 'view')" class="flex flex-col gap-3">
                                    <div class="flex items-center justify-between gap-3">
                                        <h3 class="tech-label !text-[var(--text-secondary)]">Produto</h3>
                                        <span class="text-[11px]" style="color: var(--text-muted)">opcional</span>
                                    </div>
                                    <label v-if="products.length > 6" class="relative">
                                        <span class="sr-only">Buscar produto</span>
                                        <Search class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" style="color: var(--text-muted)" />
                                        <input v-model="productSearch" type="search" placeholder="Buscar produto..." class="input pl-9 text-sm" />
                                    </label>
                                    <div class="product-grid" role="radiogroup" aria-label="Produto">
                                        <button type="button" role="radio" :aria-checked="!selectedProductId" @click="selectedProductId = ''" class="product-option" :class="{ active: !selectedProductId }">
                                            <span class="product-thumb"><PackageX class="w-5 h-5" style="color: var(--text-muted)" /></span>
                                            <span class="product-name">Sem produto</span>
                                        </button>
                                        <button
                                            v-for="p in filteredProducts"
                                            :key="p.id"
                                            type="button"
                                            role="radio"
                                            :aria-checked="selectedProductId === p.id"
                                            @click="selectedProductId = p.id"
                                            class="product-option"
                                            :class="{ active: selectedProductId === p.id }"
                                        >
                                            <span class="product-thumb">
                                                <img v-if="p.images?.length" :src="p.images[0]" alt="" loading="lazy" />
                                                <Package v-else class="w-5 h-5" style="color: var(--text-muted)" />
                                            </span>
                                            <span class="product-name">{{ p.name }}</span>
                                            <span v-if="p.price" class="product-price">R$ {{ numberToCurrency(p.price) }}</span>
                                        </button>
                                    </div>
                                    <p v-if="!products.length" class="text-xs" style="color: var(--text-muted)">
                                        Nenhum produto cadastrado ainda.
                                        <RouterLink to="/products" class="underline hover:text-white">Cadastrar produtos</RouterLink>
                                    </p>
                                </section>

                                <!-- Formato -->
                                <section class="flex flex-col gap-3">
                                    <h3 class="tech-label !text-[var(--text-secondary)]">Formato</h3>
                                    <div class="grid grid-cols-2 gap-2" role="radiogroup" aria-label="Formato">
                                        <button
                                            v-for="r in formats"
                                            :key="r.value"
                                            type="button"
                                            role="radio"
                                            :aria-checked="selectedResolution === r.value"
                                            @click="pickFormat(r.value)"
                                            class="format-option"
                                            :class="{ active: selectedResolution === r.value }"
                                        >
                                            <span class="format-shape" :style="{ aspectRatio: r.ratio }"></span>
                                            <span class="min-w-0">
                                                <span class="block text-xs font-medium text-white leading-tight">{{ r.label }}</span>
                                                <span class="block text-[11px] tabular-nums mt-0.5" style="color: var(--text-muted)">{{ r.value.replace('x', '×') }}</span>
                                            </span>
                                        </button>
                                    </div>
                                </section>

                                <!-- Prompt (referência) -->
                                <details v-if="detail.prompt" class="prompt-details">
                                    <summary class="flex items-center justify-between gap-3">
                                        <span class="tech-label !text-[var(--text-secondary)]">Prompt do modelo</span>
                                        <ChevronDown class="chevron w-4 h-4" style="color: var(--text-muted)" />
                                    </summary>
                                    <p class="text-sm leading-relaxed mt-3" style="color: var(--text-secondary)">{{ detail.prompt }}</p>
                                    <button type="button" @click="copyPrompt" class="mt-2 text-xs inline-flex items-center gap-1 transition-colors" :class="copied ? 'text-emerald-400' : 'text-gray-400 hover:text-white'">
                                        <component :is="copied ? Check : Copy" class="w-3.5 h-3.5" />
                                        {{ copied ? 'Copiado' : 'Copiar prompt' }}
                                    </button>
                                </details>
                            </div>

                            <footer class="detail-footer">
                                <p class="text-xs truncate" style="color: var(--text-muted)">{{ summary }}</p>
                                <button v-if="auth.can('generate', 'create')" type="button" @click="generate" :disabled="generating" class="btn-generate">
                                    <Loader2 v-if="generating" class="w-4 h-4 animate-spin" />
                                    <Sparkles v-else class="w-4 h-4" />
                                    {{ generating ? 'Enviando...' : `Gerar ${detail.type === 'video' ? 'vídeo' : 'imagem'}` }}
                                </button>
                                <p v-else class="text-xs text-center" style="color: var(--text-muted)">Você não tem permissão para gerar conteúdo.</p>
                            </footer>
                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- Result modal -->
        <Teleport to="body">
            <div v-if="result" class="fixed inset-0 dialog-backdrop flex items-center justify-center z-[60] p-4" role="dialog" aria-modal="true" aria-label="Arte gerada" @click.self="result = null">
                <div class="glass-dialog rounded-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto p-6 flex flex-col gap-4">
                    <div class="flex items-center justify-between">
                        <div>
                            <p class="tech-label mb-1">Pronto</p>
                            <h2 class="page-hero-title text-lg">Sua arte foi gerada</h2>
                        </div>
                        <button type="button" @click="result = null" class="icon-btn" aria-label="Fechar"><X class="w-4 h-4" /></button>
                    </div>
                    <div v-for="(url, i) in result.urls" :key="i" class="relative rounded-xl overflow-hidden">
                        <video v-if="result.type === 'video'" :src="url" controls class="w-full rounded-xl"></video>
                        <img v-else :src="url" alt="Arte gerada" class="w-full rounded-xl" />
                    </div>
                    <div class="flex flex-wrap gap-2">
                        <button type="button" @click="result.urls.forEach(u => downloadAsset(u))" class="btn-primary flex-1 inline-flex items-center justify-center gap-2 px-4 py-2.5 rounded-xl text-sm font-semibold text-white">
                            <Download class="w-4 h-4" /> Baixar
                        </button>
                        <RouterLink v-if="auth.can('history', 'view')" to="/history" class="action-card !py-2.5 !px-4 justify-center text-sm font-medium text-white">
                            <History class="w-4 h-4" /> Ver no histórico
                        </RouterLink>
                    </div>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<script setup>
import { ref, computed, onMounted, onUnmounted, watch } from 'vue'
import { RouterLink } from 'vue-router'
import { Film, Image as ImageIcon, X, Sparkles, Loader2, Download, LayoutTemplate, Star, Search, Play, Copy, Check, History, LayoutGrid, Package, PackageX, ChevronDown } from 'lucide-vue-next'
import api from '@/services/api'
import { assetUrl } from '@/utils/assetUrl'
import { downloadAsset } from '@/utils/download'
import { pollGeneration } from '@/utils/pollGeneration'
import { useAuthStore } from '@/stores/auth'
import { useToastStore } from '@/stores/toast'
import EmptyState from '@/components/ui/EmptyState.vue'
import { numberToCurrency } from '@/utils/mask'

const auth = useAuthStore()
const toast = useToastStore()

const PAGE_SIZE = 20
const RESOLUTIONS = [
    { value: '1080x1080', label: 'Feed quadrado', ratio: '1 / 1', video: true },
    { value: '1080x1350', label: 'Feed retrato', ratio: '4 / 5', video: true },
    { value: '1080x1920', label: 'Stories / Reels', ratio: '9 / 16', video: true },
    { value: '1920x1080', label: 'Paisagem', ratio: '16 / 9', video: true },
    { value: '1200x628', label: 'Post com link', ratio: '1.91 / 1', video: false },
]
const TYPE_LABELS = { image: 'Imagem', video: 'Vídeo' }

const templates = ref([])
const products = ref([])
const loading = ref(true)
const search = ref('')
const filter = ref('all')
const limit = ref(PAGE_SIZE)

const detail = ref(null)
const copied = ref(false)
const productSearch = ref('')
const selectedProductId = ref('')
const selectedResolution = ref('1080x1080')
const formatTouched = ref(false)
const generating = ref(false)   // only while the request is being sent
const pendingCount = ref(0)     // generations running in the background queue
const result = ref(null)
let mounted = true

// ── Filtering ───────────────────────────────────────────────────
const matchesFilter = (t, key) =>
    key === 'all' || (key === 'favorites' ? t.is_favorite : t.type === key)

const filters = computed(() => [
    { key: 'all', label: 'Todos', icon: LayoutGrid },
    { key: 'image', label: 'Imagens', icon: ImageIcon },
    { key: 'video', label: 'Vídeos', icon: Film },
    { key: 'favorites', label: 'Favoritos', icon: Star },
].map(f => ({ ...f, count: templates.value.filter(t => matchesFilter(t, f.key)).length })))

const filtered = computed(() => {
    const q = search.value.trim().toLowerCase()
    return templates.value.filter(t =>
        matchesFilter(t, filter.value) &&
        (!q || t.title?.toLowerCase().includes(q) || t.prompt?.toLowerCase().includes(q)))
})
const visible = computed(() => filtered.value.slice(0, limit.value))

watch([search, filter], () => { limit.value = PAGE_SIZE })

// ── Videos ──────────────────────────────────────────────────────
// `muted` must be set on the element itself before play(), otherwise browsers block autoplay.
function playMuted(el) {
    el.muted = true
    el.play().catch(() => {})
}
// Grid previews play only while on screen, so dozens of videos never decode at once.
const inView = new IntersectionObserver(entries => {
    for (const { target, isIntersecting } of entries) isIntersecting ? playMuted(target) : target.pause()
}, { threshold: 0.25 })
const vPlayInView = { mounted: el => inView.observe(el), unmounted: el => inView.unobserve(el) }
const vAutoplay = { mounted: playMuted }

// Infinite scroll: grow the visible slice when the sentinel nears the viewport.
const loadMore = new IntersectionObserver(entries => {
    if (entries.some(e => e.isIntersecting)) limit.value += PAGE_SIZE
}, { rootMargin: '600px' })
const vLoadMore = { mounted: el => loadMore.observe(el), unmounted: el => loadMore.unobserve(el) }

// ── Data ────────────────────────────────────────────────────────
async function fetchData() {
    const [tRes, pRes] = await Promise.allSettled([
        api.get('/templates'),
        auth.can('products', 'view') ? api.get('/products', { params: { per_page: 100 } }) : null,
    ])
    if (tRes.value) templates.value = tRes.value.data
    if (pRes.value) products.value = pRes.value.data.data ?? pRes.value.data
    loading.value = false
}

async function toggleFavorite(t) {
    const { data } = await api.patch(`/templates/${t.id}/favorite`, {})
    t.is_favorite = data.is_favorite
    // Keep favorites first without refetching (sort is stable).
    templates.value = [...templates.value].sort((a, b) => (b.is_favorite ? 1 : 0) - (a.is_favorite ? 1 : 0))
}

// ── Detail ──────────────────────────────────────────────────────
const formats = computed(() => detail.value?.type === 'video' ? RESOLUTIONS.filter(r => r.video) : RESOLUTIONS)
const filteredProducts = computed(() => {
    const q = productSearch.value.trim().toLowerCase()
    return q ? products.value.filter(p => `${p.name} ${p.category ?? ''}`.toLowerCase().includes(q)) : products.value
})
const selectedProduct = computed(() => products.value.find(p => p.id === selectedProductId.value))
const summary = computed(() => [
    TYPE_LABELS[detail.value?.type],
    formats.value.find(r => r.value === selectedResolution.value)?.label,
    selectedProduct.value?.name ?? 'sem produto',
].filter(Boolean).join(' · '))

function openDetail(t) {
    detail.value = t
    copied.value = false
    productSearch.value = ''
    selectedProductId.value = ''
    formatTouched.value = false
    selectedResolution.value = t.type === 'video' ? '1080x1920' : '1080x1080'
}

function pickFormat(value) {
    selectedResolution.value = value
    formatTouched.value = true
}

// Preselect the format closest to the template preview's own proportions.
const ratioOf = (value) => { const [w, h] = value.split('x').map(Number); return w / h }
function matchFormat(e) {
    const w = e.target.naturalWidth || e.target.videoWidth
    const h = e.target.naturalHeight || e.target.videoHeight
    if (formatTouched.value || !w || !h) return
    const r = w / h
    selectedResolution.value = formats.value.reduce((best, f) =>
        Math.abs(ratioOf(f.value) - r) < Math.abs(ratioOf(best.value) - r) ? f : best).value
}
function closeDetail() {
    if (!generating.value) detail.value = null
}
const onKey = (e) => { if (e.key === 'Escape') result.value ? (result.value = null) : closeDetail() }

watch([detail, result], ([d, r]) => {
    document.body.style.overflow = d || r ? 'hidden' : ''
})
onMounted(() => {
    fetchData()
    window.addEventListener('keydown', onKey)
})
onUnmounted(() => {
    mounted = false
    window.removeEventListener('keydown', onKey)
    inView.disconnect()
    loadMore.disconnect()
    document.body.style.overflow = ''
})

async function copyPrompt() {
    await navigator.clipboard.writeText(detail.value.prompt)
    copied.value = true
    setTimeout(() => (copied.value = false), 2000)
}

// Generation runs in a backend queue: send it, close the modal and let the user keep browsing.
async function generate() {
    if (!detail.value || generating.value) return
    const template = detail.value
    const product = selectedProduct.value

    let brief = template.prompt
    if (product) {
        brief += `\n\nProduto: ${product.name}`
        if (product.description) brief += `\nDescrição: ${product.description}`
        if (product.category) brief += `\nCategoria: ${product.category}`
        if (product.price) brief += `\nPreço: R$ ${Number(product.price).toFixed(2)}`
        if (product.price_discount) brief += ` (desconto: R$ ${Number(product.price_discount).toFixed(2)})`
    }

    generating.value = true
    let data
    try {
        ({ data } = await api.post('/generate', {
            type: template.type,
            platform: 'instagram',
            content_type: template.type === 'video' ? 'reel' : 'post',
            resolution: selectedResolution.value,
            brief,
            product_ids: product ? [product.id] : [],
        }))
    } catch {
        return // already toasted by the api interceptor
    } finally {
        generating.value = false
    }

    detail.value = null
    pendingCount.value++
    toast.push('Geração iniciada. Você pode continuar navegando, avisamos quando ficar pronta.', 'success')

    try {
        const final = await pollGeneration(data.id)
        if (final.status === 'failed') throw new Error(final.error_message || 'Erro na geração.')
        const urls = final.assets?.map(a => a.url ?? assetUrl(a.path)) ?? []
        if (mounted) result.value = { type: template.type, urls }
        else toast.push('Sua arte ficou pronta! Veja no Histórico.', 'success')
    } catch (e) {
        if (!e.response) toast.push(e.message || 'Erro na geração.', 'error')
    } finally {
        pendingCount.value--
    }
}
</script>

<style scoped>
/* ── Uniform grid: every card 4:5, filled row by row, so loading more only appends below ── */
.masonry { display: grid; grid-template-columns: repeat(auto-fill, minmax(220px, 1fr)); gap: 12px; }
/* Phones: always two per row instead of one full-width card */
@media (max-width: 639px) { .masonry { grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 8px; } }
.masonry-card {
    position: relative;
    aspect-ratio: 4 / 5;
    border-radius: 16px;
    overflow: hidden;
    background: var(--surface-1);
    border: 1px solid var(--border-subtle);
    transition: transform 0.22s ease, border-color 0.22s, box-shadow 0.22s;
}
.masonry-card:hover,
.masonry-card:focus-within {
    transform: translateY(-3px);
    border-color: color-mix(in srgb, var(--brand) 45%, transparent);
    box-shadow: 0 0 28px color-mix(in srgb, var(--brand) 20%, transparent);
}
.card-media { display: block; width: 100%; height: 100%; object-fit: cover; }
.card-placeholder {
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100%;
    background: var(--surface-1);
}
.card-overlay {
    position: absolute;
    inset: auto 0 0 0;
    padding: 40px 14px 14px;
    background: linear-gradient(to top, rgba(3, 2, 18, 0.92), transparent);
    opacity: 0;
    transform: translateY(6px);
    transition: opacity 0.2s, transform 0.2s;
}
.masonry-card:hover .card-overlay,
.masonry-card:focus-within .card-overlay { opacity: 1; transform: none; }
.type-pill {
    display: inline-flex;
    align-items: center;
    gap: 4px;
    padding: 2px 8px;
    border-radius: 9999px;
    font-size: 0.625rem;
    font-weight: 700;
    letter-spacing: 0.07em;
    text-transform: uppercase;
    color: #fff;
    background: color-mix(in srgb, var(--brand) 45%, transparent);
}
.favorite-btn {
    position: absolute;
    top: 8px;
    right: 8px;
    width: 32px;
    height: 32px;
    display: flex;
    align-items: center;
    justify-content: center;
    border-radius: 9999px;
    color: #fff;
    background: rgba(3, 2, 18, 0.6);
    backdrop-filter: blur(8px);
    border: 1px solid rgba(255, 255, 255, 0.14);
    opacity: 0;
    transition: opacity 0.18s, transform 0.12s;
}
.masonry-card:hover .favorite-btn,
.favorite-btn:focus-visible,
.favorite-btn.is-favorite { opacity: 1; }
.favorite-btn.is-favorite { color: #fbbf24; border-color: rgba(251, 191, 36, 0.4); }
.favorite-btn:hover { transform: scale(1.08); }
/* Touch screens have no hover: keep the star reachable */
@media (hover: none) { .favorite-btn { opacity: 1; } }

/* ── Detail modal: one card, preview left, panel right (stacks on phones) ── */
.detail-backdrop {
    position: fixed;
    inset: 0;
    z-index: 50;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 24px;
    background: rgba(3, 2, 12, 0.8);
    backdrop-filter: blur(18px);
    -webkit-backdrop-filter: blur(18px);
}
.detail-card {
    display: flex;
    width: 100%;
    max-width: 1120px;
    height: min(780px, calc(100vh - 48px));
    border-radius: 24px;
    overflow: hidden;
    background: #0A0814;
    border: 1px solid rgba(255, 255, 255, 0.1);
    box-shadow: 0 40px 100px rgba(0, 0, 0, 0.6);
}
.detail-media-area {
    flex: 1;
    min-width: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 28px;
    background:
        radial-gradient(circle at 50% 45%, color-mix(in srgb, var(--brand) 12%, transparent), transparent 70%),
        #050410;
}
.detail-media {
    max-width: 100%;
    max-height: 100%;
    border-radius: 14px;
    object-fit: contain;
    box-shadow: 0 0 0 1px rgba(255, 255, 255, 0.08), 0 30px 60px rgba(0, 0, 0, 0.55);
}
.detail-panel {
    width: 400px;
    flex-shrink: 0;
    display: flex;
    flex-direction: column;
    min-height: 0;
    border-left: 1px solid var(--border-subtle);
}
.detail-head {
    display: flex;
    align-items: flex-start;
    justify-content: space-between;
    gap: 12px;
    padding: 22px 24px 18px;
    border-bottom: 1px solid var(--border-subtle);
}
.detail-body {
    flex: 1;
    min-height: 0;
    overflow-y: auto;
    display: flex;
    flex-direction: column;
    gap: 24px;
    padding: 20px 24px;
    scrollbar-width: thin;
}
.detail-footer {
    display: flex;
    flex-direction: column;
    gap: 10px;
    padding: 16px 24px 20px;
    border-top: 1px solid var(--border-subtle);
    background: #0A0814;
}
@media (max-width: 860px) {
    .detail-backdrop { padding: 0; }
    .detail-card { flex-direction: column; height: 100%; border-radius: 0; overflow-y: auto; }
    .detail-media-area { flex: none; height: 42vh; padding: 16px; }
    .detail-panel { width: 100%; border-left: 0; }
    .detail-body { overflow: visible; }
    .detail-footer { position: sticky; bottom: 0; }
}

/* Product picker */
.product-grid {
    display: grid;
    grid-template-columns: repeat(3, minmax(0, 1fr));
    grid-auto-rows: max-content; /* a scrolling grid would otherwise squeeze rows and overlap tiles */
    gap: 8px;
    max-height: 264px;
    overflow-y: auto;
    padding: 2px;
    scrollbar-width: thin;
}
.product-option {
    display: flex;
    flex-direction: column;
    gap: 6px;
    padding: 6px;
    border-radius: 12px;
    border: 1px solid var(--border-subtle);
    background: var(--surface-1);
    text-align: left;
    transition: border-color 0.15s, background-color 0.15s;
}
.product-option:hover { border-color: var(--border-soft); }
.product-option.active {
    border-color: color-mix(in srgb, var(--brand) 65%, transparent);
    background: color-mix(in srgb, var(--brand) 14%, transparent);
}
.product-thumb {
    aspect-ratio: 1 / 1;
    border-radius: 8px;
    overflow: hidden;
    display: flex;
    align-items: center;
    justify-content: center;
    background: var(--surface-2);
}
.product-thumb img { width: 100%; height: 100%; object-fit: cover; }
.product-name {
    display: -webkit-box;
    -webkit-line-clamp: 2;
    -webkit-box-orient: vertical;
    overflow: hidden;
    padding: 0 2px;
    font-size: 11px;
    line-height: 1.25;
    color: #fff;
}
.product-price {
    padding: 0 2px;
    font-size: 11px;
    font-weight: 600;
    color: color-mix(in srgb, var(--brand) 55%, white);
}

/* Prompt (collapsible) */
.prompt-details summary { cursor: pointer; list-style: none; min-height: 32px; }
.prompt-details summary::-webkit-details-marker { display: none; }
.prompt-details .chevron { transition: transform 0.2s; }
.prompt-details[open] .chevron { transform: rotate(180deg); }

.format-option {
    display: flex;
    align-items: center;
    gap: 10px;
    padding: 10px;
    border-radius: 12px;
    border: 1px solid var(--border-subtle);
    background: var(--surface-1);
    text-align: left;
    transition: border-color 0.15s, background-color 0.15s;
}
.format-option:hover { border-color: var(--border-soft); }
.format-option.active {
    border-color: color-mix(in srgb, var(--brand) 60%, transparent);
    background: color-mix(in srgb, var(--brand) 14%, transparent);
}
.format-shape {
    width: 22px;
    max-height: 30px;
    flex-shrink: 0;
    border-radius: 3px;
    border: 1.5px solid color-mix(in srgb, var(--brand) 55%, white);
}
.format-option:not(.active) .format-shape { border-color: var(--text-muted); }

.btn-generate {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    min-height: 52px;
    padding: 12px 20px;
    border-radius: 12px;
    background: var(--brand);
    color: #fff;
    font-size: 0.9375rem;
    font-weight: 600;
    box-shadow: 0 0 28px color-mix(in srgb, var(--brand) 40%, transparent);
    transition: background-color 0.16s, transform 0.12s;
}
.btn-generate:hover:not(:disabled) { background: var(--brand-hover); transform: translateY(-1px); }
.btn-generate:disabled { opacity: 0.6; cursor: progress; }

.detail-enter-active, .detail-leave-active { transition: opacity 0.25s ease; }
.detail-enter-from, .detail-leave-to { opacity: 0; }
.detail-enter-active .detail-card, .detail-leave-active .detail-card { transition: transform 0.25s ease; }
.detail-enter-from .detail-card, .detail-leave-to .detail-card { transform: scale(0.97) translateY(8px); }
</style>
