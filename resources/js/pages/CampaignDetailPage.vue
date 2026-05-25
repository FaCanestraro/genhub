<template>
    <div class="p-8">
        <!-- Header -->
        <div v-if="campaign" class="mb-8">
            <div class="flex items-center gap-2 text-sm text-gray-400 mb-3">
                <RouterLink to="/campaigns" class="hover:text-white">Campanhas</RouterLink>
                <span>/</span>
                <span class="text-white">{{ campaign.name }}</span>
            </div>

            <div class="flex items-start justify-between">
                <div>
                    <div class="flex items-center gap-3 mb-1">
                        <h1 class="text-2xl font-bold text-white">{{ campaign.name }}</h1>
                        <StatusBadge :status="campaign.status" />
                    </div>
                    <p v-if="campaign.description" class="text-gray-400">{{ campaign.description }}</p>
                </div>
                <button @click="openActionModal()" class="btn-primary flex items-center gap-2">
                    <Plus class="w-4 h-4" />
                    Nova Ação
                </button>
            </div>
        </div>

        <!-- Actions list -->
        <div v-if="actions.length === 0 && !loading" class="text-center py-20">
            <Layers class="w-12 h-12 text-gray-600 mx-auto mb-4" />
            <p class="text-gray-400">Nenhuma ação criada nesta campanha.</p>
            <p class="text-sm text-gray-500 mt-1">Crie ações para gerar posts de Instagram, TikToks e muito mais.</p>
            <button @click="openActionModal()" class="btn-primary mt-4">Criar primeira ação</button>
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-2 xl:grid-cols-3 gap-4">
            <div
                v-for="action in actions"
                :key="action.id"
                class="relative glass-modal rounded-2xl p-5 hover:border-gray-700 transition-all"
            >
                <!-- Action buttons -->
                <div class="absolute top-3 right-3 flex gap-1 z-10">
                    <button
                        @click.stop="openActionModal(action)"
                        class="p-1.5 text-gray-500 hover:text-white hover:bg-gray-700 rounded-lg transition-colors"
                        title="Editar"
                    >
                        <Pencil class="w-3.5 h-3.5" />
                    </button>
                    <button
                        @click.stop="deleteAction(action)"
                        class="p-1.5 text-gray-500 hover:text-red-400 hover:bg-gray-700 rounded-lg transition-colors"
                        title="Excluir"
                    >
                        <Trash2 class="w-3.5 h-3.5" />
                    </button>
                </div>

                <RouterLink :to="`/campaigns/${campaignId}/actions/${action.id}`" class="block">
                    <div class="flex items-start justify-between mb-3 pr-14">
                        <div class="flex items-center gap-2">
                            <PlatformIcon :platform="action.platform" />
                            <TypeBadge :type="action.type" />
                        </div>
                        <StatusBadge :status="action.status" />
                    </div>

                    <h3 class="font-semibold text-white">{{ action.title }}</h3>
                    <p v-if="action.brief" class="text-sm text-gray-400 mt-1 line-clamp-2">{{ action.brief }}</p>

                    <div v-if="action.latest_generation" class="mt-3 pt-3 border-t border-gray-800">
                        <div class="flex items-center gap-2 text-xs text-gray-400">
                            <Sparkles class="w-3.5 h-3.5" />
                            Última geração: <StatusBadge :status="action.latest_generation.status" />
                        </div>
                    </div>

                    <div class="mt-3 pt-3 border-t border-gray-800 flex items-center gap-2 text-xs text-gray-400">
                        <Calendar class="w-3.5 h-3.5" />
                        {{ formatDate(action.created_at) }}
                    </div>
                </RouterLink>
            </div>
        </div>

        <!-- Action Modal (create / edit) -->
        <Teleport to="body">
            <div v-if="showActionModal" class="fixed inset-0 bg-black/30 backdrop-blur-xl flex items-center justify-center z-50 p-4" @click.self="showActionModal = false">
                <div class="glass-modal rounded-2xl w-full max-w-lg p-6 max-h-[90vh] overflow-y-auto">
                    <h2 class="text-lg font-semibold text-white mb-5">
                        {{ editingAction ? 'Editar Ação' : 'Nova Ação' }}
                    </h2>

                    <form @submit.prevent="saveAction" class="space-y-4">
                        <div>
                            <label class="block text-sm text-gray-400 mb-1">Título *</label>
                            <input v-model="actionForm.title" type="text" required class="input" placeholder="Ex: Post de lançamento" />
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm text-gray-400 mb-1">Plataforma</label>
                                <select v-model="actionForm.platform" class="input">
                                    <option value="instagram">Instagram</option>
                                    <option value="tiktok">TikTok</option>
                                    <option value="facebook">Facebook</option>
                                </select>
                            </div>
                            <div>
                                <label class="block text-sm text-gray-400 mb-1">Tipo</label>
                                <select v-model="actionForm.type" class="input">
                                    <option value="post">Post</option>
                                    <option value="reel">Reel</option>
                                    <option value="carousel">Carrossel</option>
                                    <option value="story">Story</option>
                                    <option value="tiktok_video">Vídeo TikTok</option>
                                </select>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm text-gray-400 mb-1">Resolução</label>
                            <select v-model="actionForm.resolution" class="input">
                                <option v-for="r in resolutions[actionForm.platform] || []" :key="r.value" :value="r.value">
                                    {{ r.label }}
                                </option>
                            </select>
                        </div>

                        <div>
                            <label class="block text-sm text-gray-400 mb-1">Brief / Instruções para IA</label>
                            <textarea v-model="actionForm.brief" rows="3" class="input resize-none" placeholder="Descreva o que a IA deve gerar..."></textarea>
                        </div>

                        <div>
                            <label class="block text-sm text-gray-400 mb-1">Quantidade de imagens</label>
                            <input v-model="actionForm.quantity" type="number" min="1" max="4" class="input" />
                        </div>

                        <div v-if="products.length > 0">
                            <label class="block text-sm text-gray-400 mb-1">Produtos relacionados</label>
                            <div class="flex flex-wrap gap-2">
                                <button
                                    v-for="p in products"
                                    :key="p.id"
                                    type="button"
                                    @click="toggleProduct(p.id)"
                                    :class="actionForm.product_ids.includes(p.id) ? 'bg-violet-600 text-white' : 'bg-gray-800 text-gray-400 hover:text-white'"
                                    class="px-3 py-1 rounded-full text-xs transition-colors"
                                >
                                    {{ p.name }}
                                </button>
                            </div>
                        </div>

                        <!-- Material gerado pela IA -->
                        <div class="border border-gray-700 rounded-xl overflow-hidden">
                            <button
                                type="button"
                                @click="showMaterialPicker = !showMaterialPicker"
                                class="w-full flex items-center justify-between px-4 py-3 text-sm text-gray-300 hover:bg-gray-800 transition-colors"
                            >
                                <span class="flex items-center gap-2">
                                    <Sparkles class="w-4 h-4 text-violet-400" />
                                    Material gerado pela IA
                                    <span v-if="actionForm.attach_generation_ids.length" class="text-xs bg-violet-600 text-white px-1.5 py-0.5 rounded-full">
                                        {{ actionForm.attach_generation_ids.length }}
                                    </span>
                                </span>
                                <ChevronUp v-if="showMaterialPicker" class="w-4 h-4 text-gray-500" />
                                <ChevronDown v-else class="w-4 h-4 text-gray-500" />
                            </button>

                            <div v-if="showMaterialPicker" class="px-4 pb-4 border-t border-gray-700">
                                <p class="text-xs text-gray-500 mt-3 mb-3">Selecione gerações existentes para vincular a esta ação.</p>

                                <div v-if="loadingGenerations" class="grid grid-cols-4 gap-2">
                                    <div v-for="i in 8" :key="i" class="aspect-square bg-gray-800 rounded-lg animate-pulse"></div>
                                </div>

                                <div v-else-if="availableGenerations.length === 0" class="text-xs text-gray-500 text-center py-4">
                                    Nenhum material gerado disponível.
                                </div>

                                <div v-else class="grid grid-cols-4 gap-2 max-h-60 overflow-y-auto">
                                    <div
                                        v-for="gen in availableGenerations"
                                        :key="gen.id"
                                        @click="toggleGeneration(gen.id)"
                                        class="relative aspect-square rounded-lg overflow-hidden cursor-pointer bg-gray-800 border-2 transition-colors"
                                        :class="actionForm.attach_generation_ids.includes(gen.id)
                                            ? 'border-violet-500'
                                            : 'border-transparent hover:border-gray-600'"
                                    >
                                        <img
                                            v-if="genThumbnail(gen)"
                                            :src="genThumbnail(gen)"
                                            class="w-full h-full object-cover"
                                        />
                                        <div v-else class="w-full h-full flex items-center justify-center">
                                            <Sparkles class="w-6 h-6 text-gray-600" />
                                        </div>

                                        <!-- Ícone vídeo -->
                                        <div v-if="genIsVideo(gen)" class="absolute inset-0 flex items-center justify-center bg-black/30">
                                            <Film class="w-5 h-5 text-white" />
                                        </div>

                                        <!-- Check de seleção -->
                                        <div
                                            v-if="actionForm.attach_generation_ids.includes(gen.id)"
                                            class="absolute inset-0 bg-violet-600/30 flex items-center justify-center"
                                        >
                                            <div class="w-6 h-6 rounded-full bg-violet-600 flex items-center justify-center">
                                                <Check class="w-3.5 h-3.5 text-white" />
                                            </div>
                                        </div>

                                        <!-- Badge tipo -->
                                        <span class="absolute bottom-1 left-1 text-[10px] bg-black/70 text-white px-1 rounded capitalize">
                                            {{ gen.type }}
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="flex justify-end gap-3 pt-2">
                            <button type="button" @click="showActionModal = false" class="btn-ghost">Cancelar</button>
                            <button type="submit" :disabled="saving" class="btn-primary">
                                {{ saving ? 'Salvando...' : (editingAction ? 'Salvar' : 'Criar Ação') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import { useRoute, RouterLink } from 'vue-router'
import { Plus, Layers, Sparkles, Calendar, Pencil, Trash2, Film, Image, Check, ChevronDown, ChevronUp } from 'lucide-vue-next'
import api from '@/services/api'
import StatusBadge from '@/components/StatusBadge.vue'
import PlatformIcon from '@/components/PlatformIcon.vue'
import TypeBadge from '@/components/TypeBadge.vue'

const route = useRoute()
const campaignId = computed(() => route.params.id)

const campaign = ref(null)
const actions = ref([])
const products = ref([])
const loading = ref(false)
const showActionModal = ref(false)
const saving = ref(false)
const editingAction = ref(null)

const emptyForm = () => ({ title: '', platform: 'instagram', type: 'post', resolution: '1080x1080', brief: '', quantity: 1, product_ids: [], attach_generation_ids: [] })
const actionForm = ref(emptyForm())

const availableGenerations = ref([])
const showMaterialPicker   = ref(false)
const loadingGenerations   = ref(false)

const resolutions = {
    instagram: [
        { value: '1080x1080', label: 'Feed Quadrado (1080x1080)' },
        { value: '1080x1350', label: 'Feed Retrato (1080x1350)' },
        { value: '1080x1920', label: 'Stories/Reels (1080x1920)' },
    ],
    tiktok: [
        { value: '1080x1920', label: 'Vertical (1080x1920)' },
        { value: '1920x1080', label: 'Horizontal (1920x1080)' },
    ],
    facebook: [
        { value: '1200x630', label: 'Link/Compartilhamento (1200x630)' },
        { value: '1080x1080', label: 'Quadrado (1080x1080)' },
    ],
}

async function fetchData() {
    loading.value = true
    const [campaignRes, actionsRes, productsRes] = await Promise.all([
        api.get(`/campaigns/${campaignId.value}`),
        api.get(`/campaigns/${campaignId.value}/actions`),
        api.get('/products?per_page=100'),
    ])
    campaign.value = campaignRes.data
    actions.value = actionsRes.data
    products.value = productsRes.data.data
    loading.value = false
}

async function openActionModal(action = null) {
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
        // Filtra gerações que já têm assets e ainda não estão vinculadas a nenhuma ação
        availableGenerations.value = (data.data || []).filter(g => g.assets?.length > 0)
    } finally {
        loadingGenerations.value = false
    }
}

function toggleGeneration(id) {
    const idx = actionForm.value.attach_generation_ids.indexOf(id)
    if (idx > -1) actionForm.value.attach_generation_ids.splice(idx, 1)
    else actionForm.value.attach_generation_ids.push(id)
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
    const idx = actionForm.value.product_ids.indexOf(id)
    if (idx > -1) actionForm.value.product_ids.splice(idx, 1)
    else actionForm.value.product_ids.push(id)
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

onMounted(fetchData)
</script>

<style scoped>
@reference "tailwindcss";
.input { @apply w-full bg-gray-800 border border-gray-700 rounded-lg px-4 py-2.5 text-white placeholder-gray-500 focus:outline-none focus:border-violet-500 transition-colors text-sm; }
.btn-primary { @apply bg-violet-600 hover:bg-violet-700 disabled:opacity-50 text-white font-medium px-4 py-2 rounded-lg transition-colors text-sm; }
.btn-ghost { @apply text-gray-400 hover:text-white hover:bg-gray-800 font-medium px-4 py-2 rounded-lg transition-colors text-sm; }
</style>





