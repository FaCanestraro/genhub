<template>
    <div class="p-8 max-w-7xl mx-auto w-full">
        <div class="mb-8">
            <p class="text-xs font-semibold tracking-widest uppercase mb-1" style="color: var(--text-muted)">Criação</p>
            <h1 class="text-2xl font-bold text-white tracking-tight leading-tight">Galeria de Modelos</h1>
            <p class="text-sm mt-1" style="color: var(--text-secondary)">Selecione um modelo, escolha um produto e gere sua arte</p>
        </div>

        <!-- Empty -->
        <div v-if="!loading && templates.length === 0" class="text-center py-20">
            <LayoutTemplate class="w-12 h-12 text-gray-600 mx-auto mb-4" />
            <p class="text-gray-400">Nenhum modelo cadastrado ainda.</p>
            <RouterLink to="/templates" class="btn-primary mt-4 inline-flex items-center gap-2">
                <Plus class="w-4 h-4" />
                Criar modelos
            </RouterLink>
        </div>

        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div
                v-for="t in templates"
                :key="t.id"
                @click="selectTemplate(t)"
                class="glass-modal rounded-2xl overflow-hidden cursor-pointer transition-all hover:scale-[1.02]"
                :class="selected?.id === t.id ? 'ring-2 ring-violet-500' : ''"
            >
                <!-- Preview -->
                <div class="relative h-44 bg-white/[0.03]">
                    <video
                        v-if="t.type === 'video' && t.preview_path"
                        :src="`/storage/${t.preview_path}`"
                        class="w-full h-full object-cover"
                        muted loop autoplay playsinline
                    />
                    <img
                        v-else-if="t.preview_path"
                        :src="`/storage/${t.preview_path}`"
                        :alt="t.title"
                        class="w-full h-full object-cover"
                    />
                    <div v-else class="w-full h-full flex flex-col items-center justify-center gap-2 text-gray-600">
                        <Film v-if="t.type === 'video'" class="w-10 h-10" />
                        <ImageIcon v-else class="w-10 h-10" />
                    </div>

                    <div class="absolute top-2 left-2">
                        <span class="text-xs font-medium px-2 py-0.5 rounded-full" :class="t.type === 'video' ? 'bg-blue-500/20 text-blue-300' : 'bg-violet-500/20 text-violet-300'">
                            {{ t.type === 'video' ? '🎬 Vídeo' : '🖼 Imagem' }}
                        </span>
                    </div>

                    <div v-if="selected?.id === t.id" class="absolute inset-0 bg-violet-600/20 flex items-center justify-center">
                        <div class="w-10 h-10 rounded-full bg-violet-600 flex items-center justify-center shadow-lg">
                            <Check class="w-5 h-5 text-white" />
                        </div>
                    </div>
                </div>

                <div class="p-4">
                    <h3 class="font-semibold text-white leading-tight">{{ t.title }}</h3>
                    <p class="text-xs text-gray-500 mt-1.5 line-clamp-2 leading-relaxed">{{ t.prompt }}</p>
                </div>
            </div>
        </div>

        <!-- Painel inferior: selecionar produto e gerar -->
        <Transition enter-from-class="opacity-0 translate-y-4" enter-active-class="transition duration-300" leave-to-class="opacity-0 translate-y-4" leave-active-class="transition duration-200">
            <div v-if="selected" class="fixed bottom-6 left-1/2 -translate-x-1/2 w-full max-w-2xl px-4 z-40">
                <div class="glass-modal rounded-2xl p-5 shadow-2xl">
                    <div class="flex items-center gap-3 mb-4">
                        <div class="w-8 h-8 rounded-xl bg-violet-600 flex items-center justify-center flex-shrink-0">
                            <Sparkles class="w-4 h-4 text-white" />
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-semibold text-white leading-tight">{{ selected.title }}</p>
                            <p class="text-xs text-gray-500 truncate">{{ selected.prompt }}</p>
                        </div>
                        <button @click="selected = null" class="ml-auto p-1.5 text-gray-500 hover:text-white rounded-lg transition-colors flex-shrink-0">
                            <X class="w-4 h-4" />
                        </button>
                    </div>

                    <div class="grid grid-cols-2 gap-3 mb-3">
                        <div>
                            <label class="block text-xs text-gray-400 mb-1.5">Resolução</label>
                            <select v-model="selectedResolution" class="input text-sm">
                                <option value="1080x1080">1080×1080 — Feed quadrado</option>
                                <option value="1080x1350">1080×1350 — Feed retrato</option>
                                <option value="1080x1920">1080×1920 — Stories / Reels</option>
                                <option value="1920x1080">1920×1080 — Paisagem / YouTube</option>
                                <option value="1200x628">1200×628 — Facebook / LinkedIn</option>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs text-gray-400 mb-1.5">Produto (opcional)</label>
                            <select v-model="selectedProductId" class="input text-sm">
                                <option value="">Sem produto específico</option>
                                <option v-for="p in products" :key="p.id" :value="p.id">
                                    {{ p.name }}{{ p.category ? ` · ${p.category}` : '' }}
                                </option>
                            </select>
                        </div>
                    </div>
                    <button
                        @click="generate"
                        :disabled="generating"
                        class="btn-primary w-full flex items-center justify-center gap-2 py-2.5"
                    >
                        <Loader2 v-if="generating" class="w-4 h-4 animate-spin" />
                        <Sparkles v-else class="w-4 h-4" />
                        {{ generating ? 'Gerando...' : `Gerar ${selected.type === 'video' ? 'Vídeo' : 'Imagem'}` }}
                    </button>
                </div>
            </div>
        </Transition>

        <!-- Modal de resultado -->
        <Teleport to="body">
            <div v-if="result" class="fixed inset-0 bg-black/50 backdrop-blur-xl flex items-center justify-center z-50 p-4" @click.self="result = null">
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

                    <button @click="result = null" class="mt-4 btn-ghost w-full">Fechar</button>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import { Film, Image as ImageIcon, Check, X, Sparkles, Loader2, Download, LayoutTemplate, Plus } from 'lucide-vue-next'
