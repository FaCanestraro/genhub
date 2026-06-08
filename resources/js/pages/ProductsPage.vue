<template>
    <div class="p-8 max-w-7xl mx-auto w-full">
        <div class="flex items-center justify-between mb-8">
            <div>
                <p class="text-xs font-semibold tracking-widest uppercase mb-1" style="color: var(--text-muted)">Catálogo</p>
                <h1 class="text-2xl font-bold text-white tracking-tight leading-tight">Produtos</h1>
                <p class="text-sm mt-1" style="color: var(--text-secondary)">Gerencie os produtos para geração de conteúdo</p>
            </div>
            <div class="flex items-center gap-2">
                <button @click="openIntegrationModal()" class="btn-ghost flex items-center gap-2 border border-white/10">
                    <Plug class="w-4 h-4" />
                    Integrações
                    <span v-if="integrations.length" class="text-xs bg-white/10 px-1.5 py-0.5 rounded-full">{{ integrations.length }}</span>
                </button>
                <button @click="openModal()" class="btn-primary flex items-center gap-2">
                    <Plus class="w-4 h-4" />
                    Novo Produto
                </button>
            </div>
        </div>

        <!-- Integrações ativas -->
        <div v-if="integrations.length" class="mb-6 flex flex-wrap gap-3">
            <div
                v-for="intg in integrations"
                :key="intg.id"
                class="flex items-center gap-3 glass-panel rounded-xl px-4 py-2.5"
            >
                <span class="text-xl">{{ intg.system.icon }}</span>
                <div>
                    <p class="text-sm font-medium text-white leading-tight">{{ intg.system.name }}</p>
                    <p class="text-xs text-gray-500">{{ intg.env === 'production' ? 'Produção' : 'Sandbox' }} · Sincronização {{ syncLabel(intg.sync) }}</p>
                </div>
                <span class="w-2 h-2 rounded-full bg-green-400 ml-1 shadow-[0_0_6px_#4ade80]"></span>
                <button @click="removeIntegration(intg.id)" class="ml-1 p-1 text-gray-600 hover:text-red-400 rounded-lg transition-colors">
                    <X class="w-3.5 h-3.5" />
                </button>
            </div>
        </div>

        <!-- Empty -->
        <div v-if="!loading && products.length === 0" class="text-center py-20">
            <Package class="w-12 h-12 text-gray-600 mx-auto mb-4" />
            <p class="text-gray-400">Nenhum produto cadastrado ainda.</p>
            <button @click="openModal()" class="btn-primary mt-4">Adicionar produto</button>
        </div>

        <!-- Grid -->
        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div
                v-for="p in products"
                :key="p.id"
                class="glass-modal rounded-2xl overflow-hidden list-row"
            >
                <!-- Product image -->
                <div class="relative h-40 bg-gray-800">
                    <img
                        v-if="p.images && p.images.length"
                        :src="`/storage/${p.images[0]}`"
                        :alt="p.name"
                        class="w-full h-full object-cover"
                    />
                    <div v-else class="w-full h-full flex items-center justify-center">
                        <Package class="w-10 h-10 text-gray-600" />
                    </div>
                    <div class="absolute top-2 right-2 flex gap-1">
                        <button @click="openModal(p)" class="p-1.5 text-gray-400 hover:text-white bg-black/50 hover:bg-black/80 rounded-lg transition-colors backdrop-blur-sm">
                            <Pencil class="w-4 h-4" />
                        </button>
                        <button @click="deleteProduct(p)" class="p-1.5 text-gray-400 hover:text-red-400 bg-black/50 hover:bg-black/80 rounded-lg transition-colors backdrop-blur-sm">
                            <Trash2 class="w-4 h-4" />
                        </button>
                    </div>
                </div>

                <div class="p-5">
                    <h3 class="font-semibold text-white">{{ p.name }}</h3>
                    <div class="flex items-center gap-2 mt-1">
                        <p class="text-xs text-gray-400">{{ p.category || 'Sem categoria' }}</p>
                        <span v-if="p.sku" class="text-xs text-gray-600 font-mono bg-gray-800 px-1.5 py-0.5 rounded">{{ p.sku }}</span>
                    </div>
                    <p class="text-sm text-gray-300 mt-2 line-clamp-2">{{ p.description }}</p>
                    <div v-if="p.price" class="flex items-center gap-2 mt-3">
                        <p class="font-semibold" :class="p.price_discount ? 'text-gray-500 line-through text-sm' : 'text-violet-400'">R$ {{ numberToCurrency(p.price) }}</p>
                        <p v-if="p.price_discount" class="text-violet-400 font-semibold">R$ {{ numberToCurrency(p.price_discount) }}</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal de Integrações -->
        <Teleport to="body">
            <div v-if="showIntegrationModal" class="fixed inset-0 bg-black/30 backdrop-blur-xl flex items-center justify-center z-50 p-4" @click.self="showIntegrationModal = false">
                <div class="glass-modal rounded-2xl w-full max-w-xl p-6 max-h-[90vh] overflow-y-auto">

                    <!-- Sucesso -->
                    <div v-if="integrationSuccess" class="flex flex-col items-center gap-4 py-8">
                        <div class="w-16 h-16 rounded-full bg-green-500/20 flex items-center justify-center">
                            <Check class="w-8 h-8 text-green-400" />
                        </div>
                        <p class="text-white font-semibold text-lg">Integração conectada!</p>
                        <p class="text-gray-400 text-sm">{{ selectedSystem?.name }} foi adicionado com sucesso.</p>
                    </div>

                    <template v-else>
                        <div class="flex items-center justify-between mb-5">
                            <div class="flex items-center gap-3">
                                <button v-if="integrationStep === 2" @click="integrationStep = 1" class="p-1.5 text-gray-500 hover:text-white rounded-lg transition-colors">
                                    <ChevronRight class="w-4 h-4 rotate-180" />
                                </button>
                                <h2 class="text-lg font-semibold text-white">
                                    {{ integrationStep === 1 ? 'Conectar Sistema' : selectedSystem?.name }}
                                </h2>
                            </div>
                            <button @click="showIntegrationModal = false" class="p-1.5 text-gray-500 hover:text-white rounded-lg transition-colors">
                                <X class="w-4 h-4" />
                            </button>
                        </div>

                        <!-- Step 1: escolher sistema -->
                        <div v-if="integrationStep === 1">
                            <p class="text-sm text-gray-400 mb-4">Selecione o sistema de gestão de produtos que deseja integrar.</p>
                            <div class="grid grid-cols-2 sm:grid-cols-3 gap-3">
                                <button
                                    v-for="sys in SYSTEMS"
                                    :key="sys.id"
                                    @click="pickSystem(sys)"
                                    class="flex flex-col items-center gap-2 p-4 rounded-xl border border-white/10 bg-white/[0.03] hover:bg-white/[0.07] hover:border-violet-500/50 transition-all text-center group"
                                >
                                    <span class="text-3xl">{{ sys.icon }}</span>
                                    <span class="text-sm font-medium text-gray-300 group-hover:text-white transition-colors">{{ sys.name }}</span>
                                </button>
                            </div>
                        </div>

                        <!-- Step 2: formulário -->
                        <div v-else>
                            <div class="flex items-center gap-3 mb-5 p-3 rounded-xl bg-white/[0.04] border border-white/[0.07]">
                                <span class="text-2xl">{{ selectedSystem?.icon }}</span>
                                <div>
                                    <p class="text-sm font-medium text-white">{{ selectedSystem?.name }}</p>
                                    <p class="text-xs text-gray-500">Preencha as credenciais de acesso</p>
                                </div>
                            </div>

                            <form @submit.prevent="saveIntegration" class="space-y-4">
                                <div>
                                    <label class="block text-sm text-gray-400 mb-1">Chave de API *</label>
                                    <input v-model="integrationForm.api_key" type="text" required class="input font-mono" placeholder="sk-••••••••••••" />
                                </div>

                                <div>
                                    <label class="block text-sm text-gray-400 mb-1">{{ selectedSystem?.urlLabel ?? 'URL da API' }}</label>
                                    <input v-model="integrationForm.api_url" type="url" class="input" placeholder="https://api.exemplo.com.br" />
                                </div>

                                <div v-if="selectedSystem?.hasSecret">
                                    <label class="block text-sm text-gray-400 mb-1">Secret / Token</label>
                                    <input v-model="integrationForm.api_secret" type="password" class="input" placeholder="••••••••••••" />
                                </div>

                                <div class="grid grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-sm text-gray-400 mb-1">Ambiente</label>
                                        <select v-model="integrationForm.env" class="input">
                                            <option value="production">Produção</option>
                                            <option value="sandbox">Sandbox</option>
                                        </select>
                                    </div>
                                    <div>
                                        <label class="block text-sm text-gray-400 mb-1">Sincronização</label>
                                        <select v-model="integrationForm.sync" class="input">
                                            <option value="manual">Manual</option>
                                            <option value="hourly">A cada hora</option>
                                            <option value="daily">Diária</option>
                                        </select>
                                    </div>
                                </div>

                                <div class="flex justify-end gap-3 pt-2">
                                    <button type="button" @click="showIntegrationModal = false" class="btn-ghost">Cancelar</button>
                                    <button type="submit" :disabled="savingIntegration || !integrationForm.api_key" class="btn-primary flex items-center gap-2">
                                        <span v-if="savingIntegration" class="w-4 h-4 border-2 border-white/30 border-t-white rounded-full animate-spin"></span>
                                        {{ savingIntegration ? 'Conectando...' : 'Conectar' }}
                                    </button>
                                </div>
                            </form>
                        </div>
                    </template>
                </div>
            </div>
        </Teleport>

        <!-- Modal -->
        <Teleport to="body">
            <div v-if="showModal" class="fixed inset-0 bg-black/30 backdrop-blur-xl flex items-center justify-center z-50 p-4" @click.self="showModal = false">
                <div class="glass-modal rounded-2xl w-full max-w-lg p-6 max-h-[90vh] overflow-y-auto">
                    <h2 class="text-lg font-semibold text-white mb-5">{{ editingProduct ? 'Editar' : 'Novo' }} Produto</h2>

                    <form @submit.prevent="saveProduct" class="space-y-4">

                        <!-- Image upload -->
                        <div>
                            <label class="block text-sm text-gray-400 mb-2">Imagem do produto</label>
                            <div
                                class="image-drop-area"
                                :class="{ 'border-violet-500': dragOver }"
                                @dragover.prevent="dragOver = true"
                                @dragleave="dragOver = false"
                                @drop.prevent="onDrop"
                                @click="$refs.fileInput.click()"
                            >
                                <img v-if="imagePreview" :src="imagePreview" class="w-full h-full object-cover rounded-lg" />
                                <div v-else class="flex flex-col items-center gap-2 text-gray-500">
                                    <ImageIcon class="w-8 h-8" />
                                    <span class="text-sm">Clique ou arraste uma imagem</span>
                                </div>
                            </div>
                            <input ref="fileInput" type="file" accept="image/*" class="hidden" @change="onFileChange" />
                            <button v-if="imagePreview" type="button" @click="clearImage" class="text-xs text-red-400 hover:text-red-300 mt-1">
                                Remover imagem
                            </button>
                        </div>

                        <div>
                            <label class="block text-sm text-gray-400 mb-1">Nome *</label>
                            <input v-model="form.name" type="text" required class="input" placeholder="Nome do produto" />
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm text-gray-400 mb-1">Categoria</label>
                                <input v-model="form.category" type="text" class="input" placeholder="Ex: Roupas" />
                            </div>
                            <div>
                                <label class="block text-sm text-gray-400 mb-1">Preço (R$)</label>
                                <input
                                    :value="numberToCurrency(form.price)"
                                    @input="e => onCurrencyInput(e, v => form.price = v)"
                                    type="text"
                                    inputmode="numeric"
                                    class="input"
                                    placeholder="0,00"
                                />
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-sm text-gray-400 mb-1">Preço com Desconto (R$)</label>
                                <input
                                    :value="numberToCurrency(form.price_discount)"
                                    @input="e => onCurrencyInput(e, v => form.price_discount = v)"
                                    type="text"
                                    inputmode="numeric"
                                    class="input"
                                    placeholder="0,00"
                                />
                            </div>
                            <div>
                                <label class="block text-sm text-gray-400 mb-1">SKU / EAN</label>
                                <input v-model="form.sku" type="text" class="input" placeholder="Ex: 7891234567890" />
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm text-gray-400 mb-1">Descrição</label>
                            <textarea v-model="form.description" rows="3" class="input resize-none" placeholder="Descreva o produto para ajudar a IA..."></textarea>
                        </div>
                        <div>
                            <label class="block text-sm text-gray-400 mb-1">URL do produto</label>
                            <input v-model="form.url" type="url" class="input" placeholder="https://..." />
                        </div>

                        <div class="flex justify-end gap-3 pt-2">
                            <button type="button" @click="showModal = false" class="btn-ghost">Cancelar</button>
                            <button type="submit" :disabled="saving" class="btn-primary">
                                {{ saving ? 'Salvando...' : 'Salvar' }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </Teleport>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { Plus, Package, Pencil, Trash2, Image as ImageIcon, Plug, X, Check, ChevronRight } from 'lucide-vue-next'
import api from '@/services/api'
import { numberToCurrency, onCurrencyInput } from '@/utils/mask'

const products = ref([])
const loading = ref(false)
const showModal = ref(false)
const saving = ref(false)
const editingProduct = ref(null)
const form = ref({ name: '', sku: '', description: '', category: '', price: '', price_discount: '', url: '' })

// Integrations
const integrations = ref(JSON.parse(localStorage.getItem('product_integrations') || '[]'))
const showIntegrationModal = ref(false)
const savingIntegration = ref(false)
const integrationSuccess = ref(false)
const integrationStep = ref(1) // 1 = pick system, 2 = fill form
const selectedSystem = ref(null)
const integrationForm = ref({ api_key: '', api_url: '', api_secret: '', env: 'production', sync: 'daily' })

const SYSTEMS = [
    { id: 'linx',       name: 'Linx',         icon: '🔗', urlLabel: 'URL da API',    hasSecret: false },
    { id: 'totvs',      name: 'TOTVS',         icon: '🏢', urlLabel: 'URL do TOTVS',  hasSecret: true  },
    { id: 'sults',      name: 'SULTS',         icon: '📊', urlLabel: 'URL de Acesso',  hasSecret: false },
    { id: 'oracle',     name: 'Oracle Retail', icon: '☁️', urlLabel: 'Endpoint Oracle', hasSecret: true  },
    { id: 'sap',        name: 'SAP',           icon: '💼', urlLabel: 'SAP Gateway URL', hasSecret: true  },
    { id: 'bling',      name: 'Bling',         icon: '📦', urlLabel: 'URL da API',    hasSecret: false },
    { id: 'tiny',       name: 'Tiny ERP',      icon: '🟢', urlLabel: 'URL da API',    hasSecret: false },
    { id: 'woocommerce',name: 'WooCommerce',   icon: '🛒', urlLabel: 'URL da Loja',   hasSecret: true  },
    { id: 'shopify',    name: 'Shopify',       icon: '🛍️', urlLabel: 'URL da Loja',   hasSecret: true  },
    { id: 'outro',      name: 'Outro',         icon: '⚙️', urlLabel: 'URL da API',    hasSecret: true  },
]

function openIntegrationModal() {
    integrationStep.value = 1
    selectedSystem.value = null
    integrationSuccess.value = false
    integrationForm.value = { api_key: '', api_url: '', api_secret: '', env: 'production', sync: 'daily' }
    showIntegrationModal.value = true
}

function pickSystem(sys) {
    selectedSystem.value = sys
    integrationStep.value = 2
}

async function saveIntegration() {
    savingIntegration.value = true
    await new Promise(r => setTimeout(r, 1400))
    const intg = {
        id: Date.now(),
        system: selectedSystem.value,
        api_key: integrationForm.value.api_key,
        api_url: integrationForm.value.api_url,
        env: integrationForm.value.env,
        sync: integrationForm.value.sync,
    }
    integrations.value.push(intg)
    localStorage.setItem('product_integrations', JSON.stringify(integrations.value))
    savingIntegration.value = false
    integrationSuccess.value = true
    setTimeout(() => { showIntegrationModal.value = false; integrationSuccess.value = false }, 1800)
}

function removeIntegration(id) {
    integrations.value = integrations.value.filter(i => i.id !== id)
    localStorage.setItem('product_integrations', JSON.stringify(integrations.value))
}

function syncLabel(sync) {
    return { manual: 'Manual', hourly: 'A cada hora', daily: 'Diária' }[sync] ?? sync
}

const fileInput = ref(null)
const imageFile = ref(null)
const imagePreview = ref(null)
const dragOver = ref(false)

async function fetchProducts() {
    loading.value = true
    const { data } = await api.get('/products')
    products.value = data.data
    loading.value = false
}

function openModal(product = null) {
    editingProduct.value = product
    form.value = product
        ? { name: product.name, sku: product.sku || '', description: product.description || '', category: product.category || '', price: product.price || '', price_discount: product.price_discount || '', url: product.url || '' }
        : { name: '', sku: '', description: '', category: '', price: '', price_discount: '', url: '' }
    imageFile.value = null
    imagePreview.value = product?.images?.length ? `/storage/${product.images[0]}` : null
    showModal.value = true
}

function onFileChange(e) {
    const file = e.target.files[0]
    if (file) setImageFile(file)
}

function onDrop(e) {
    dragOver.value = false
    const file = e.dataTransfer.files[0]
    if (file && file.type.startsWith('image/')) setImageFile(file)
}

function setImageFile(file) {
    imageFile.value = file
    imagePreview.value = URL.createObjectURL(file)
}

function clearImage() {
    imageFile.value = null
    imagePreview.value = null
    if (fileInput.value) fileInput.value.value = ''
}

async function saveProduct() {
    saving.value = true
    try {
        let product
        if (editingProduct.value) {
            const { data } = await api.put(`/products/${editingProduct.value.id}`, form.value)
            product = data
        } else {
            const { data } = await api.post('/products', form.value)
            product = data
        }

        if (imageFile.value) {
            const fd = new FormData()
            fd.append('image', imageFile.value)
            await api.post(`/products/${product.id}/images`, fd, {
                headers: { 'Content-Type': 'multipart/form-data' },
            })
        }

        showModal.value = false
        fetchProducts()
    } finally {
        saving.value = false
    }
}

async function deleteProduct(product) {
    if (!confirm(`Excluir "${product.name}"?`)) return
    await api.delete(`/products/${product.id}`)
    fetchProducts()
}

onMounted(fetchProducts)
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
.btn-ghost:hover {
    color: #fff;
    background: rgba(255, 255, 255, 0.055);
}

.image-drop-area {
    position: relative;
    width: 100%;
    height: 10rem;
    border-radius: 0.75rem;
    display: flex;
    align-items: center;
    justify-content: center;
    cursor: pointer;
    overflow: hidden;
    background: rgba(255, 255, 255, 0.03);
    border: 1.5px dashed rgba(255, 255, 255, 0.13);
    box-shadow: inset 0 1px 0 rgba(255, 255, 255, 0.05);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    transition: border-color 0.18s, background 0.18s;
}
.image-drop-area:hover {
    background: rgba(255, 255, 255, 0.055);
    border-color: rgba(255, 255, 255, 0.22);
}
.image-drop-area:hover {
    background: rgba(255, 255, 255, 0.055);
    border-color: rgba(255, 255, 255, 0.22);
}
</style>





