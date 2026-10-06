<template>
    <section class="max-w-4xl flex flex-col gap-6">
        <div>
            <h1 class="page-hero-title text-2xl">Log de auditoria</h1>
            <p class="text-sm mt-1" style="color: var(--text-secondary)">Tudo o que aconteceu na conta: o que foi feito, por quem, quando e com qual resultado.</p>
        </div>

        <!-- Filtros -->
        <div class="panel flex flex-col gap-4">
            <div class="flex flex-wrap items-center gap-3">
                <label class="relative flex-1 min-w-[14rem]">
                    <span class="sr-only">Buscar no log</span>
                    <Search class="w-4 h-4 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" style="color: var(--text-muted)" />
                    <input v-model="search" type="search" placeholder="Buscar por produto, campanha, e-mail..." class="input pl-9" />
                </label>
                <div class="flex gap-1.5" role="radiogroup" aria-label="Status">
                    <button v-for="s in STATUSES" :key="s.value" type="button" role="radio" :aria-checked="filters.status === s.value" @click="filters.status = s.value" class="filter-chip" :class="{ active: filters.status === s.value }">
                        <span v-if="s.dot" class="w-1.5 h-1.5 rounded-full" :style="{ background: s.dot }"></span>
                        {{ s.label }}
                    </button>
                </div>
            </div>

            <div class="flex flex-wrap gap-1.5" role="radiogroup" aria-label="Área">
                <button v-for="a in AREAS" :key="a.value" type="button" role="radio" :aria-checked="filters.area === a.value" @click="filters.area = a.value" class="filter-chip !min-h-8 !py-1 !text-xs" :class="{ active: filters.area === a.value }">
                    {{ a.label }}
                </button>
            </div>

            <div class="flex flex-wrap items-center gap-1.5">
                <span class="text-xs mr-1" style="color: var(--text-muted)">Período</span>
                <button v-for="p in PERIODS" :key="p.value" type="button" @click="period = p.value" :aria-pressed="period === p.value" class="filter-chip !min-h-8 !py-1 !text-xs" :class="{ active: period === p.value }">
                    {{ p.label }}
                </button>
                <template v-if="period === 'custom'">
                    <input v-model="customFrom" type="date" class="input !py-1.5 !w-auto text-xs" aria-label="De" />
                    <span class="text-xs" style="color: var(--text-muted)">até</span>
                    <input v-model="customTo" type="date" class="input !py-1.5 !w-auto text-xs" aria-label="Até" />
                </template>
                <button v-if="hasFilters" type="button" @click="clearFilters" class="ml-auto inline-flex items-center gap-1 text-xs hover:text-white transition-colors" style="color: var(--text-secondary)">
                    <X class="w-3.5 h-3.5" /> Limpar filtros
                </button>
            </div>
        </div>

        <!-- Lista -->
        <div v-if="loading && !logs.length" class="flex flex-col gap-2">
            <div v-for="i in 6" :key="i" class="shimmer h-16 rounded-xl"></div>
        </div>

        <EmptyState
            v-else-if="!logs.length"
            class="panel"
            :icon="ScrollText"
            :title="hasFilters ? 'Nada encontrado com esses filtros' : 'Nenhuma ação registrada ainda'"
            :text="hasFilters ? 'Tente ampliar o período ou limpar os filtros.' : 'As ações feitas na conta aparecem aqui automaticamente.'"
        />

        <div v-else class="flex flex-col gap-6">
            <p class="text-xs -mb-3" style="color: var(--text-muted)">{{ total }} {{ total === 1 ? 'registro' : 'registros' }}</p>

            <div v-for="group in groups" :key="group.label" class="flex flex-col gap-2">
                <h2 class="tech-label !text-[var(--text-secondary)] sticky top-0 py-1 z-[1]" style="background: var(--bg-base)">{{ group.label }}</h2>

                <ul class="flex flex-col gap-1.5">
                    <li v-for="log in group.logs" :key="log.id" class="log-item" :class="{ failed: log.status === 'failed', open: openId === log.id }">
                        <button type="button" @click="openId = openId === log.id ? null : log.id" :aria-expanded="openId === log.id" class="w-full flex items-start gap-3 px-4 py-3 text-left">
                            <span class="log-icon" :class="iconTone(log)">
                                <component :is="iconFor(log)" class="w-4 h-4" />
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="block text-sm text-white leading-snug">{{ log.description }}</span>
                                <span class="flex flex-wrap items-center gap-x-2 gap-y-1 mt-1 text-xs" style="color: var(--text-muted)">
                                    <span class="inline-flex items-center gap-1.5">
                                        <span class="avatar-xs">{{ initials(log.causer?.name) }}</span>
                                        {{ log.causer?.name ?? 'Sistema' }}
                                    </span>
                                    <span aria-hidden="true">·</span>
                                    <span>{{ areaLabel(log.area) }}</span>
                                    <template v-if="log.duration_ms">
                                        <span aria-hidden="true">·</span>
                                        <span class="tabular-nums">{{ formatDuration(log.duration_ms) }}</span>
                                    </template>
                                    <span v-if="log.ai_model" class="px-1.5 py-0.5 rounded-full" style="color: color-mix(in srgb, var(--brand) 60%, white); background: color-mix(in srgb, var(--brand) 14%, transparent)">{{ log.ai_model }}</span>
                                    <span v-if="log.status === 'failed'" class="px-1.5 py-0.5 rounded-full text-red-300 bg-red-500/15">Falhou</span>
                                </span>
                            </span>
                            <span class="text-xs tabular-nums flex-shrink-0 mt-0.5" style="color: var(--text-muted)">{{ formatTime(log.created_at) }}</span>
                            <ChevronDown class="w-4 h-4 flex-shrink-0 mt-0.5 transition-transform" :class="{ 'rotate-180': openId === log.id }" style="color: var(--text-muted)" />
                        </button>

                        <!-- Detalhes -->
                        <div v-if="openId === log.id" class="px-4 pb-4 pl-[3.75rem] flex flex-col gap-3 text-xs">
                            <dl class="grid grid-cols-[7rem_minmax(0,1fr)] gap-x-3 gap-y-1.5">
                                <dt style="color: var(--text-muted)">Data e hora</dt><dd class="text-gray-200">{{ formatFull(log.created_at) }}</dd>
                                <dt style="color: var(--text-muted)">Ação</dt><dd class="text-gray-200 font-mono">{{ log.action }}</dd>
                                <template v-if="log.ip_address"><dt style="color: var(--text-muted)">IP</dt><dd class="text-gray-200 font-mono">{{ log.ip_address }}</dd></template>
                                <template v-if="log.user_agent"><dt style="color: var(--text-muted)">Navegador</dt><dd class="text-gray-200 truncate" :title="log.user_agent">{{ browserOf(log.user_agent) }}</dd></template>
                            </dl>
                            <div v-if="hasData(log.input)">
                                <p class="mb-1" style="color: var(--text-muted)">Dados enviados</p>
                                <pre class="log-json">{{ pretty(log.input) }}</pre>
                            </div>
                            <div v-if="hasData(log.output)">
                                <p class="mb-1" style="color: var(--text-muted)">Resultado</p>
                                <pre class="log-json">{{ pretty(log.output) }}</pre>
                            </div>
                        </div>
                    </li>
                </ul>
            </div>

            <button v-if="page < lastPage" type="button" @click="loadMore" :disabled="loading" class="btn-secondary self-center">
                <Loader2 v-if="loading" class="w-4 h-4 animate-spin" />
                {{ loading ? 'Carregando...' : 'Carregar mais' }}
            </button>
        </div>
    </section>
