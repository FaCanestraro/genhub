<template>
    <div class="gallery-root">

        <!-- ── Header ──────────────────────────────────────────────── -->
        <div class="px-8 pt-10 pb-0">
            <p class="tech-label mb-2">Criação</p>
            <h1 class="gallery-hero-title">Galeria de Modelos</h1>
            <p class="text-sm mt-3 mb-2" style="color: var(--text-secondary)">
                Selecione um modelo, escolha um produto e gere sua arte
            </p>
        </div>

        <!-- ── Empty state ──────────────────────────────────────────── -->
        <div v-if="!loading && templates.length === 0" class="text-center py-24">
            <LayoutTemplate class="w-12 h-12 text-gray-700 mx-auto mb-4" />
            <p class="text-gray-500 text-sm mb-5">Nenhum modelo encontrado.</p>
            <RouterLink to="/templates" class="btn-primary inline-flex items-center gap-2">
                <Plus class="w-4 h-4" />
                Criar modelos
            </RouterLink>
        </div>

        <!-- ── Sections (Imagem / Vídeo) ────────────────────────────── -->
        <template v-else>
            <section
                v-for="s in sections"
                v-show="s.items.length"
                :key="s.type"
                class="gallery-section"
            >
                <!-- Section header -->
                <div class="px-8 pt-8 pb-4 flex items-end justify-between gap-4">
                    <div>
                        <h2 class="section-title">{{ s.title }}</h2>
                        <p class="section-desc">{{ s.desc }}</p>
                    </div>
                    <button
                        v-if="!s.expanded && s.items.length > COLLAPSED_COUNT"
                        @click="expanded[s.type] = true"
                        class="section-see-all"
                    >
                        Ver todos
                        <ArrowUpRight class="w-3.5 h-3.5" />
                    </button>
                    <button
                        v-else-if="s.expanded && s.items.length > COLLAPSED_COUNT"
                        @click="expanded[s.type] = false"
                        class="section-see-all"
                    >
                        Ver menos
                    </button>
                </div>

                <!-- Grid wrapper (with fade when collapsed) -->
                <div
                    class="section-grid-wrap px-8"
                    :class="{ 'is-collapsed': !s.expanded && s.items.length > COLLAPSED_COUNT }"
                >
                    <div class="masonry-grid pb-2">
                        <div v-for="(col, ci) in s.columns" :key="ci" class="masonry-col">
                            <div
                                v-for="t in col"
                                :key="t.id"
                                class="masonry-card"
                                @click="openDetail(t)"
                            >
                                <video
                                    v-if="t.type === 'video' && t.preview_url"
                                    :src="t.preview_url"
                                    class="card-media"
                                    muted loop autoplay playsinline
                                />
                                <img
                                    v-else-if="t.preview_url"
                                    :src="t.preview_url"
                                    :alt="t.title"
                                    class="card-media"
                                />
                                <div v-else class="card-placeholder">
                                    <Film v-if="t.type === 'video'" class="w-10 h-10 text-gray-700" />
                                    <ImageIcon v-else class="w-10 h-10 text-gray-700" />
                                </div>

                                <!-- Hover overlay -->
                                <div class="card-overlay">
                                    <div class="card-overlay-inner">
                                        <span class="type-pill" :class="t.type === 'video' ? 'type-pill--video' : 'type-pill--image'">
                                            {{ t.type === 'video' ? 'Vídeo' : 'Imagem' }}
                                        </span>
                                        <h3 class="card-title">{{ t.title }}</h3>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Fade + CTA (only when collapsed & has more) -->
                    <div
                        v-if="!s.expanded && s.items.length > COLLAPSED_COUNT"
                        class="section-fade"
                    >
                        <button @click="expanded[s.type] = true" class="fade-btn">
                            Ver todos os modelos
                            <ArrowUpRight class="w-4 h-4" />
                        </button>
                    </div>
                </div>
            </section>
        </template>

        <!-- ── Detail modal ─────────────────────────────────────────── -->
        <Teleport to="body">
            <Transition name="detail">
                <div v-if="detail" class="detail-backdrop" @click.self="closeDetail">

                    <!-- Blurred bg -->
                    <div
                        class="detail-bg"
                        :style="detail.preview_url
                            ? { backgroundImage: `url('${detail.preview_url}')` }
                            : {}"
                    />

                    <div class="detail-inner">

                        <!-- Left: media -->
                        <div class="detail-media-area">
                            <video
                                v-if="detail.type === 'video' && detail.preview_url"
                                :src="detail.preview_url"
                                class="detail-media"
                                muted loop autoplay playsinline controls
                            />
                            <img
                                v-else-if="detail.preview_url"
                                :src="detail.preview_url"
                                :alt="detail.title"
                                class="detail-media"
                            />
                            <div v-else class="detail-media-empty">
                                <Film v-if="detail.type === 'video'" class="w-16 h-16 text-gray-600" />
                                <ImageIcon v-else class="w-16 h-16 text-gray-600" />
                                <p class="text-gray-600 text-sm mt-3">Sem prévia</p>
                            </div>
                        </div>

                        <!-- Right: info panel -->
                        <div class="detail-panel">

                            <!-- Header -->
                            <div class="flex items-start justify-between mb-1">
                                <div class="flex-1 min-w-0 pr-3">
                                    <p class="tech-label mb-1.5">Modelo</p>
                                    <h2 class="text-xl font-bold text-white leading-tight">{{ detail.title }}</h2>
                                </div>
                                <button
                                    @click="closeDetail"
                                    class="flex-shrink-0 w-8 h-8 rounded-lg flex items-center justify-center text-gray-500 hover:text-white hover:bg-white/[0.08] transition-colors"
                                >
                                    <X class="w-4 h-4" />
                                </button>
                            </div>

                            <hr class="divider-subtle my-4" />

                            <!-- Prompt -->
                            <div class="mb-1">
                                <div class="flex items-center justify-between mb-2">
                                    <span class="tech-label">Prompt</span>
                                    <button
                                        @click="copyPrompt"
                                        class="text-xs px-2.5 py-1 rounded border transition-all"
                                        :class="copied
                                            ? 'border-emerald-500/40 text-emerald-400 bg-emerald-500/10'
                                            : 'border-white/10 text-gray-500 hover:text-white hover:border-white/20'"
                                    >
                                        {{ copied ? '✓ Copiado' : 'Copiar' }}
                                    </button>
                                </div>
                                <p
                                    class="text-sm leading-relaxed text-gray-400"
                                    :class="{ 'line-clamp-4': !expandPrompt }"
                                >
                                    {{ detail.prompt }}
                                </p>
                                <button
                                    v-if="detail.prompt && detail.prompt.length > 120"
                                    @click="expandPrompt = !expandPrompt"
                                    class="text-xs mt-2 flex items-center gap-1 transition-colors"
                                    style="color: var(--brand)"
                                >
                                    {{ expandPrompt ? 'Ver menos ↑' : 'Ver tudo ↓' }}
                                </button>
                            </div>

                            <hr class="divider-subtle my-4" />

                            <!-- Informações -->
                            <div class="mb-1">
                                <p class="tech-label mb-3">Informações</p>
                                <div class="space-y-2.5">
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm text-gray-500">Tipo</span>
                                        <span class="text-sm font-medium text-white">
                                            {{ detail.type === 'image' ? 'Imagem' : 'Vídeo' }}
                                        </span>
                                    </div>
                                    <div class="flex items-center justify-between">
                                        <span class="text-sm text-gray-500">Resolução selecionada</span>
                                        <span class="text-sm font-medium text-white">{{ selectedResolution.replace('x', ' × ') }}</span>
                                    </div>
                                </div>
                            </div>

                            <hr class="divider-subtle my-4" />

                            <!-- Configurações -->
                            <div class="space-y-3 mb-5">
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1.5 tracking-wide">Resolução</label>
                                    <select v-model="selectedResolution" class="input text-sm">
                                        <option value="1080x1080">1080 × 1080 — Feed quadrado</option>
                                        <option value="1080x1350">1080 × 1350 — Feed retrato</option>
                                        <option value="1080x1920">1080 × 1920 — Stories / Reels</option>
                                        <option value="1920x1080">1920 × 1080 — Paisagem / YouTube</option>
                                        <option value="1200x628">1200 × 628 — Facebook / LinkedIn</option>
                                    </select>
                                </div>
                                <div>
                                    <label class="block text-xs text-gray-500 mb-1.5 tracking-wide">Produto (opcional)</label>
                                    <select v-model="selectedProductId" class="input text-sm">
                                        <option value="">Sem produto específico</option>
                                        <option v-for="p in products" :key="p.id" :value="p.id">
                                            {{ p.name }}{{ p.category ? ` · ${p.category}` : '' }}
                                        </option>
                                    </select>
                                </div>
                            </div>

                            <!-- CTA -->
                            <div class="mt-auto">
                                <button
                                    @click="generate"
                                    :disabled="generating"
                                    class="btn-generate w-full"
                                >
                                    <Loader2 v-if="generating" class="w-4 h-4 animate-spin" />
                                    <Sparkles v-else class="w-4 h-4" />
                                    {{ generating ? 'Gerando...' : `Gerar ${detail.type === 'video' ? 'Vídeo' : 'Imagem'}` }}
                                </button>
                            </div>

                        </div>
                    </div>
                </div>
            </Transition>
        </Teleport>

        <!-- ── Result modal ─────────────────────────────────────────── -->
        <Teleport to="body">
            <div
                v-if="result"
                class="fixed inset-0 bg-black/50 backdrop-blur-xl flex items-center justify-center z-[60] p-4"
                @click.self="result = null"
            >
                <div class="glass-modal rounded-2xl w-full max-w-2xl p-6">
                    <div class="flex items-center justify-between mb-4">
                        <h2 class="text-lg font-semibold text-white">Arte Gerada</h2>
                        <button @click="result = null" class="p-1.5 text-gray-500 hover:text-white rounded-lg transition-colors">
                            <X class="w-4 h-4" />
                        </button>
                    </div>
                    <div class="space-y-3">
                        <template v-if="result.type === 'image'">
                            <div v-for="(url, i) in result.urls" :key="i" class="relative rounded-xl overflow-hidden">
                                <img :src="url" class="w-full rounded-xl" />
                                <a :href="url" download class="absolute bottom-2 right-2 p-1.5 bg-black/60 hover:bg-black/80 text-white rounded-lg transition-colors backdrop-blur-sm">
                                    <Download class="w-4 h-4" />
                                </a>
                            </div>
                        </template>
                        <template v-else-if="result.type === 'video'">
                            <div v-for="(url, i) in result.urls" :key="i" class="relative rounded-xl overflow-hidden">
                                <video :src="url" controls class="w-full rounded-xl" />
                                <a :href="url" download class="absolute bottom-2 right-2 p-1.5 bg-black/60 hover:bg-black/80 text-white rounded-lg transition-colors backdrop-blur-sm">
                                    <Download class="w-4 h-4" />
                                </a>
                            </div>
                        </template>
                    </div>
                    <button @click="result = null" class="mt-4 w-full text-center text-sm text-gray-500 hover:text-white transition-colors py-2">
                        Fechar
                    </button>
                </div>
            </div>
        </Teleport>

    </div>
