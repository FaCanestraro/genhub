<template>
    <div class="p-8 max-w-7xl mx-auto w-full">
        <!-- Header -->
        <div class="flex items-center justify-between mb-8">
            <div>
                <p class="text-xs font-semibold tracking-widest uppercase mb-1" style="color: var(--text-muted)">IA</p>
                <h1 class="text-2xl font-bold text-white tracking-tight leading-tight">Histórico</h1>
                <p class="text-sm mt-1" style="color: var(--text-secondary)">Todas as gerações de conteúdo com IA.</p>
            </div>

            <div class="flex items-center gap-2">
                <!-- Filtro tipo -->
                <div class="flex items-center gap-1 rounded-xl p-1" style="background: var(--surface-1); border: 1px solid var(--border-subtle)">
                    <button
                        v-for="t in typeFilters"
                        :key="t.value"
                        @click="setFilter('type', t.value)"
                        :class="filters.type === t.value
                            ? 'brand-active'
                            : 'text-gray-400 hover:text-white'"
                        class="px-3 py-1.5 rounded-lg text-xs font-medium transition-colors flex items-center gap-1.5"
                    >
                        <component :is="t.icon" class="w-3.5 h-3.5" />
                        {{ t.label }}
                    </button>
                </div>

                <!-- Filtro status -->
                <select v-model="filters.status" @change="reload" class="text-sm rounded-xl px-3 py-2 focus:outline-none text-gray-300" style="background: var(--surface-1); border: 1px solid var(--border-subtle)">
                    <option value="">Todos os status</option>
                    <option value="completed">Concluído</option>
                    <option value="failed">Falhou</option>
                    <option value="processing">Processando</option>
                </select>
            </div>
        </div>

        <!-- Loading skeleton -->
        <div v-if="loading && generations.length === 0" class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
            <div v-for="i in 10" :key="i" class="rounded-2xl overflow-hidden animate-pulse shimmer" style="border: 1px solid var(--border-subtle); background: var(--surface-1)">
                <div class="aspect-square bg-gray-800"></div>
                <div class="p-3 space-y-2">
                    <div class="w-2/3 h-3 bg-gray-800 rounded"></div>
                    <div class="w-1/2 h-2 bg-gray-800 rounded"></div>
                </div>
            </div>
        </div>

        <!-- Empty state -->
        <div v-else-if="generations.length === 0" class="text-center py-24">
            <History class="w-12 h-12 text-gray-700 mx-auto mb-4" />
            <p class="text-gray-400">Nenhuma geração encontrada.</p>
            <RouterLink to="/generate" class="mt-4 inline-flex items-center gap-2 px-4 py-2 btn-primary text-sm rounded-xl transition-colors">
                <Sparkles class="w-4 h-4" />
                Motor de Criação
            </RouterLink>
        </div>

        <!-- Grid -->
        <div v-else>
            <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 xl:grid-cols-5 gap-4">
                <div
                    v-for="gen in generations"
                    :key="gen.id"
                    class="group rounded-2xl overflow-hidden transition-all"
                    style="background: var(--surface-1); border: 1px solid var(--border-subtle)"
                    onmouseenter="this.style.borderColor='rgba(255,255,255,0.14)'; this.style.transform='translateY(-1px)'"
                    onmouseleave="this.style.borderColor='var(--border-subtle)'; this.style.transform=''"
                >
                    <!-- Thumbnail quadrada -->
                    <div class="relative aspect-square bg-gray-800">
                        <img
                            v-if="firstImage(gen)"
                            :src="firstImage(gen).url"
                            class="w-full h-full object-cover"
                        />
                        <div v-else-if="firstVideo(gen)" class="w-full h-full relative">
                            <video :src="firstVideo(gen).url" class="w-full h-full object-cover" muted></video>
                            <div class="absolute inset-0 flex items-center justify-center bg-black/40">
                                <Film class="w-8 h-8 text-white" />
                            </div>
                        </div>
                        <div v-else class="w-full h-full flex items-center justify-center">
                            <component :is="typeIcon(gen.type)" class="w-10 h-10 text-gray-600" />
                        </div>

                        <!-- Overlay com ações ao hover -->
                        <div class="absolute inset-0 bg-black/60 opacity-0 group-hover:opacity-100 transition-opacity flex flex-col justify-between p-3">
                            <!-- Topo: excluir -->
                            <div class="flex justify-end">
                                <button
                                    @click="deleteGen(gen)"
                                    class="p-1.5 bg-red-500/20 hover:bg-red-500/40 rounded-lg transition-colors"
                                    title="Excluir"
                                >
                                    <Trash2 class="w-3.5 h-3.5 text-red-400" />
                                </button>
                            </div>

                            <!-- Base: ações -->
                            <div class="flex flex-wrap items-center gap-1.5">
                                <RouterLink
                                    v-if="gen.action"
                                    :to="`/campaigns/${gen.action.campaign_id}/actions/${gen.action.id}`"
                                    class="flex items-center gap-1 text-xs bg-white/10 hover:bg-white/20 text-white px-2 py-1 rounded-lg transition-colors"
                                >
                                    <ExternalLink class="w-3 h-3" />
                                    Ver ação
                                </RouterLink>
                                <RouterLink
                                    v-else-if="gen.session_id"
                                    :to="`/generate?session=${gen.session_id}`"
                                    class="flex items-center gap-1 text-xs bg-white/10 hover:bg-white/20 text-white px-2 py-1 rounded-lg transition-colors"
                                >
                                    <MessageSquare class="w-3 h-3" />
                                    Retomar
                                </RouterLink>
                                <button
                                    v-for="asset in downloadableAssets(gen)"
                                    :key="asset.id"
                                    @click="downloadAsset(asset.url)"
                                    class="flex items-center gap-1 text-xs bg-white/10 hover:bg-white/20 text-white px-2 py-1 rounded-lg transition-colors"
                                >
                                    <Download class="w-3 h-3" />
                                    {{ asset.type === 'video' ? 'Vídeo' : 'Baixar' }}
                                </button>
                            </div>
                        </div>

                        <!-- Badge de status (canto superior esquerdo) -->
                        <div class="absolute top-2 left-2">
                            <StatusBadge :status="gen.status" />
                        </div>

                        <!-- Contador de assets extras -->
                        <div v-if="totalAssets(gen) > 1" class="absolute bottom-2 right-2 bg-black/70 text-white text-xs px-1.5 py-0.5 rounded-full">
                            {{ totalAssets(gen) }}×
                        </div>
                    </div>

                    <!-- Info abaixo da thumbnail -->
                    <div class="p-3">
                        <div class="flex items-center justify-between gap-1 mb-1">
                            <span class="text-xs px-1.5 py-0.5 rounded-full bg-gray-800 text-gray-400 capitalize">{{ typeLabel(gen.type) }}</span>
                            <span class="text-xs text-gray-600">{{ formatDate(gen.created_at) }}</span>
                        </div>

                        <p v-if="gen.session_title" class="text-xs font-medium text-gray-200 truncate">
                            {{ gen.session_title }}
                        </p>
                        <p v-else-if="gen.prompt" class="text-xs text-gray-400 line-clamp-2 leading-relaxed">
                            {{ gen.prompt }}
                        </p>
                        <p v-else-if="gen.status === 'failed' && gen.error_message" class="text-xs text-red-400 line-clamp-2">
                            {{ gen.error_message }}
                        </p>

                        <div class="mt-1.5 flex items-center gap-1 text-xs text-gray-600">
                            <template v-if="gen.action">
                                <Megaphone class="w-3 h-3 flex-shrink-0" />
                                <span class="truncate">{{ gen.action.campaign?.name }}</span>
                            </template>
                            <template v-else>
                                <Sparkles class="w-3 h-3 flex-shrink-0" />
                                <span>Avulsa</span>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Paginação -->
            <div v-if="hasMore" class="flex justify-center pt-6">
                <button
                    @click="loadMore"
                    :disabled="loading"
                    class="px-6 py-2.5 bg-gray-900 border border-gray-700 hover:border-gray-600 text-gray-300 hover:text-white text-sm rounded-xl transition-colors disabled:opacity-50"
                >
                    <Loader2 v-if="loading" class="w-4 h-4 animate-spin inline mr-2" />
                    Carregar mais
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import { History, Sparkles, Film, Image, FileText, Layers, Trash2, Download, Loader2, Megaphone, ExternalLink, MessageSquare } from 'lucide-vue-next'
import api from '@/services/api'
import { downloadAsset } from '@/utils/download'
import StatusBadge from '@/components/StatusBadge.vue'

