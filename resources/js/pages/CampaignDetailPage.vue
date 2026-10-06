<template>
    <div class="page flex flex-col gap-6">

        <!-- Header -->
        <header class="flex flex-col gap-4">
            <nav aria-label="Navegação" class="flex items-center gap-1.5 text-sm" style="color: var(--text-muted)">
                <RouterLink to="/campaigns" class="hover:text-white transition-colors">Campanhas</RouterLink>
                <ChevronRight class="w-3.5 h-3.5" />
                <span class="text-white truncate">{{ campaign?.name ?? '...' }}</span>
            </nav>

            <div v-if="!campaign" class="flex flex-col gap-3">
                <div class="shimmer h-9 w-72 rounded-lg"></div>
                <div class="shimmer h-4 w-96 max-w-full rounded-md"></div>
            </div>
            <div v-else class="flex flex-wrap items-end justify-between gap-4">
                <div class="flex flex-col gap-2 min-w-0">
                    <div class="flex flex-wrap items-center gap-3">
                        <h1 class="page-hero-title text-3xl leading-tight">{{ campaign.name }}</h1>
                        <StatusBadge :status="campaign.status" />
                    </div>
                    <p v-if="campaign.description" class="text-sm max-w-2xl" style="color: var(--text-secondary)">{{ campaign.description }}</p>
                    <div class="flex flex-wrap items-center gap-x-5 gap-y-2 text-sm mt-1" style="color: var(--text-secondary)">
                        <span v-if="campaign.start_date || campaign.end_date" class="flex items-center gap-1.5">
                            <CalendarRange class="w-4 h-4" />
                            {{ formatShort(campaign.start_date) || '—' }} → {{ formatShort(campaign.end_date) || '—' }}
                            <span v-if="period(campaign)" class="ml-1 text-xs" :class="period(campaign).tone">· {{ period(campaign).label }}</span>
                        </span>
                        <span v-if="Number(campaign.budget)" class="flex items-center gap-1.5">
                            <Wallet class="w-4 h-4" /> R$ {{ numberToCurrency(campaign.budget) }}
                        </span>
                        <span class="flex items-center gap-1.5">
                            <Layers class="w-4 h-4" /> {{ actions.length }} {{ actions.length === 1 ? 'ação' : 'ações' }}
                        </span>
                    </div>
                </div>
                <button v-if="auth.can('campaigns', 'create')" @click="openActionModal()" class="btn-primary inline-flex items-center gap-2 px-5 py-2.5 rounded-xl text-sm font-semibold text-white">
                    <Plus class="w-4 h-4" /> Nova ação
                </button>
            </div>
        </header>

        <!-- Actions -->
        <div v-if="loading" class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            <div v-for="i in 3" :key="i" class="shimmer h-80 rounded-2xl"></div>
        </div>

        <EmptyState
            v-else-if="!actions.length"
            class="panel"
            :icon="Layers"
            title="Nenhuma ação nesta campanha"
            text="Cada ação é uma peça: um post, um reel, um story. Crie a primeira e gere os criativos com IA."
        />

        <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            <article v-for="action in actions" :key="action.id" class="action-tile group">
                <RouterLink :to="`/campaigns/${campaignId}/actions/${action.id}`" class="flex flex-col h-full">
                    <!-- Preview do último criativo -->
                    <span class="relative block aspect-[4/3] overflow-hidden" style="background: var(--surface-2)">
                        <template v-if="preview(action)">
                            <video v-if="preview(action).type === 'video'" :src="`${preview(action).url}#t=0.1`" muted playsinline preload="metadata" class="w-full h-full object-cover"></video>
                            <img v-else :src="preview(action).url" :alt="action.title" loading="lazy" class="w-full h-full object-cover transition-transform duration-300 group-hover:scale-105" />
                        </template>
                        <span v-else class="w-full h-full flex flex-col items-center justify-center gap-2" style="color: var(--text-muted)">
                            <Sparkles class="w-7 h-7" />
                            <span class="text-xs">Nenhum criativo gerado</span>
                        </span>
                        <span v-if="isGenerating(action)" class="absolute inset-0 flex items-center justify-center gap-2 text-sm text-white bg-black/55">
                            <Loader2 class="w-4 h-4 animate-spin" /> Gerando...
                        </span>
                    </span>

                    <span class="flex flex-col gap-3 p-5 flex-1">
                        <span class="flex flex-wrap items-center gap-2">
                            <PlatformIcon :platform="action.platform" />
                            <TypeBadge :type="action.type" />
                            <StatusBadge :status="action.status" class="ml-auto" />
                        </span>
                        <span class="min-w-0">
                            <span class="block font-semibold text-white leading-snug">{{ action.title }}</span>
                            <span v-if="action.brief" class="block text-sm mt-1 line-clamp-2" style="color: var(--text-secondary)">{{ action.brief }}</span>
                        </span>
                        <span class="mt-auto flex items-center justify-between gap-3 pt-3 text-xs" style="border-top: 1px solid var(--border-subtle); color: var(--text-muted)">
                            <span class="flex items-center gap-1.5"><Calendar class="w-3.5 h-3.5" /> {{ formatDate(action.created_at) }}</span>
                            <span v-if="action.resolution" class="tabular-nums">{{ action.resolution.replace('x', '×') }}</span>
                        </span>
                    </span>
                </RouterLink>

                <!-- Edit / delete -->
                <div class="absolute top-2.5 right-2.5 flex gap-1">
                    <button v-if="auth.can('campaigns', 'edit')" type="button" @click="openActionModal(action)" class="tile-btn" :aria-label="`Editar ação ${action.title}`">
                        <Pencil class="w-3.5 h-3.5" />
                    </button>
                    <button v-if="auth.can('campaigns', 'delete')" type="button" @click="deleteAction(action)" class="tile-btn hover:!text-red-400" :aria-label="`Excluir ação ${action.title}`">
                        <Trash2 class="w-3.5 h-3.5" />
                    </button>
                </div>
            </article>
        </div>

        <!-- Action Modal (create / edit) -->
        <Teleport to="body">
            <div v-if="showActionModal" class="fixed inset-0 dialog-backdrop flex items-center justify-center z-50 p-4" @click.self="showActionModal = false">
                <div class="glass-dialog rounded-2xl w-full max-w-xl max-h-[90vh] overflow-y-auto p-6" role="dialog" aria-modal="true" :aria-label="editingAction ? 'Editar ação' : 'Nova ação'">
                    <div class="flex items-start justify-between gap-3 mb-6">
                        <div>
                            <p class="tech-label mb-1">{{ campaign?.name }}</p>
                            <h2 class="page-hero-title text-lg">{{ editingAction ? 'Editar ação' : 'Nova ação' }}</h2>
                        </div>
                        <button type="button" @click="showActionModal = false" class="icon-btn -mr-2 -mt-1" aria-label="Fechar"><X class="w-4 h-4" /></button>
                    </div>

                    <form @submit.prevent="saveAction" class="flex flex-col gap-5">
                        <div>
                            <label for="action-title" class="field-label">Título *</label>
                            <input id="action-title" v-model="actionForm.title" type="text" required class="input" placeholder="Ex: Oferta de fim de semana" />
                        </div>

                        <div>
                            <p class="field-label">Plataforma</p>
                            <div class="flex flex-wrap gap-2" role="radiogroup" aria-label="Plataforma">
                                <button v-for="p in PLATFORMS" :key="p.value" type="button" role="radio" :aria-checked="actionForm.platform === p.value" @click="actionForm.platform = p.value" class="filter-chip" :class="{ active: actionForm.platform === p.value }">
                                    <component :is="p.icon" class="w-3.5 h-3.5" /> {{ p.label }}
                                </button>
                            </div>
                        </div>

                        <div>
                            <p class="field-label">Tipo</p>
                            <div class="flex flex-wrap gap-2" role="radiogroup" aria-label="Tipo">
                                <button v-for="t in TYPES" :key="t.value" type="button" role="radio" :aria-checked="actionForm.type === t.value" @click="actionForm.type = t.value" class="filter-chip" :class="{ active: actionForm.type === t.value }">
                                    {{ t.label }}
                                </button>
                            </div>
                        </div>

                        <div>
                            <label for="action-resolution" class="field-label">Formato</label>
                            <select id="action-resolution" v-model="actionForm.resolution" class="input">
                                <option v-for="r in resolutions[actionForm.platform] || []" :key="r.value" :value="r.value">{{ r.label }}</option>
                            </select>
                        </div>

                        <div>
                            <label for="action-brief" class="field-label">Brief para a IA</label>
                            <textarea id="action-brief" v-model="actionForm.brief" rows="3" class="input resize-none" placeholder="Ex: oferta de fim de semana, destaque no preço, tom alegre..."></textarea>
                        </div>

                        <div v-if="products.length">
                            <div class="flex items-center justify-between mb-1.5">
                                <p class="field-label !mb-0">Produtos</p>
                                <span v-if="actionForm.product_ids.length" class="text-xs" style="color: var(--text-muted)">{{ actionForm.product_ids.length }} selecionado{{ actionForm.product_ids.length > 1 ? 's' : '' }}</span>
                            </div>
                            <div class="flex flex-wrap gap-2 max-h-40 overflow-y-auto p-0.5">
                                <button
                                    v-for="p in products"
                                    :key="p.id"
                                    type="button"
                                    @click="toggleProduct(p.id)"
                                    :aria-pressed="actionForm.product_ids.includes(p.id)"
                                    class="filter-chip !pl-1.5"
                                    :class="{ active: actionForm.product_ids.includes(p.id) }"
                                >
                                    <img v-if="p.images?.length" :src="p.images[0]" alt="" class="w-6 h-6 rounded-full object-cover" />
                                    <Package v-else class="w-4 h-4 mx-1" />
                                    {{ p.name }}
                                </button>
                            </div>
                        </div>

                        <!-- Material já gerado -->
                        <div class="rounded-xl overflow-hidden" style="border: 1px solid var(--border-soft)">
                            <button
                                type="button"
                                @click="showMaterialPicker = !showMaterialPicker"
                                :aria-expanded="showMaterialPicker"
                                class="w-full flex items-center justify-between px-4 py-3 text-sm transition-colors hover:bg-white/5"
                                style="color: var(--text-secondary)"
                            >
                                <span class="flex items-center gap-2">
                                    <Sparkles class="w-4 h-4" style="color: color-mix(in srgb, var(--brand) 55%, white)" />
                                    Vincular criativos já gerados
                                    <span v-if="actionForm.attach_generation_ids.length" class="text-xs text-white px-1.5 py-0.5 rounded-full" style="background: var(--brand)">
                                        {{ actionForm.attach_generation_ids.length }}
                                    </span>
                                </span>
                                <ChevronDown class="w-4 h-4 transition-transform" :class="{ 'rotate-180': showMaterialPicker }" />
                            </button>

                            <div v-if="showMaterialPicker" class="px-4 pb-4" style="border-top: 1px solid var(--border-soft)">
                                <p class="text-xs mt-3 mb-3" style="color: var(--text-muted)">Selecione criativos do histórico para anexar a esta ação.</p>

                                <div v-if="loadingGenerations" class="grid grid-cols-4 gap-2">
                                    <div v-for="i in 8" :key="i" class="shimmer aspect-square rounded-lg"></div>
                                </div>
                                <p v-else-if="!availableGenerations.length" class="text-xs text-center py-4" style="color: var(--text-muted)">Nenhum criativo disponível.</p>
                                <!-- Scroll lives on the wrapper: a scrolling grid squeezes its rows and the tiles overlap -->
                                <div v-else class="max-h-72 overflow-y-auto pr-1 -mr-1">
                                    <div class="grid grid-cols-3 sm:grid-cols-4 gap-2">
                                        <button
                                            v-for="gen in availableGenerations"
                                            :key="gen.id"
                                            type="button"
                                            @click="toggleGeneration(gen.id)"
                                            :aria-pressed="actionForm.attach_generation_ids.includes(gen.id)"
                                            :aria-label="`${GEN_LABELS[gen.type] ?? gen.type} de ${formatDate(gen.created_at)}`"
                                            class="relative aspect-square rounded-lg overflow-hidden transition-shadow"
                                            :style="{ background: 'var(--surface-2)', boxShadow: actionForm.attach_generation_ids.includes(gen.id) ? '0 0 0 2px var(--brand)' : '0 0 0 1px var(--border-subtle)' }"
                                        >
                                            <video v-if="genIsVideo(gen)" :src="`${genThumbnail(gen)}#t=0.1`" muted playsinline preload="metadata" class="w-full h-full object-cover"></video>
                                            <img v-else-if="genThumbnail(gen)" :src="genThumbnail(gen)" alt="" loading="lazy" class="w-full h-full object-cover" />
                                            <span class="absolute inset-x-0 bottom-0 flex items-center justify-between gap-1 px-1.5 py-1 text-[10px] text-white bg-gradient-to-t from-black/80 to-transparent">
                                                <span class="flex items-center gap-1"><Film v-if="genIsVideo(gen)" class="w-3 h-3" />{{ GEN_LABELS[gen.type] ?? gen.type }}</span>
                                                <span class="text-white/70">{{ formatShortDate(gen.created_at) }}</span>
                                            </span>
                                            <span v-if="actionForm.attach_generation_ids.includes(gen.id)" class="absolute top-1.5 right-1.5 w-5 h-5 rounded-full flex items-center justify-center" style="background: var(--brand)">
                                                <Check class="w-3 h-3 text-white" />
                                            </span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end gap-3 pt-1">
                            <button type="button" @click="showActionModal = false" class="btn-secondary">Cancelar</button>
                            <button type="submit" :disabled="saving" class="btn-primary px-5 py-2.5 rounded-xl text-sm font-semibold text-white">
                                {{ saving ? 'Salvando...' : (editingAction ? 'Salvar' : 'Criar ação') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<script setup>
import { ref, onMounted, computed, watch } from 'vue'
import { useRoute, RouterLink } from 'vue-router'
import { Plus, Layers, Sparkles, Calendar, Pencil, Trash2, Film, Check, ChevronDown, ChevronRight, CalendarRange, Wallet, Loader2, Package, X, Instagram, Facebook, Music2 } from 'lucide-vue-next'
import api from '@/services/api'
import StatusBadge from '@/components/StatusBadge.vue'
import PlatformIcon from '@/components/PlatformIcon.vue'
import TypeBadge from '@/components/TypeBadge.vue'
import EmptyState from '@/components/ui/EmptyState.vue'
import { useAuthStore } from '@/stores/auth'
import { numberToCurrency } from '@/utils/mask'
import { formatShort, period } from '@/utils/campaignPeriod'

const auth = useAuthStore()

const route = useRoute()
const campaignId = computed(() => route.params.id)

const campaign = ref(null)
const actions = ref([])
const products = ref([])
const loading = ref(true)
const showActionModal = ref(false)
const saving = ref(false)
const editingAction = ref(null)

const emptyForm = () => ({ title: '', platform: 'instagram', type: 'post', resolution: '1080x1080', brief: '', quantity: 1, product_ids: [], attach_generation_ids: [] })
const actionForm = ref(emptyForm())

const availableGenerations = ref([])
const showMaterialPicker = ref(false)
const loadingGenerations = ref(false)

const PLATFORMS = [
    { value: 'instagram', label: 'Instagram', icon: Instagram },
    { value: 'facebook', label: 'Facebook', icon: Facebook },
    { value: 'tiktok', label: 'TikTok', icon: Music2 },
]
const TYPES = [
    { value: 'post', label: 'Post' },
    { value: 'carousel', label: 'Carrossel' },
    { value: 'reel', label: 'Reel' },
    { value: 'story', label: 'Story' },
    { value: 'tiktok_video', label: 'Vídeo TikTok' },
]

const resolutions = {
    instagram: [
        { value: '1080x1080', label: 'Feed quadrado (1080×1080)' },
        { value: '1080x1350', label: 'Feed retrato (1080×1350)' },
        { value: '1080x1920', label: 'Stories / Reels (1080×1920)' },
    ],
    tiktok: [
        { value: '1080x1920', label: 'Vertical (1080×1920)' },
        { value: '1920x1080', label: 'Horizontal (1920×1080)' },
    ],
    facebook: [
        { value: '1200x630', label: 'Link / compartilhamento (1200×630)' },
        { value: '1080x1080', label: 'Quadrado (1080×1080)' },
    ],
}

// Switching platform must not keep a format that platform doesn't offer.
watch(() => actionForm.value.platform, (platform) => {
    const options = resolutions[platform] || []
    if (!options.some(r => r.value === actionForm.value.resolution)) {
        actionForm.value.resolution = options[0]?.value
    }
})

// Latest creative of an action, for the card preview.
function preview(action) {
    const assets = action.latest_generation?.assets || []
    return assets.find(a => a.type === 'image') ?? assets.find(a => a.type === 'video') ?? null
}
const isGenerating = (action) => ['pending', 'processing'].includes(action.latest_generation?.status)

async function fetchData() {
    const [campaignRes, actionsRes, productsRes] = await Promise.allSettled([
        api.get(`/campaigns/${campaignId.value}`),
        api.get(`/campaigns/${campaignId.value}/actions`),
        auth.can('products', 'view') ? api.get('/products?per_page=100') : null,
    ])
    if (campaignRes.value) campaign.value = campaignRes.value.data
    if (actionsRes.value) actions.value = actionsRes.value.data
    if (productsRes.value) products.value = productsRes.value.data.data
    loading.value = false
}

function openActionModal(action = null) {
    editingAction.value = action
    actionForm.value = action
        ? {
            title: action.title,
            platform: action.platform,
            type: action.type,
            resolution: action.resolution || '1080x1080',
            brief: action.brief || '',
            quantity: action.quantity || 1,
            product_ids: action.product_ids || [],
            attach_generation_ids: [],
          }
        : emptyForm()
    showMaterialPicker.value = false
    showActionModal.value = true
    loadAvailableGenerations()
}

async function loadAvailableGenerations() {
    loadingGenerations.value = true
    try {
        const { data } = await api.get('/generations', { params: { status: 'completed', per_page: 40 } })
        availableGenerations.value = (data.data || []).filter(g => g.assets?.length > 0)
    } finally {
        loadingGenerations.value = false
    }
}

function toggleGeneration(id) {
    const ids = actionForm.value.attach_generation_ids
    const idx = ids.indexOf(id)
    idx > -1 ? ids.splice(idx, 1) : ids.push(id)
}

function genThumbnail(gen) {
    return gen.assets?.find(a => a.type === 'image')?.url
        ?? gen.assets?.find(a => a.type === 'video')?.url
        ?? null
}

function genIsVideo(gen) {
    return !gen.assets?.find(a => a.type === 'image') && gen.assets?.find(a => a.type === 'video')
}

function toggleProduct(id) {
    const ids = actionForm.value.product_ids
    const idx = ids.indexOf(id)
    idx > -1 ? ids.splice(idx, 1) : ids.push(id)
}

async function saveAction() {
    saving.value = true
    try {
        if (editingAction.value) {
            await api.put(`/actions/${editingAction.value.id}`, actionForm.value)
        } else {
            await api.post(`/campaigns/${campaignId.value}/actions`, actionForm.value)
        }
        showActionModal.value = false
        fetchData()
    } finally {
        saving.value = false
    }
}

async function deleteAction(action) {
    if (!confirm(`Excluir a ação "${action.title}"? Isso também remove todas as gerações e assets.`)) return
    await api.delete(`/actions/${action.id}`)
    actions.value = actions.value.filter(a => a.id !== action.id)
}

function formatDate(d) {
    return d ? new Date(d).toLocaleDateString('pt-BR') : ''
}
const formatShortDate = (d) => d ? new Date(d).toLocaleDateString('pt-BR', { day: '2-digit', month: '2-digit' }) : ''
const GEN_LABELS = { image: 'Imagem', video: 'Vídeo', carousel: 'Carrossel', text: 'Texto' }

onMounted(fetchData)
</script>

<style scoped>
.action-tile {
    position: relative;
    border-radius: 1.25rem;
    overflow: hidden;
    background: var(--surface-1);
    border: 1px solid var(--border-subtle);
    transition: border-color 0.2s, transform 0.2s, box-shadow 0.2s;
}
.action-tile:hover,
.action-tile:focus-within {
    transform: translateY(-2px);
    border-color: color-mix(in srgb, var(--brand) 40%, transparent);
    box-shadow: 0 0 32px color-mix(in srgb, var(--brand) 16%, transparent);
}
.tile-btn {
    width: 32px;
    height: 32px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 9px;
    color: #fff;
    background: rgba(3, 2, 18, 0.6);
    backdrop-filter: blur(8px);
    border: 1px solid rgba(255, 255, 255, 0.12);
    opacity: 0;
    transition: opacity 0.18s, color 0.15s;
}
.action-tile:hover .tile-btn,
.tile-btn:focus-visible { opacity: 1; }
@media (hover: none) { .tile-btn { opacity: 1; } }
</style>