</template>

<script setup>
import { ref, reactive, computed, watch, onMounted } from 'vue'
import { Loader2, ScrollText, Search, X, ChevronDown, LogIn, LogOut, ShieldAlert, PlusCircle, Pencil, Trash2, Sparkles, AlertCircle, ImageUp, KeyRound, Activity, UserPlus, Wallet } from 'lucide-vue-next'
import api from '@/services/api'
import EmptyState from '@/components/ui/EmptyState.vue'

const AREAS = [
    { value: '', label: 'Todas as áreas' },
    { value: 'auth', label: 'Acessos' },
    { value: 'campaigns', label: 'Campanhas' },
    { value: 'products', label: 'Produtos' },
    { value: 'generate', label: 'Geração de IA' },
    { value: 'ai_providers', label: 'Chaves de IA' },
    { value: 'settings', label: 'Configurações' },
]
const STATUSES = [
    { value: '', label: 'Todos' },
    { value: 'success', label: 'Sucesso', dot: '#4ade80' },
    { value: 'failed', label: 'Falhas', dot: '#f87171' },
]
const PERIODS = [
    { value: '', label: 'Tudo' },
    { value: 'today', label: 'Hoje' },
    { value: '7d', label: '7 dias' },
    { value: '30d', label: '30 dias' },
    { value: 'custom', label: 'Personalizado' },
]