</template>

<script setup>
import { ref, computed, onMounted, watch } from 'vue'
import { RouterLink } from 'vue-router'
import { Film, Image as ImageIcon, X, Sparkles, Loader2, Download, LayoutTemplate, Plus, ArrowUpRight } from 'lucide-vue-next'
import api from '@/services/api'
import { assetUrl } from '@/utils/assetUrl'

const templates          = ref([])
const products           = ref([])
const loading            = ref(false)
const selectedProductId  = ref('')
const selectedResolution = ref('1080x1080')
const generating         = ref(false)
const result             = ref(null)

const detail       = ref(null)
const expandPrompt = ref(false)
const copied       = ref(false)

const COL_COUNT      = 5
const COLLAPSED_COUNT = 10   // itens exibidos antes do fade / "Ver todos"

const expanded = ref({ image: false, video: false })

const SECTION_META = [
    { type: 'image', title: 'Imagem', desc: 'Modelos para gerar imagens estáticas.' },
    { type: 'video', title: 'Vídeo',  desc: 'Modelos para gerar vídeos e reels.'    },
]

const sections = computed(() =>
    SECTION_META.map(meta => {
        const items       = templates.value.filter(t => t.type === meta.type)
        const isExpanded  = expanded.value[meta.type]
        const visible     = isExpanded ? items : items.slice(0, COLLAPSED_COUNT)
        const columns     = Array.from({ length: COL_COUNT }, (_, i) =>
            visible.filter((_, j) => j % COL_COUNT === i)
        )
        return { ...meta, items, expanded: isExpanded, columns }
    })
)