const generations = ref([])
const loading     = ref(true)
const page        = ref(1)
const lastPage    = ref(1)
const filters     = ref({ type: '', status: '' })

const typeFilters = [
    { value: '',        label: 'Todos',     icon: History },
    { value: 'image',   label: 'Imagens',   icon: Image },
    { value: 'video',   label: 'Vídeos',    icon: Film },
    { value: 'carousel',label: 'Carrossel', icon: Layers },
    { value: 'text',    label: 'Legendas',  icon: FileText },
]

const hasMore = computed(() => page.value < lastPage.value)

function typeLabel(type) {
    return { image: 'Imagem', video: 'Vídeo', carousel: 'Carrossel', text: 'Legenda' }[type] ?? type
}

function typeIcon(type) {
    return { image: Image, video: Film, carousel: Layers, text: FileText }[type] ?? Sparkles
}

function firstImage(gen) {
    return (gen.assets || []).find(a => a.type === 'image')
}

function firstVideo(gen) {
    return (gen.assets || []).find(a => a.type === 'video')
}

function totalAssets(gen) {
    return (gen.assets || []).length
}

function extraAssets(gen) {
    const all = gen.assets || []
    const first = firstImage(gen) || firstVideo(gen)
    return all.filter(a => a.id !== first?.id).slice(0, 3)
}