const logs = ref([])
const loading = ref(true)
const page = ref(1)
const lastPage = ref(1)
const total = ref(0)
const openId = ref(null)

const filters = reactive({ area: '', status: '' })
const search = ref('')
const period = ref('')
const customFrom = ref('')
const customTo = ref('')

const hasFilters = computed(() => !!(filters.area || filters.status || search.value.trim() || period.value))

// ── Labels & icons ──────────────────────────────────────────────
const areaLabel = (v) => AREAS.find(a => a.value === v)?.label ?? ({ billing: 'Cobrança', platform_admin: 'Admin da plataforma' }[v] || v)

function iconFor(log) {
    const a = log.action || ''
    if (a === 'auth.login') return LogIn
    if (a === 'auth.logout') return LogOut
    if (a === 'auth.login_failed') return ShieldAlert
    if (a.startsWith('generation.')) return log.status === 'failed' ? AlertCircle : Sparkles
    if (a.startsWith('ai_credential')) return KeyRound
    if (a.endsWith('image_updated') || a.endsWith('logo_updated')) return ImageUp
    if (a.startsWith('team_member.created')) return UserPlus
    if (a.includes('monthly_fee')) return Wallet
    if (a.endsWith('.created') || a.endsWith('.saved') || a.endsWith('.granted')) return PlusCircle
    if (a.endsWith('.updated')) return Pencil
    if (a.endsWith('.deleted') || a.endsWith('.revoked')) return Trash2
    return Activity
}
function iconTone(log) {
    const a = log.action || ''
    if (log.status === 'failed' || a === 'auth.login_failed') return 'tone-red'
    if (a.endsWith('.deleted') || a.endsWith('.revoked')) return 'tone-amber'
    if (a.startsWith('generation.')) return 'tone-brand'
    return 'tone-neutral'
}

// ── Formatting ──────────────────────────────────────────────────
const initials = (name = '') => name ? name.split(' ').filter(Boolean).slice(0, 2).map(w => w[0]).join('').toUpperCase() : '·'
const formatTime = (d) => new Date(d).toLocaleTimeString('pt-BR', { hour: '2-digit', minute: '2-digit' })
const formatFull = (d) => new Date(d).toLocaleString('pt-BR')
const formatDuration = (ms) => ms < 1000 ? `${ms} ms` : `${(ms / 1000).toFixed(1)} s`
const hasData = (v) => v && (typeof v !== 'object' || Object.keys(v).length)
const pretty = (v) => typeof v === 'string' ? v : JSON.stringify(v, null, 2)

function browserOf(ua) {
    const browser = /Edg\//.test(ua) ? 'Edge' : /Chrome\//.test(ua) ? 'Chrome' : /Firefox\//.test(ua) ? 'Firefox' : /Safari\//.test(ua) ? 'Safari' : 'Navegador'
    const os = /Windows/.test(ua) ? 'Windows' : /Mac OS X/.test(ua) ? 'macOS' : /Android/.test(ua) ? 'Android' : /iPhone|iPad/.test(ua) ? 'iOS' : /Linux/.test(ua) ? 'Linux' : ''
    return os ? `${browser} · ${os}` : browser
}