// Lock body scroll when modal is open
watch(detail, (val) => {
    document.body.style.overflow = val ? 'hidden' : ''
})

async function fetchData() {
    loading.value = true
    const [tRes, pRes] = await Promise.all([
        api.get('/templates'),
        api.get('/products', { params: { per_page: 100 } }),
    ])
    templates.value = tRes.data
    products.value  = pRes.data.data ?? pRes.data
    loading.value   = false
}

function openDetail(t) {
    detail.value         = t
    expandPrompt.value   = false
    copied.value         = false
    selectedProductId.value = ''
}

function closeDetail() {
    detail.value = null
}

async function copyPrompt() {
    if (!detail.value?.prompt) return
    await navigator.clipboard.writeText(detail.value.prompt)
    copied.value = true
    setTimeout(() => (copied.value = false), 2000)
}

async function generate() {
    if (!detail.value || generating.value) return
    generating.value = true
    try {
        const product = products.value.find(p => p.id == selectedProductId.value)
        let brief = detail.value.prompt
        if (product) {
            brief += `\n\nProduto: ${product.name}`
            if (product.description) brief += `\nDescrição: ${product.description}`
            if (product.category)    brief += `\nCategoria: ${product.category}`
            if (product.price)       brief += `\nPreço: R$ ${Number(product.price).toFixed(2)}`
            if (product.price_discount) brief += ` (desconto: R$ ${Number(product.price_discount).toFixed(2)})`
        }

        const { data } = await api.post('/generate', {
            type:         detail.value.type,
            platform:     'instagram',
            content_type: detail.value.type === 'video' ? 'reel' : 'post',
            resolution:   selectedResolution.value,
            brief,
            product_ids:  product ? [product.id] : [],
        })

        const urls = data.assets?.map(a => a.url ?? assetUrl(a.path)) ?? []
        result.value = { type: detail.value.type, urls }
        closeDetail()
    } catch (e) {
        alert(e.response?.data?.message || 'Erro na geração.')
    } finally {
        generating.value = false
    }
}

