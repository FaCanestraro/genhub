<template>
    <div class="p-8 max-w-7xl mx-auto w-full">
        <div class="flex items-center justify-between mb-8">
            <div>
                <p class="text-xs font-semibold tracking-widest uppercase mb-1" style="color: var(--text-muted)">Criação</p>
                <h1 class="text-2xl font-bold text-white tracking-tight leading-tight">Modelos de Arte</h1>
                <p class="text-sm mt-1" style="color: var(--text-secondary)">Cadastre prompts e prévias para geração rápida</p>
            </div>
            <button @click="openModal()" class="btn-primary flex items-center gap-2">
                <Plus class="w-4 h-4" />
                Novo Modelo
            </button>
        </div>

        <!-- Empty -->
        <div v-if="!loading && templates.length === 0" class="text-center py-20">
            <LayoutTemplate class="w-12 h-12 text-gray-600 mx-auto mb-4" />
            <p class="text-gray-400">Nenhum modelo cadastrado ainda.</p>
            <button @click="openModal()" class="btn-primary mt-4">Criar modelo</button>
        </div>

        <!-- Grid -->
        <div v-else class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
            <div
                v-for="t in templates"
                :key="t.id"
                class="glass-modal rounded-2xl overflow-hidden list-row"
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
                        <span class="text-xs">Sem prévia</span>
                    </div>
                    <div class="absolute top-2 left-2">
                        <span class="text-xs font-medium px-2 py-0.5 rounded-full" :class="t.type === 'video' ? 'bg-blue-500/20 text-blue-300' : 'bg-violet-500/20 text-violet-300'">
                            {{ t.type === 'video' ? '🎬 Vídeo' : '🖼 Imagem' }}
                        </span>
                    </div>
                    <div class="absolute top-2 right-2 flex gap-1">
                        <button @click="openModal(t)" class="p-1.5 text-gray-400 hover:text-white bg-black/50 hover:bg-black/80 rounded-lg transition-colors backdrop-blur-sm">
                            <Pencil class="w-4 h-4" />
                        </button>
                        <button @click="deleteTemplate(t)" class="p-1.5 text-gray-400 hover:text-red-400 bg-black/50 hover:bg-black/80 rounded-lg transition-colors backdrop-blur-sm">
                            <Trash2 class="w-4 h-4" />
                        </button>
                    </div>
                </div>

                <div class="p-4">
                    <h3 class="font-semibold text-white leading-tight">{{ t.title }}</h3>
                    <p class="text-xs text-gray-500 mt-1.5 line-clamp-3 leading-relaxed">{{ t.prompt }}</p>
                </div>
            </div>
        </div>

        <!-- Modal -->
        <Teleport to="body">
            <div v-if="showModal" class="fixed inset-0 bg-black/30 backdrop-blur-xl flex items-center justify-center z-50 p-4" @click.self="showModal = false">
                <div class="glass-modal rounded-2xl w-full max-w-lg p-6 max-h-[90vh] overflow-y-auto">
                    <h2 class="text-lg font-semibold text-white mb-5">{{ editing ? 'Editar' : 'Novo' }} Modelo</h2>

                    <form @submit.prevent="save" class="space-y-4">
                        <div>
                            <label class="block text-sm text-gray-400 mb-1">Título *</label>
                            <input v-model="form.title" type="text" required class="input" placeholder="Ex: Post de Produto com Fundo Branco" />
                        </div>

                        <div>
                            <label class="block text-sm text-gray-400 mb-1">Tipo *</label>
                            <div class="grid grid-cols-2 gap-2">
                                <button
                                    v-for="opt in [{ value: 'image', label: '🖼 Imagem' }, { value: 'video', label: '🎬 Vídeo' }]"
                                    :key="opt.value"
                                    type="button"
                                    @click="form.type = opt.value"
                                    :class="form.type === opt.value ? 'bg-violet-600 text-white border-violet-500' : 'text-gray-400 hover:text-white'"
                                    :style="form.type !== opt.value ? { background: 'rgba(255,255,255,0.045)', border: '1px solid rgba(255,255,255,0.10)' } : {}"
                                    class="py-2 px-3 rounded-lg text-sm font-medium transition-colors border"
                                >
                                    {{ opt.label }}
                                </button>
                            </div>
                        </div>

                        <div>
                            <label class="block text-sm text-gray-400 mb-1">Prompt *</label>
                            <textarea v-model="form.prompt" rows="5" required class="input resize-none" placeholder="Descreva o estilo visual, cenário, iluminação, composição..."></textarea>
                        </div>

                        <!-- Preview upload -->
                        <div>
                            <label class="block text-sm text-gray-400 mb-2">Prévia (imagem ou vídeo)</label>
                            <div
                                class="preview-drop"
                                :class="{ 'border-violet-500': dragOver }"
                                @dragover.prevent="dragOver = true"
                                @dragleave="dragOver = false"
                                @drop.prevent="onDrop"
                                @click="$refs.fileInput.click()"
                            >
                                <video v-if="previewIsVideo && previewUrl" :src="previewUrl" class="w-full h-full object-cover rounded-lg" muted loop autoplay playsinline />
                                <img v-else-if="previewUrl" :src="previewUrl" class="w-full h-full object-cover rounded-lg" />
                                <div v-else class="flex flex-col items-center gap-2 text-gray-500">
                                    <ImageIcon class="w-8 h-8" />
                                    <span class="text-sm">Clique ou arraste um arquivo</span>
                                    <span class="text-xs text-gray-600">PNG, JPG, GIF, MP4, MOV</span>
                                </div>
                            </div>
                            <input ref="fileInput" type="file" accept="image/*,video/*" class="hidden" @change="onFileChange" />
                            <button v-if="previewUrl" type="button" @click="clearPreview" class="text-xs text-red-400 hover:text-red-300 mt-1">Remover prévia</button>
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
import { ref, computed, onMounted } from 'vue'
import { Plus, Pencil, Trash2, Image as ImageIcon, Film, LayoutTemplate } from 'lucide-vue-next'
import api from '@/services/api'