const dayKey = (d) => new Date(d).toDateString()
function dayLabel(d) {
    const date = new Date(d)
    const today = new Date()
    const yesterday = new Date(); yesterday.setDate(today.getDate() - 1)
    if (date.toDateString() === today.toDateString()) return 'Hoje'
    if (date.toDateString() === yesterday.toDateString()) return 'Ontem'
    return date.toLocaleDateString('pt-BR', { weekday: 'long', day: '2-digit', month: 'long' })
}
const groups = computed(() => {
    const out = []
    for (const log of logs.value) {
        const last = out[out.length - 1]
        if (last?.key === dayKey(log.created_at)) last.logs.push(log)
        else out.push({ key: dayKey(log.created_at), label: dayLabel(log.created_at), logs: [log] })
    }
    return out
})

// ── Data ────────────────────────────────────────────────────────
// Local YYYY-MM-DD (toISOString is UTC: after 21h in Brazil it would already be tomorrow).
const isoDay = (d) => `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`
function dateRange() {
    const today = new Date()
    const back = (n) => { const d = new Date(); d.setDate(today.getDate() - n); return isoDay(d) }
    if (period.value === 'today') return { date_from: isoDay(today) }
    if (period.value === '7d') return { date_from: back(6) }
    if (period.value === '30d') return { date_from: back(29) }
    if (period.value === 'custom') return { date_from: customFrom.value || undefined, date_to: customTo.value || undefined }
    return {}
}

async function fetchLogs({ append = false } = {}) {
    loading.value = true
    try {
        const params = { page: page.value, ...dateRange() }
        if (filters.area) params.area = filters.area
        if (filters.status) params.status = filters.status
        if (search.value.trim()) params.search = search.value.trim()

        const { data } = await api.get('/audit-logs', { params })
        logs.value = append ? [...logs.value, ...data.data] : data.data
        lastPage.value = data.last_page
        total.value = data.total
    } finally {
        loading.value = false
    }
}

function reload() {
    page.value = 1
    openId.value = null
    fetchLogs()
}
function loadMore() {
    page.value++
    fetchLogs({ append: true })
}
function clearFilters() {
    Object.assign(filters, { area: '', status: '' })
    search.value = ''
    period.value = ''
}

watch(() => [filters.area, filters.status, period.value, customFrom.value, customTo.value], reload)

// Debounce typing so each keystroke doesn't hit the API.
let searchTimer = null
watch(search, () => {
    clearTimeout(searchTimer)
    searchTimer = setTimeout(reload, 350)
})

onMounted(fetchLogs)
</script>

<style scoped>
.log-item {
    border-radius: 14px;
    background: var(--surface-1);
    border: 1px solid var(--border-subtle);
    transition: border-color 0.15s, background-color 0.15s;
}
.log-item:hover,
.log-item.open { border-color: var(--border-soft); background: rgba(255, 255, 255, 0.045); }
.log-item.failed { border-color: rgba(248, 113, 113, 0.25); }

.log-icon {
    width: 32px;
    height: 32px;
    flex-shrink: 0;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 10px;
}
.tone-neutral { color: var(--text-secondary); background: var(--surface-2); }
.tone-brand { color: color-mix(in srgb, var(--brand) 60%, white); background: color-mix(in srgb, var(--brand) 16%, transparent); }
.tone-amber { color: #fcd34d; background: rgba(251, 191, 36, 0.12); }
.tone-red { color: #fca5a5; background: rgba(248, 113, 113, 0.14); }

.avatar-xs {
    width: 18px;
    height: 18px;
    display: inline-flex;
    align-items: center;
    justify-content: center;
    border-radius: 9999px;
    font-size: 9px;
    font-weight: 600;
    color: color-mix(in srgb, var(--brand) 60%, white);
    background: color-mix(in srgb, var(--brand) 18%, transparent);
}

.log-json {
    max-height: 16rem;
    overflow: auto;
    padding: 10px 12px;
    border-radius: 10px;
    font-size: 11px;
    line-height: 1.5;
    white-space: pre-wrap;
    word-break: break-word;
    color: var(--text-secondary);
    background: rgba(0, 0, 0, 0.3);
    border: 1px solid var(--border-subtle);
}
</style>