onMounted(fetchData)
</script>

<style scoped>
/* ── Page root ─────────────────────────────────────────────────── */
.gallery-root {
    min-height: 100vh;
}

/* ── Hero title ────────────────────────────────────────────────── */
.gallery-hero-title {
    font-family: var(--font-display);
    font-size: clamp(1.75rem, 3.5vw, 3rem);
    font-weight: 700;
    color: #fff;
    letter-spacing: -0.03em;
    line-height: 1;
}

/* ── Section ────────────────────────────────────────────────────── */
.section-title {
    font-family: var(--font-display);
    font-size: 1.5rem;
    font-weight: 600;
    color: #fff;
    letter-spacing: -0.02em;
    line-height: 1.1;
}
.section-desc {
    font-size: 0.8125rem;
    color: var(--text-secondary);
    margin-top: 4px;
}

.section-see-all {
    display: inline-flex;
    align-items: center;
    gap: 5px;
    flex-shrink: 0;
    padding: 6px 14px;
    border-radius: 9999px;
    border: 1px solid rgba(255, 255, 255, 0.12);
    background: transparent;
    color: rgba(196, 181, 253, 1);
    font-size: 0.75rem;
    font-weight: 600;
    letter-spacing: 0.02em;
    cursor: pointer;
    transition: all 0.15s;
}
.section-see-all:hover {
    border-color: rgba(124, 58, 237, 0.48);
    background: rgba(124, 58, 237, 0.14);
    color: #ddd6fe;
}