import api from '@/services/api'

const templates = ref([])
const products = ref([])
const loading = ref(false)
const selected = ref(null)
const selectedProductId = ref('')
const selectedResolution = ref('1080x1080')
const generating = ref(false)
const result = ref(null)

async function fetchData() {
    loading.value = true
    const [tRes, pRes] = await Promise.all([
        api.get('/templates'),
        api.get('/products', { params: { per_page: 100 } }),
    ])
    templates.value = tRes.data
    products.value = pRes.data.data ?? pRes.data
    loading.value = false
}

function selectTemplate(t) {
    selected.value = selected.value?.id === t.id ? null : t
    selectedProductId.value = ''
}

async function generate() {
    if (!selected.value || generating.value) return
    generating.value = true
    try {
        const product = products.value.find(p => p.id == selectedProductId.value)

        let brief = selected.value.prompt
        if (product) {
            brief += `\n\nProduto: ${product.name}`
            if (product.description) brief += `\nDescrição: ${product.description}`
            if (product.category) brief += `\nCategoria: ${product.category}`
            if (product.price) brief += `\nPreço: R$ ${Number(product.price).toFixed(2)}`
            if (product.price_discount) brief += ` (desconto: R$ ${Number(product.price_discount).toFixed(2)})`
        }

        const contentType = selected.value.type === 'video' ? 'reel' : 'post'

        const { data } = await api.post('/generate', {
            type:         selected.value.type,
            platform:     'instagram',
            content_type: contentType,
            resolution:   selectedResolution.value,
            brief,
            product_ids:  product ? [product.id] : [],
        })

        const urls = data.assets?.map(a => `/storage/${a.path}`) ?? []
        result.value = { type: selected.value.type, urls }
    } catch (e) {
        alert(e.response?.data?.message || 'Erro na geração.')
    } finally {
        generating.value = false
    }
}

onMounted(fetchData)
</script>

<style scoped>
.btn-primary {
    background-color: #7c3aed;
    color: #fff;
    font-weight: 500;
    font-size: 0.875rem;
    padding: 0.5rem 1rem;
    border-radius: 0.5rem;
    transition: background-color 0.18s, opacity 0.18s;
}
.btn-primary:hover { background-color: #6d28d9; }
.btn-primary:disabled { opacity: 0.5; }

.btn-ghost {
    color: #9ca3af;
    font-weight: 500;
    font-size: 0.875rem;
    padding: 0.5rem 1rem;
    border-radius: 0.5rem;
    background: transparent;
    transition: background 0.18s, color 0.18s;
}
.btn-ghost:hover { color: #fff; background: rgba(255,255,255,0.055); }
</style>
