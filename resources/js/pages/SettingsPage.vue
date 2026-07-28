<template>
    <div class="flex h-full">
        <!-- Sidebar -->
        <aside class="w-56 flex-shrink-0 border-r border-gray-800 p-4">
            <h2 class="text-xs font-semibold text-gray-500 uppercase tracking-wider px-3 mb-3">Configurações</h2>
            <nav class="space-y-0.5">
                <button
                    v-for="item in sections"
                    :key="item.id"
                    @click="active = item.id"
                    class="w-full flex items-center gap-2.5 px-3 py-2 rounded-lg text-sm transition-colors text-left"
                    :class="active === item.id ? 'text-white' : 'text-gray-400 hover:text-white hover:bg-gray-800'"
                    :style="active === item.id ? { backgroundColor: settingsStore.primaryColor } : {}"
                >
                    <component :is="item.icon" class="w-4 h-4 flex-shrink-0" />
                    {{ item.label }}
                </button>
            </nav>
        </aside>

        <!-- Content -->
        <div class="flex-1 overflow-y-auto p-8">
            <!-- Geral -->
            <section v-if="active === 'geral'">
                <div class="mb-6">
                    <h1 class="text-xl font-bold text-white">Configurações Gerais</h1>
                    <p class="text-gray-400 text-sm mt-1">Personalize o comportamento do sistema.</p>
                </div>

                <div v-if="loadingSettings" class="flex items-center justify-center py-20">
                    <Loader2 class="w-6 h-6 animate-spin" :style="{ color: settingsStore.primaryColor }" />
                </div>

                <form v-else @submit.prevent="saveGeneral" class="space-y-6 max-w-2xl">
                    <!-- Empresa -->
                    <div class="card">
                        <h3 class="section-title">Empresa</h3>
                        <div class="grid grid-cols-2 gap-4 mt-4">
                            <div class="col-span-2">
                                <label class="label">Nome da Empresa</label>
                                <input v-model="general.nome_empresa" class="input" placeholder="Razão social ou nome fantasia" />
                            </div>
                            <div class="col-span-2">
                                <label class="label">CNPJ</label>
                                <input
                                    :value="general.cnpj"
                                    @input="e => onCNPJInput(e, v => general.cnpj = v)"
                                    type="text"
                                    inputmode="numeric"
                                    class="input"
                                    placeholder="00.000.000/0000-00"
                                    maxlength="18"
                                />
                            </div>
                            <div>
                                <label class="label">Moeda Padrão</label>
                                <select v-model="general.moeda" class="input">
                                    <option value="BRL">BRL — Real brasileiro</option>
                                    <option value="USD">USD — Dólar americano</option>
                                    <option value="EUR">EUR — Euro</option>
                                </select>
                            </div>
                            <div>
                                <label class="label">Fuso Horário</label>
                                <select v-model="general.fuso_horario" class="input">
                                    <option value="America/Sao_Paulo">América/São Paulo (GMT-3)</option>
                                    <option value="America/Manaus">América/Manaus (GMT-4)</option>
                                    <option value="America/Belem">América/Belém (GMT-3)</option>
                                    <option value="America/Fortaleza">América/Fortaleza (GMT-3)</option>
                                    <option value="America/Recife">América/Recife (GMT-3)</option>
                                    <option value="America/Maceio">América/Maceió (GMT-3)</option>
                                    <option value="America/Bahia">América/Salvador (GMT-3)</option>
                                    <option value="America/Cuiaba">América/Cuiabá (GMT-4)</option>
                                    <option value="America/Porto_Velho">América/Porto Velho (GMT-4)</option>
                                    <option value="America/Rio_Branco">América/Rio Branco (GMT-5)</option>
                                    <option value="America/Noronha">América/Noronha (GMT-2)</option>
                                </select>
                            </div>
                        </div>
                    </div>

                    <!-- Leads -->
                    <div class="card">
                        <h3 class="section-title">Leads</h3>
                        <div class="space-y-4 mt-4">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-white">Auto-atribuir leads</p>
                                    <p class="text-xs text-gray-500 mt-0.5">Distribui novos leads automaticamente</p>
                                </div>
                                <button type="button" @click="general.auto_atribuir_leads = !general.auto_atribuir_leads"
                                    class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors flex-shrink-0"
                                    :style="general.auto_atribuir_leads ? { backgroundColor: settingsStore.primaryColor } : {}"
                                    :class="!general.auto_atribuir_leads ? 'bg-gray-700' : ''">
                                    <span class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform"
                                        :class="general.auto_atribuir_leads ? 'translate-x-6' : 'translate-x-1'"></span>
                                </button>
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="label">Email de notificação (novos leads)</label>
                                    <input v-model="general.email_notificacao_leads" type="email" class="input" placeholder="email@exemplo.com" />
                                </div>
                                <div>
                                    <label class="label">Dias para expirar lead inativo</label>
                                    <input v-model.number="general.dias_expirar_lead" type="number" min="1" max="365" class="input" placeholder="30" />
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Campanhas -->
                    <div class="card">
                        <h3 class="section-title">Campanhas</h3>
                        <div class="grid grid-cols-2 gap-4 mt-4">
                            <div>
                                <label class="label">Orçamento padrão (R$)</label>
                                <input v-model.number="general.orcamento_padrao" type="number" min="0" class="input" placeholder="0,00" />
                            </div>
                            <div>
                                <label class="label">Domínio de tracking</label>
                                <input v-model="general.dominio_tracking" class="input" placeholder="https://track.exemplo.com" />
                            </div>
                        </div>
                    </div>

                    <!-- Notificações -->
                    <div class="card">
                        <h3 class="section-title">Notificações</h3>
                        <div class="space-y-4 mt-4">
                            <div v-for="notif in notifToggles" :key="notif.key" class="flex items-center justify-between">
                                <div>
                                    <p class="text-sm text-white">{{ notif.label }}</p>
                                    <p class="text-xs text-gray-500 mt-0.5">{{ notif.desc }}</p>
                                </div>
                                <button type="button" @click="general[notif.key] = !general[notif.key]"
                                    class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors flex-shrink-0"
                                    :style="general[notif.key] ? { backgroundColor: settingsStore.primaryColor } : {}"
                                    :class="!general[notif.key] ? 'bg-gray-700' : ''">
                                    <span class="inline-block h-4 w-4 transform rounded-full bg-white shadow transition-transform"
                                        :class="general[notif.key] ? 'translate-x-6' : 'translate-x-1'"></span>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Branding -->
                    <div class="card">
                        <h3 class="section-title">Branding</h3>
                        <div class="space-y-5 mt-4">
                            <!-- Cor Primária -->
                            <div>
                                <label class="label">Cor Primária</label>
                                <div class="flex items-center gap-3">
                                    <input
                                        type="color"
                                        v-model="general.cor_primaria"
                                        @input="settingsStore.applyColor(general.cor_primaria)"
                                        class="w-10 h-10 rounded-lg border border-gray-700 bg-gray-800 cursor-pointer p-1"
                                    />
                                    <input
                                        v-model="general.cor_primaria"
                                        @input="onColorHexInput"
                                        class="input w-36 font-mono uppercase"
                                        maxlength="7"
                                        placeholder="#C6FF3D"
                                    />
                                    <!-- Live preview swatch -->
                                    <div class="flex items-center gap-2">
                                        <div class="w-8 h-8 rounded-lg shadow" :style="{ backgroundColor: general.cor_primaria }"></div>
                                        <span class="text-xs text-gray-500">Prévia ao vivo</span>
                                    </div>
                                </div>
                                <p class="text-xs text-gray-600 mt-1.5">A cor é aplicada imediatamente e salva ao clicar em "Salvar".</p>
                            </div>

                            <!-- Logo -->
                            <div>
                                <label class="label">Logo</label>
                                <div class="flex items-start gap-4">
                                    <!-- Current logo preview -->
                                    <div class="w-16 h-16 rounded-xl border border-gray-700 flex items-center justify-center overflow-hidden flex-shrink-0 p-1"
                                        :style="{ backgroundColor: general.cor_primaria }">
                                        <img v-if="logoPreview || settingsStore.logoUrl"
                                            :src="logoPreview || settingsStore.logoUrl"
                                            class="w-full h-full object-contain" alt="Logo preview" />
                                        <Zap v-else class="w-7 h-7 text-white" />
                                    </div>

                                    <div v-if="auth.can('settings', 'edit')" class="flex-1">
                                        <label
                                            class="flex items-center gap-2 cursor-pointer w-fit px-4 py-2 border border-gray-700 rounded-lg text-sm text-gray-300 hover:bg-gray-800 transition-colors"
                                            :class="uploadingLogo ? 'opacity-50 pointer-events-none' : ''"
                                        >
                                            <Upload class="w-4 h-4" />
                                            {{ uploadingLogo ? 'Enviando...' : 'Escolher imagem' }}
                                            <input type="file" accept="image/*" class="hidden" @change="onLogoChange" :disabled="uploadingLogo" />
                                        </label>
                                        <p class="text-xs text-gray-600 mt-2">PNG, JPG, SVG ou WebP — máx. 2 MB</p>
                                        <p v-if="logoError" class="text-xs text-red-400 mt-1">{{ logoError }}</p>
                                        <p v-if="logoSuccess" class="text-xs text-green-400 mt-1 flex items-center gap-1">
                                            <CheckCircle class="w-3 h-3" /> Logo atualizada!
                                        </p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Save -->
                    <div class="flex items-center gap-4">
                        <button v-if="auth.can('settings', 'edit')" type="submit" :disabled="savingGeneral" class="btn-primary">
                            {{ savingGeneral ? 'Salvando...' : 'Salvar configurações' }}
                        </button>
                        <Transition name="fade">
                            <span v-if="savedGeneral" class="text-sm text-green-400 flex items-center gap-1.5">
                                <CheckCircle class="w-4 h-4" /> Salvo com sucesso!
                            </span>
                        </Transition>
                        <span v-if="errorGeneral" class="text-sm text-red-400">{{ errorGeneral }}</span>
                    </div>
                </form>
            </section>

            <!-- Usuários -->
            <section v-else-if="active === 'usuarios'">
                <div class="mb-6">
                    <h1 class="text-xl font-bold text-white">Usuários</h1>
                    <p class="text-gray-400 text-sm mt-1">Gerencie quem tem acesso ao sistema.</p>
                </div>

                <div class="max-w-2xl space-y-6">
                    <div class="card">
                        <h3 class="section-title mb-4">Conta atual</h3>
                        <div class="flex items-center gap-4">
                            <div class="w-12 h-12 rounded-full flex items-center justify-center font-bold text-lg flex-shrink-0 text-white"
                                :style="{ backgroundColor: settingsStore.primaryColor + '33', color: settingsStore.primaryColor }">
                                {{ auth.user?.name?.charAt(0)?.toUpperCase() }}
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-white font-medium">{{ auth.user?.name }}</p>
                                <p class="text-gray-400 text-sm">{{ auth.user?.email }}</p>
                                <p v-if="general.nome_empresa" class="text-gray-500 text-xs mt-0.5">{{ general.nome_empresa }}</p>
                            </div>
                            <span class="text-xs px-2.5 py-1 rounded-full font-medium"
                                :style="{ backgroundColor: settingsStore.primaryColor + '22', color: settingsStore.primaryColor }">Admin</span>
                        </div>
                        <div class="mt-4 pt-4 border-t border-gray-800">
                            <router-link to="/profile" class="text-sm transition-colors hover:opacity-80" :style="{ color: settingsStore.primaryColor }">
                                Editar perfil →
                            </router-link>
                        </div>
                    </div>

                    <div v-if="auth.isOwner" class="card">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="section-title">Membros da equipe</h3>
                            <button type="button" @click="openMemberModal()" class="text-xs font-medium px-3 py-1.5 rounded-lg transition-colors flex items-center gap-1.5"
                                :style="{ backgroundColor: settingsStore.primaryColor + '22', color: settingsStore.primaryColor }">
                                <Plus class="w-3.5 h-3.5" /> Novo membro
                            </button>
                        </div>
                        <div v-if="loadingMembers" class="py-8 text-center text-gray-500 text-sm">Carregando...</div>
                        <div v-else-if="!members.length" class="py-8 text-center">
                            <Users class="w-10 h-10 text-gray-700 mx-auto mb-3" />
                            <p class="text-gray-400 text-sm">Convide membros da equipe para colaborar.</p>
                        </div>
                        <div v-else class="space-y-2">
                            <div v-for="member in members" :key="member.id" class="flex items-center justify-between gap-3 py-2.5 px-3 rounded-lg bg-gray-800/50">
                                <div class="min-w-0">
                                    <p class="text-sm text-white truncate">{{ member.name }}</p>
                                    <p class="text-xs text-gray-500 truncate">{{ member.email }} · {{ member.role?.name }}</p>
                                </div>
                                <div class="flex items-center gap-1 flex-shrink-0">
                                    <button type="button" @click="openMemberModal(member)" class="p-1.5 rounded-lg text-gray-500 hover:text-white hover:bg-gray-700 transition-colors">
                                        <Pencil class="w-3.5 h-3.5" />
                                    </button>
                                    <button type="button" @click="deleteMember(member)" class="p-1.5 rounded-lg text-gray-500 hover:text-red-400 hover:bg-gray-700 transition-colors">
                                        <Trash2 class="w-3.5 h-3.5" />
                                    </button>
                                </div>
                            </div>
                        </div>
                        <p v-if="errorMembers" class="text-sm text-red-400 mt-3">{{ errorMembers }}</p>
                    </div>

                    <div v-if="auth.isOwner" class="card">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="section-title">Perfis de acesso</h3>
                            <button type="button" @click="openRoleModal()" class="text-xs font-medium px-3 py-1.5 rounded-lg transition-colors flex items-center gap-1.5"
                                :style="{ backgroundColor: settingsStore.primaryColor + '22', color: settingsStore.primaryColor }">
                                <Plus class="w-3.5 h-3.5" /> Novo perfil
                            </button>
                        </div>
                        <div v-if="loadingRoles" class="py-8 text-center text-gray-500 text-sm">Carregando...</div>
                        <div v-else class="space-y-2">
                            <div v-for="role in roles" :key="role.id" class="flex items-center justify-between gap-3 py-2.5 px-3 rounded-lg bg-gray-800/50">
                                <div class="min-w-0">
                                    <div class="flex items-center gap-2">
                                        <p class="text-sm text-white truncate">{{ role.name }}</p>
                                        <span v-if="role.is_default" class="text-[10px] px-1.5 py-0.5 rounded-full bg-gray-700 text-gray-400 flex-shrink-0">Padrão</span>
                                    </div>
                                    <p v-if="role.description" class="text-xs text-gray-500 truncate">{{ role.description }}</p>
                                </div>
                                <div v-if="!role.is_default" class="flex items-center gap-1 flex-shrink-0">
                                    <button type="button" @click="openRoleModal(role)" class="p-1.5 rounded-lg text-gray-500 hover:text-white hover:bg-gray-700 transition-colors">
                                        <Pencil class="w-3.5 h-3.5" />
                                    </button>
                                    <button type="button" @click="deleteRole(role)" class="p-1.5 rounded-lg text-gray-500 hover:text-red-400 hover:bg-gray-700 transition-colors">
                                        <Trash2 class="w-3.5 h-3.5" />
                                    </button>
                                </div>
                            </div>
                        </div>
                        <p v-if="errorRoles" class="text-sm text-red-400 mt-3">{{ errorRoles }}</p>
                    </div>
                </div>
            </section>

            <!-- Em desenvolvimento -->
            <section v-else>
                <div class="mb-6">
                    <h1 class="text-xl font-bold text-white">{{ currentSection?.label }}</h1>
                </div>
                <div class="max-w-md">
                    <div class="card py-16 text-center">
                        <component :is="currentSection?.icon" class="w-12 h-12 text-gray-700 mx-auto mb-4" />
                        <p class="text-white font-medium mb-1">Em desenvolvimento</p>
                        <p class="text-gray-500 text-sm">Esta funcionalidade estará disponível em breve.</p>
                    </div>
                </div>
            </section>
        </div>
    </div>

    <!-- Modal: Perfil de acesso -->
    <Teleport to="body">
        <div v-if="showRoleModal" class="fixed inset-0 bg-black/30 backdrop-blur-xl flex items-center justify-center z-50 p-4" @click.self="showRoleModal = false">
            <div class="glass-modal rounded-2xl w-full max-w-2xl p-6 max-h-[90vh] overflow-y-auto">
                <h2 class="text-lg font-semibold text-white mb-5">{{ editingRole ? 'Editar' : 'Novo' }} Perfil de Acesso</h2>

                <form @submit.prevent="saveRole" class="space-y-4">
                    <div>
                        <label class="label">Nome *</label>
                        <input v-model="roleForm.name" type="text" required class="input" placeholder="Ex: Operador" />
                    </div>
                    <div>
                        <label class="label">Descrição</label>
                        <input v-model="roleForm.description" type="text" class="input" placeholder="Descreva a função deste perfil" />
                    </div>

                    <div>
                        <label class="label mb-2">Permissões por área</label>
                        <div class="rounded-lg border border-gray-800 overflow-hidden">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="bg-gray-800/50 text-gray-400 text-xs uppercase tracking-wide">
                                        <th class="text-left font-medium px-3 py-2">Área</th>
                                        <th v-for="action in permissionActions" :key="action.key" class="px-2 py-2 text-center font-medium">
                                            <button type="button" @click="toggleColumn(action.key)" class="hover:text-white transition-colors">
                                                {{ action.label }}
                                            </button>
                                        </th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <tr v-for="(label, slug) in menus" :key="slug" class="border-t border-gray-800">
                                        <td class="px-3 py-2 text-gray-300">{{ label }}</td>
                                        <td v-for="action in permissionActions" :key="action.key" class="px-2 py-2 text-center">
                                            <input type="checkbox" v-model="roleForm.permissions[slug][action.key]" class="w-4 h-4 accent-lime-600 cursor-pointer" />
                                        </td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                    <p v-if="errorRole" class="text-sm text-red-400">{{ errorRole }}</p>

                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" @click="showRoleModal = false" class="btn-ghost">Cancelar</button>
                        <button type="submit" :disabled="savingRole" class="btn-primary">
                            {{ savingRole ? 'Salvando...' : 'Salvar' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>

    <!-- Modal: Membro da equipe -->
    <Teleport to="body">
        <div v-if="showMemberModal" class="fixed inset-0 bg-black/30 backdrop-blur-xl flex items-center justify-center z-50 p-4" @click.self="showMemberModal = false">
            <div class="glass-modal rounded-2xl w-full max-w-md p-6 max-h-[90vh] overflow-y-auto">
                <h2 class="text-lg font-semibold text-white mb-5">{{ editingMember ? 'Editar' : 'Novo' }} Membro da Equipe</h2>

                <form @submit.prevent="saveMember" class="space-y-4">
                    <div>
                        <label class="label">Nome *</label>
                        <input v-model="memberForm.name" type="text" required class="input" placeholder="Nome do membro" />
                    </div>
                    <div>
                        <label class="label">E-mail *</label>
                        <input v-model="memberForm.email" type="email" required class="input" placeholder="email@exemplo.com" />
                    </div>
                    <div>
                        <label class="label">{{ editingMember ? 'Nova senha (opcional)' : 'Senha *' }}</label>
                        <input v-model="memberForm.password" type="password" :required="!editingMember" minlength="8" class="input" placeholder="Mínimo 8 caracteres" />
                    </div>
                    <div>
                        <label class="label">Confirmar senha{{ editingMember ? '' : ' *' }}</label>
                        <input v-model="memberForm.password_confirmation" type="password" :required="!editingMember && !!memberForm.password" minlength="8" class="input" placeholder="Repita a senha" />
                    </div>
                    <div>
                        <label class="label">Perfil de acesso *</label>
                        <select v-model="memberForm.role_id" required class="input">
                            <option value="" disabled>Selecione um perfil</option>
                            <option v-for="role in roles" :key="role.id" :value="role.id">{{ role.name }}</option>
                        </select>
                    </div>

                    <p v-if="errorMember" class="text-sm text-red-400">{{ errorMember }}</p>

                    <div class="flex justify-end gap-3 pt-2">
                        <button type="button" @click="showMemberModal = false" class="btn-ghost">Cancelar</button>
                        <button type="submit" :disabled="savingMember" class="btn-primary">
                            {{ savingMember ? 'Salvando...' : 'Salvar' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </Teleport>
</template>

<script setup>
import { ref, reactive, computed, onMounted } from 'vue'
import { RouterLink } from 'vue-router'
import {
    Settings, Users, Mail, Webhook, FileText, Bot, Share2,
    LayoutList, CreditCard, ScrollText, Zap,
    Loader2, CheckCircle, Upload, Plus, Pencil, Trash2
} from 'lucide-vue-next'
import api from '@/services/api'
import { useAuthStore } from '@/stores/auth'
import { useSettingsStore } from '@/stores/settings'
import { onCNPJInput, maskCNPJ } from '@/utils/mask'

const auth          = useAuthStore()
const settingsStore = useSettingsStore()
const active        = ref('geral')

const sections = [
    { id: 'geral',      label: 'Geral',                   icon: Settings },
    { id: 'usuarios',   label: 'Usuários',                icon: Users },
    { id: 'email',      label: 'Email',                   icon: Mail },
    { id: 'webhooks',   label: 'Webhooks',                icon: Webhook },
    { id: 'templates',  label: 'Templates',               icon: FileText },
    { id: 'ia',         label: 'Inteligência Artificial', icon: Bot },
    { id: 'sociais',    label: 'Redes Sociais',           icon: Share2 },
    { id: 'campos',     label: 'Campos Customizados',     icon: LayoutList },
    { id: 'assinatura', label: 'Assinatura',              icon: CreditCard },
    { id: 'logs',       label: 'Logs de Auditoria',       icon: ScrollText },
    { id: 'automacao',  label: 'Automação',               icon: Zap },
]

const currentSection = computed(() => sections.find(s => s.id === active.value))

// ─── Geral ─────────────────────────────────────────────────────────────────────

const loadingSettings = ref(true)
const savingGeneral   = ref(false)
const savedGeneral    = ref(false)
const errorGeneral    = ref('')

const general = reactive({
    nome_empresa: '', cnpj: '', moeda: 'BRL', fuso_horario: 'America/Sao_Paulo',
    auto_atribuir_leads: false, email_notificacao_leads: '',
    dias_expirar_lead: 30, orcamento_padrao: 0, dominio_tracking: '',
    notificar_novo_lead: true, notificar_tarefa_vencida: true, notificar_fim_campanha: true,
    cor_primaria: '#C6FF3D',
})

const notifToggles = [
    { key: 'notificar_novo_lead',      label: 'Notificar novo lead',       desc: 'Receba alertas quando um lead for criado' },
    { key: 'notificar_tarefa_vencida', label: 'Notificar tarefa vencida',  desc: 'Alertas para tarefas com prazo ultrapassado' },
    { key: 'notificar_fim_campanha',   label: 'Notificar fim de campanha', desc: 'Aviso quando uma campanha encerrar' },
]

// Live preview: apply color immediately as user types a valid hex
function onColorHexInput() {
    if (/^#[0-9A-Fa-f]{6}$/.test(general.cor_primaria)) {
        settingsStore.applyColor(general.cor_primaria)
    }
}

async function loadGeneral() {
    try {
        const { data } = await api.get('/settings')
        Object.assign(general, data)
        if (general.cnpj) general.cnpj = maskCNPJ(general.cnpj)
        // Ensure the loaded color is applied
        if (data.cor_primaria) settingsStore.applyColor(data.cor_primaria)
    } finally {
        loadingSettings.value = false
    }
}

async function saveGeneral() {
    savingGeneral.value = true
    errorGeneral.value  = ''
    try {
        await settingsStore.save(general)
        savedGeneral.value = true
        setTimeout(() => (savedGeneral.value = false), 3000)
    } catch {
        errorGeneral.value = 'Erro ao salvar. Tente novamente.'
    } finally {
        savingGeneral.value = false
    }
}

// ─── Logo upload ────────────────────────────────────────────────────────────

const logoPreview    = ref(null)
const uploadingLogo  = ref(false)
const logoError      = ref('')
const logoSuccess    = ref(false)

async function onLogoChange(e) {
    const file = e.target.files[0]
    if (!file) return

    if (file.size > 2 * 1024 * 1024) {
        logoError.value = 'Arquivo muito grande. Máximo 2 MB.'
        return
    }

    // Instant local preview
    const reader = new FileReader()
    reader.onload = ev => (logoPreview.value = ev.target.result)
    reader.readAsDataURL(file)

    uploadingLogo.value = true
    logoError.value     = ''
    logoSuccess.value   = false
    try {
        await settingsStore.uploadLogo(file)
        logoSuccess.value = true
        setTimeout(() => (logoSuccess.value = false), 3000)
    } catch {
        logoError.value  = 'Erro ao enviar imagem. Tente novamente.'
        logoPreview.value = null
    } finally {
        uploadingLogo.value = false
        e.target.value = ''
    }
}

// ─── Perfis de acesso ───────────────────────────────────────────────────────────

const menus         = ref({})
const roles         = ref([])
const loadingRoles   = ref(true)
const errorRoles     = ref('')
const showRoleModal = ref(false)
const editingRole    = ref(null)
const savingRole     = ref(false)
const errorRole      = ref('')

const permissionActions = [
    { key: 'view',   label: 'Ver' },
    { key: 'create', label: 'Criar' },
    { key: 'edit',   label: 'Editar' },
    { key: 'delete', label: 'Excluir' },
]

const roleForm = reactive({ name: '', description: '', permissions: {} })

function blankPermissions() {
    return Object.fromEntries(
        Object.keys(menus.value).map(slug => [slug, { view: false, create: false, edit: false, delete: false }])
    )
}

async function loadMenus() {
    const { data } = await api.get('/menus')
    menus.value = data
}

async function loadRoles() {
    loadingRoles.value = true
    errorRoles.value = ''
    try {
        const { data } = await api.get('/roles')
        roles.value = data
    } catch {
        errorRoles.value = 'Erro ao carregar perfis de acesso.'
    } finally {
        loadingRoles.value = false
    }
}

function openRoleModal(role = null) {
    editingRole.value = role
    roleForm.permissions = blankPermissions()
    if (role) {
        roleForm.name = role.name
        roleForm.description = role.description || ''
        Object.assign(roleForm.permissions, JSON.parse(JSON.stringify(role.permissions || {})))
    } else {
        roleForm.name = ''
        roleForm.description = ''
    }
    errorRole.value = ''
    showRoleModal.value = true
}

function toggleColumn(actionKey) {
    const allChecked = Object.keys(menus.value).every(slug => roleForm.permissions[slug][actionKey])
    Object.keys(menus.value).forEach(slug => { roleForm.permissions[slug][actionKey] = !allChecked })
}

async function saveRole() {
    savingRole.value = true
    errorRole.value = ''
    try {
        if (editingRole.value) {
            await api.put(`/roles/${editingRole.value.id}`, roleForm)
        } else {
            await api.post('/roles', roleForm)
        }
        showRoleModal.value = false
        await loadRoles()
    } catch (e) {
        errorRole.value = e.response?.data?.message ?? 'Erro ao salvar perfil.'
    } finally {
        savingRole.value = false
    }
}

async function deleteRole(role) {
    if (!confirm(`Excluir o perfil "${role.name}"?`)) return
    try {
        await api.delete(`/roles/${role.id}`)
        await loadRoles()
    } catch {
        errorRoles.value = 'Erro ao excluir perfil.'
    }
}

// ─── Membros da equipe ──────────────────────────────────────────────────────────

const members         = ref([])
const loadingMembers   = ref(true)
const errorMembers     = ref('')
const showMemberModal = ref(false)
const editingMember    = ref(null)
const savingMember     = ref(false)
const errorMember      = ref('')

const memberForm = reactive({ name: '', email: '', password: '', password_confirmation: '', role_id: '' })

async function loadMembers() {
    loadingMembers.value = true
    errorMembers.value = ''
    try {
        const { data } = await api.get('/team-members')
        members.value = data
    } catch {
        errorMembers.value = 'Erro ao carregar membros da equipe.'
    } finally {
        loadingMembers.value = false
    }
}

function openMemberModal(member = null) {
    editingMember.value = member
    if (member) {
        memberForm.name = member.name
        memberForm.email = member.email
        memberForm.role_id = member.role_id
    } else {
        memberForm.name = ''
        memberForm.email = ''
        memberForm.role_id = ''
    }
    memberForm.password = ''
    memberForm.password_confirmation = ''
    errorMember.value = ''
    showMemberModal.value = true
}

async function saveMember() {
    savingMember.value = true
    errorMember.value = ''
    try {
        if (editingMember.value) {
            await api.put(`/team-members/${editingMember.value.id}`, memberForm)
        } else {
            await api.post('/team-members', memberForm)
        }
        showMemberModal.value = false
        await loadMembers()
    } catch (e) {
        errorMember.value = e.response?.data?.message ?? 'Erro ao salvar membro.'
    } finally {
        savingMember.value = false
    }
}

async function deleteMember(member) {
    if (!confirm(`Remover "${member.name}" da equipe?`)) return
    try {
        await api.delete(`/team-members/${member.id}`)
        await loadMembers()
    } catch {
        errorMembers.value = 'Erro ao remover membro.'
    }
}

onMounted(async () => {
    loadGeneral()
    if (auth.isOwner) {
        await loadMenus()
        loadRoles()
        loadMembers()
    }
})
</script>

<style scoped>
@reference "tailwindcss";
.label        { @apply block text-xs text-gray-400 mb-1 font-medium; }
.btn-ghost    { @apply text-gray-400 hover:text-white font-medium px-4 py-2 rounded-lg transition-colors text-sm; background: transparent; }
.btn-ghost:hover { background: rgba(255, 255, 255, 0.055); }
.card {
    @apply rounded-2xl p-6;
    background: rgba(14, 12, 22, 0.68);
    backdrop-filter: blur(28px) saturate(180%);
    -webkit-backdrop-filter: blur(28px) saturate(180%);
    border: 1px solid rgba(255, 255, 255, 0.07);
    box-shadow:
        inset 0 1px 0   rgba(255, 255, 255, 0.10),
        inset 0 -1px 0  rgba(0,   0,   0,   0.25),
        0 0 0 0.5px     rgba(255, 255, 255, 0.04),
        0 12px 48px     rgba(0,   0,   0,   0.55);
}
.section-title { @apply text-sm font-semibold text-gray-300 uppercase tracking-wide; }
.fade-enter-active, .fade-leave-active { transition: opacity 0.3s; }
.fade-enter-from, .fade-leave-to { opacity: 0; }
</style>