/* ── Collapsible grid wrapper + fade ───────────────────────────── */
.section-grid-wrap {
    position: relative;
}
.section-grid-wrap.is-collapsed {
    max-height: 720px;
    overflow: hidden;
}
.section-fade {
    position: absolute;
    left: 0;
    right: 0;
    bottom: 0;
    height: 240px;
    display: flex;
    align-items: flex-end;
    justify-content: center;
    padding-bottom: 28px;
    background: linear-gradient(
        to bottom,
        transparent 0%,
        color-mix(in srgb, var(--bg-base) 65%, transparent) 45%,
        var(--bg-base) 92%
    );
    pointer-events: none;
}
.fade-btn {
    pointer-events: auto;
    display: inline-flex;
    align-items: center;
    gap: 7px;
    padding: 11px 22px;
    border-radius: 9999px;
    background: rgba(12, 10, 26, 0.92);
    border: 1px solid rgba(124, 58, 237, 0.4);
    color: #ddd6fe;
    font-size: 0.8125rem;
    font-weight: 600;
    letter-spacing: 0.01em;
    cursor: pointer;
    backdrop-filter: blur(12px);
    box-shadow: 0 0 24px rgba(124, 58, 237, 0.28), 0 6px 20px rgba(0, 0, 0, 0.4);
    transition: all 0.16s;
}
.fade-btn:hover {
    background: var(--brand);
    border-color: var(--brand);
    color: #fff;
    transform: translateY(-2px);
    box-shadow: 0 0 34px rgba(124, 58, 237, 0.5), 0 8px 24px rgba(0, 0, 0, 0.45);
}


/* ── Masonry grid ──────────────────────────────────────────────── */
.masonry-grid {
    display: flex;
    gap: 10px;
    align-items: flex-start;
}
.masonry-col {
    flex: 1;
    display: flex;
    flex-direction: column;
    gap: 10px;
    min-width: 0;
}
@media (max-width: 1400px) { .masonry-grid > .masonry-col:nth-child(5) { display: none; } }
@media (max-width: 1100px) { .masonry-grid > .masonry-col:nth-child(n+4) { display: none; } }
@media (max-width: 768px)  { .masonry-grid > .masonry-col:nth-child(n+3) { display: none; } }
@media (max-width: 480px)  { .masonry-grid > .masonry-col:nth-child(n+2) { display: none; } }

/* ── Masonry card ──────────────────────────────────────────────── */
.masonry-card {
    border-radius: 14px;
    overflow: hidden;
    cursor: pointer;
    position: relative;
    background: rgba(255, 255, 255, 0.03);
    border: 1px solid rgba(255, 255, 255, 0.07);
    transition: transform 0.22s ease, box-shadow 0.22s ease, border-color 0.22s;
}
.masonry-card:hover {
    transform: translateY(-4px);
    border-color: rgba(124, 58, 237, 0.38);
    box-shadow:
        0 0 0 1px rgba(124, 58, 237, 0.26),
        0 0 28px rgba(124, 58, 237, 0.18),
        0 0 52px rgba(124, 58, 237, 0.08);
}
.masonry-card:hover .card-overlay {
    opacity: 1;
}

.card-media {
    display: block;
    width: 100%;
    height: auto;
}
.card-placeholder {
    width: 100%;
    aspect-ratio: 4 / 5;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255, 255, 255, 0.02);
}

.card-overlay {
    position: absolute;
    inset: 0;
    background: linear-gradient(
        to top,
        rgba(3, 2, 18, 0.92) 0%,
        rgba(3, 2, 18, 0.4) 40%,
        transparent 70%
    );
    opacity: 0;
    transition: opacity 0.2s ease;
    display: flex;
    align-items: flex-end;
    padding: 14px;
}
.card-overlay-inner { width: 100%; }
.card-title {
    font-size: 0.8125rem;
    font-weight: 600;
    color: #fff;
    line-height: 1.3;
    margin-top: 5px;
}

.type-pill {
    display: inline-block;
    font-size: 0.6rem;
    font-weight: 700;
    letter-spacing: 0.07em;
    text-transform: uppercase;
    padding: 2px 8px;
    border-radius: 999px;
}
.type-pill--image {
    background: rgba(124, 58, 237, 0.22);
    color: #c4b5fd;
    border: 1px solid rgba(124, 58, 237, 0.35);
}
.type-pill--video {
    background: rgba(59, 130, 246, 0.18);
    color: #93c5fd;
    border: 1px solid rgba(59, 130, 246, 0.28);
}

/* ── Detail backdrop ───────────────────────────────────────────── */
.detail-backdrop {
    position: fixed;
    inset: 0;
    z-index: 50;
    display: flex;
    align-items: center;
    justify-content: center;
}
.detail-bg {
    position: absolute;
    inset: -20px;
    background-size: cover;
    background-position: center;
    background-color: #030212;
    filter: blur(45px) saturate(50%) brightness(0.22);
}