function downloadableAssets(gen) {
    return (gen.assets || []).slice(0, 3)
}

async function fetchGenerations(reset = false) {
    loading.value = true
    try {
        const params = { page: page.value }
        if (filters.value.type)   params.type   = filters.value.type
        if (filters.value.status) params.status  = filters.value.status

        const { data } = await api.get('/generations', { params })
        if (reset) {
            generations.value = data.data
        } else {
            generations.value.push(...data.data)
        }
        lastPage.value = data.last_page
    } finally {
        loading.value = false
    }
}

function setFilter(key, value) {
    filters.value[key] = value
    reload()
}

async function reload() {
    page.value = 1
    await fetchGenerations(true)
}

async function loadMore() {
    page.value++
    await fetchGenerations(false)
}

async function deleteGen(gen) {
    if (!confirm('Excluir esta geração e seus arquivos?')) return
    await api.delete(`/generations/${gen.id}`)
    generations.value = generations.value.filter(g => g.id !== gen.id)
}

function formatDate(d) {
    if (!d) return ''
    const date = new Date(d)
    const today = new Date()
    const yesterday = new Date(today)
    yesterday.setDate(today.getDate() - 1)

    const dateStr = date.toLocaleDateString('pt-BR')
    const todayStr = today.toLocaleDateString('pt-BR')
    const yestStr = yesterday.toLocaleDateString('pt-BR')

    const time = date.toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' })

    if (dateStr === todayStr) return `Hoje, ${time}`
    if (dateStr === yestStr)  return `Ontem, ${time}`
    return `${dateStr}, ${time}`
}

onMounted(() => fetchGenerations(true))
</script>