const templates = ref([])
const loading = ref(false)
const showModal = ref(false)
const saving = ref(false)
const editing = ref(null)
const dragOver = ref(false)
const fileInput = ref(null)
const previewFile = ref(null)
const previewUrl = ref(null)

const form = ref({ title: '', prompt: '', type: 'image' })

const previewIsVideo = computed(() => {
    if (previewFile.value) return previewFile.value.type.startsWith('video/')
    if (editing.value?.preview_path) return /\.(mp4|mov|webm)$/i.test(editing.value.preview_path)
    return false
})

async function fetchTemplates() {
    loading.value = true
    const { data } = await api.get('/templates')
    templates.value = data
    loading.value = false
}

function openModal(t = null) {
    editing.value = t
    form.value = t
        ? { title: t.title, prompt: t.prompt, type: t.type }
        : { title: '', prompt: '', type: 'image' }
    previewFile.value = null
    previewUrl.value = t?.preview_path ? `/storage/${t.preview_path}` : null
    showModal.value = true
}

function onFileChange(e) {
    const file = e.target.files[0]
    if (file) setFile(file)
}

function onDrop(e) {
    dragOver.value = false
    const file = e.dataTransfer.files[0]
    if (file) setFile(file)
}

function setFile(file) {
    previewFile.value = file
    previewUrl.value = URL.createObjectURL(file)
}

function clearPreview() {
    previewFile.value = null
    previewUrl.value = null
    if (fileInput.value) fileInput.value.value = ''
}

async function save() {
    saving.value = true
    try {
        let template
        if (editing.value) {
            const { data } = await api.put(`/templates/${editing.value.id}`, form.value)
            template = data
        } else {
            const { data } = await api.post('/templates', form.value)
            template = data
        }

        if (previewFile.value) {
            const fd = new FormData()
            fd.append('file', previewFile.value)
            await api.post(`/templates/${template.id}/preview`, fd, {
                headers: { 'Content-Type': 'multipart/form-data' },
            })
        }

        showModal.value = false
        fetchTemplates()
    } finally {
        saving.value = false
    }
}

async function deleteTemplate(t) {
    if (!confirm(`Excluir "${t.title}"?`)) return
    await api.delete(`/templates/${t.id}`)
    fetchTemplates()
}

onMounted(fetchTemplates)
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

.preview-drop {
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
    transition: border-color 0.18s, background 0.18s;
}
.preview-drop:hover, .preview-drop.border-violet-500 {
    background: rgba(255, 255, 255, 0.055);
    border-color: rgba(124, 58, 237, 0.6);
}
</style>