.detail-inner {
    position: relative;
    z-index: 1;
    display: flex;
    align-items: center;
    gap: 28px;
    width: 100%;
    max-width: 1140px;
    padding: 28px 28px 28px 28px;
    height: 100vh;
    box-sizing: border-box;
}

/* ── Detail media area ─────────────────────────────────────────── */
.detail-media-area {
    flex: 1;
    min-width: 0;
    display: flex;
    align-items: center;
    justify-content: center;
    height: 100%;
}
.detail-media {
    max-width: 100%;
    max-height: calc(100vh - 80px);
    border-radius: 16px;
    object-fit: contain;
    box-shadow:
        0 0 0 1px rgba(255, 255, 255, 0.08),
        0 40px 80px rgba(0, 0, 0, 0.65);
}
.detail-media-empty {
    display: flex;
    flex-direction: column;
    align-items: center;
    justify-content: center;
    width: 100%;
    aspect-ratio: 4 / 5;
    max-height: calc(100vh - 80px);
    background: rgba(255, 255, 255, 0.025);
    border-radius: 16px;
    border: 1px dashed rgba(255, 255, 255, 0.08);
}

/* ── Detail panel (right) ──────────────────────────────────────── */
.detail-panel {
    width: 360px;
    flex-shrink: 0;
    height: calc(100vh - 72px);
    overflow-y: auto;
    background: rgba(8, 6, 20, 0.90);
    backdrop-filter: blur(48px) saturate(160%);
    -webkit-backdrop-filter: blur(48px) saturate(160%);
    border: 1px solid rgba(255, 255, 255, 0.10);
    border-radius: 20px;
    padding: 24px;
    display: flex;
    flex-direction: column;
    box-shadow:
        inset 0 1.5px 0 rgba(255, 255, 255, 0.12),
        inset 0 0 0 1px rgba(255, 255, 255, 0.03),
        0 24px 64px rgba(0, 0, 0, 0.55);
    scrollbar-width: thin;
    scrollbar-color: rgba(255,255,255,0.08) transparent;
}
.detail-panel::-webkit-scrollbar { width: 3px; }
.detail-panel::-webkit-scrollbar-thumb { background: rgba(255, 255, 255, 0.08); border-radius: 3px; }

/* ── CTA button ────────────────────────────────────────────────── */
.btn-generate {
    display: flex;
    align-items: center;
    justify-content: center;
    gap: 8px;
    padding: 14px 24px;
    border-radius: 12px;
    background: var(--brand);
    color: #fff;
    font-size: 0.9375rem;
    font-weight: 600;
    letter-spacing: 0.01em;
    box-shadow:
        0 0 28px rgba(124, 58, 237, 0.42),
        0 4px 16px rgba(0, 0, 0, 0.3);
    transition: background 0.16s, box-shadow 0.16s, transform 0.12s;
    cursor: pointer;
}
.btn-generate:hover:not(:disabled) {
    background: var(--brand-hover);
    box-shadow:
        0 0 40px rgba(124, 58, 237, 0.58),
        0 4px 20px rgba(0, 0, 0, 0.3);
    transform: translateY(-1px);
}
.btn-generate:disabled {
    opacity: 0.48;
    transform: none;
    cursor: not-allowed;
}

/* ── Transition ────────────────────────────────────────────────── */
.detail-enter-active,
.detail-leave-active {
    transition: opacity 0.25s ease;
}
.detail-enter-from,
.detail-leave-to {
    opacity: 0;
}
.detail-enter-active .detail-inner,
.detail-leave-active .detail-inner {
    transition: transform 0.25s ease;
}
.detail-enter-from .detail-inner,
.detail-leave-to .detail-inner {
    transform: scale(0.97) translateY(8px);
}

/* ── Primary button ────────────────────────────────────────────── */
.btn-primary {
    background-color: var(--brand);
    color: #fff;
    font-weight: 500;
    font-size: 0.875rem;
    padding: 0.5rem 1.25rem;
    border-radius: 0.5rem;
    transition: background-color 0.18s, box-shadow 0.18s;
    box-shadow: 0 0 20px rgba(124, 58, 237, 0.32);
    cursor: pointer;
}
.btn-primary:hover { background-color: var(--brand-hover); }
</style>
