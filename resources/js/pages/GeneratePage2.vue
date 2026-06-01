<template>
    <div class="flex-1 overflow-y-auto px-6 py-8 max-w-2xl mx-auto w-full">

        <!-- Header -->
        <div class="mb-8">
            <h1 class="text-xs font-bold tracking-widest uppercase mb-1" style="color: var(--brand)">Gerador de Prompts</h1>
            <p class="text-sm" style="color: var(--text-muted)">Preencha produto e valor. O sistema monta tudo.</p>
        </div>

        <!-- Produto -->
        <div class="section">
            <label class="field-label">Produto</label>
            <input
                v-model="produto"
                @input="detectCategory"
                type="text"
                class="field-input"
                placeholder="Ex: Coca-Cola 600ml, Detergente Ypê, Biscoito Oreo..."
            />
            <p v-if="detectedTag" class="mt-2 text-xs" style="color: var(--brand)">↳ {{ detectedTag }}</p>
        </div>

        <!-- Valor -->
        <div class="section">
            <label class="field-label">Valor</label>
            <input
                v-model="valor"
                type="text"
                class="field-input"
                placeholder="Ex: R$ 2,95 / unidade"
            />
        </div>

        <!-- Personagem -->
        <div class="section">
            <label class="field-label">Personagem</label>
            <div class="grid grid-cols-2 gap-2">
                <button
                    v-for="p in personagens"
                    :key="p.value"
                    type="button"
                    @click="selected.personagem = p.value"
                    class="chip"
                    :class="{ active: selected.personagem === p.value }"
                >
                    <div class="chip-title">{{ p.emoji }} {{ p.label }}</div>
                    <div class="chip-sub">{{ p.sub }}</div>
                </button>
            </div>
        </div>

        <!-- Mood Visual -->
        <div class="section">
            <label class="field-label">Mood Visual</label>
            <div class="grid grid-cols-2 gap-2">
                <button
                    v-for="m in moods"
                    :key="m.value"
                    type="button"
                    @click="selected.mood = m.value"
                    class="chip"
                    :class="{ active: selected.mood === m.value }"
                >
                    <div class="chip-title">{{ m.emoji }} {{ m.label }}</div>
                    <div class="chip-sub">{{ m.sub }}</div>
                </button>
            </div>
        </div>

        <!-- Categoria / Action -->
        <div class="section">
            <label class="field-label">Categoria do produto — Action do vídeo</label>
            <div class="grid grid-cols-3 gap-2">
                <button
                    v-for="a in actions"
                    :key="a.cat"
                    type="button"
                    @click="selected.action = a.cat"
                    class="chip"
                    :class="{ active: selected.action === a.cat }"
                >
                    <div class="chip-title">{{ a.emoji }} {{ a.label }}</div>
                    <div class="chip-sub">{{ a.sub }}</div>
                </button>
            </div>
        </div>

        <!-- Botão gerar -->
        <button
            @click="gerar"
            class="w-full py-3.5 rounded-xl text-xs font-black tracking-widest uppercase transition-colors btn-primary mt-2"
            :disabled="!produto.trim() || !valor.trim()"
            style="disabled:opacity: 0.4"
        >
            ⚡ Gerar Prompts
        </button>

        <!-- Output -->
        <div v-if="output" class="mt-8 space-y-4">
            <div class="divider"></div>

            <!-- Prompt Imagem -->
            <div class="out-box">
                <div class="out-label">🟣 PROMPT 01 — Imagem · Nano Banana / Midjourney</div>
                <pre class="out-text">{{ output.imagem }}</pre>
                <button @click="copiar('imagem')" class="copy-btn">
                    {{ copied === 'imagem' ? '✓ Copiado!' : 'Copiar Prompt de Imagem' }}
                </button>
            </div>

            <!-- Prompt Vídeo -->
            <div class="out-box">
                <div class="out-label">🎬 PROMPT 02 — Vídeo · Kling / Seedance 2.0</div>
                <pre class="out-text">{{ output.video }}</pre>
                <button @click="copiar('video')" class="copy-btn">
                    {{ copied === 'video' ? '✓ Copiado!' : 'Copiar Prompt de Vídeo' }}
                </button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref } from 'vue'

const produto     = ref('')
const valor       = ref('')
const detectedTag = ref('')
const output      = ref(null)
const copied      = ref(null)

const selected = ref({
    personagem: 'mulher',
    mood:       'quente',
    action:     'geral',
})

const personagens = [
    { value: 'mulher',  emoji: '👩', label: 'Mulher Jovem', sub: 'Jovem, brasileira, confiante',  prompt: 'a beautiful young Brazilian woman, confident and natural smile' },
    { value: 'homem',   emoji: '👨', label: 'Homem Jovem',  sub: 'Jovem, brasileiro, dinâmico',   prompt: 'a handsome young Brazilian man, energetic and expressive' },
    { value: 'casal',   emoji: '👫', label: 'Casal',        sub: 'Família, bem-estar',             prompt: 'a happy Brazilian couple, warm and relaxed' },
    { value: 'crianca', emoji: '👦', label: 'Criança',      sub: 'Família, alegria',               prompt: 'a joyful Brazilian child, curious and excited' },
]

const moods = [
    { value: 'quente',        emoji: '🌅', label: 'Quente e Vibrante',  sub: 'Energia, impacto, alegria',        prompt: 'warm golden hour lighting, vibrant and energetic, saturated rich colors, sun-kissed atmosphere' },
    { value: 'cinematografico', emoji: '🎬', label: 'Cinematográfico', sub: 'Premium, sofisticado, impacto',     prompt: 'dark cinematic lighting, premium and dramatic, deep contrast shadows, rich desaturated tones, film noir quality' },
    { value: 'clean',         emoji: '☀️', label: 'Clean e Fresco',    sub: 'Clareza, confiança, leve',          prompt: 'bright clean studio lighting, fresh and modern, high key, white and blue tones, minimal background' },
    { value: 'familiar',      emoji: '🏠', label: 'Familiar',          sub: 'Lar, conforto, afeto',              prompt: 'cozy warm indoor lighting, familiar and comforting, natural earth tones, soft bokeh home environment' },
]

const actions = [
    { cat: 'bebida',    emoji: '🥤', label: 'Bebida',         sub: 'Abre, bebe, reage' },
    { cat: 'alimento',  emoji: '🍫', label: 'Alimento',       sub: 'Pega, abre, prova' },
    { cat: 'limpeza',   emoji: '🧴', label: 'Limpeza',        sub: 'Aplica, resultado' },
    { cat: 'hortifruti',emoji: '🥩', label: 'Açougue/Horti',  sub: 'Exibe, frescor' },
    { cat: 'higiene',   emoji: '🪥', label: 'Higiene',        sub: 'Usa, sente, reage' },
    { cat: 'geral',     emoji: '📦', label: 'Geral',          sub: 'Apresenta, destaca' },
]

function getPersonagemPrompt() {
    return personagens.find(p => p.value === selected.value.personagem)?.prompt ?? personagens[0].prompt
}

function getMoodPrompt() {
    return moods.find(m => m.value === selected.value.mood)?.prompt ?? moods[0].prompt
}

function detectCategory() {
    const p = produto.value.toLowerCase()
    let cat = 'geral', tag = ''

    if (/(coca|pepsi|suco|água|cerveja|refrigerante|isotônico|leite|vinho|energético|café|chá|nescau|guaraná)/i.test(p)) {
        cat = 'bebida'; tag = '🥤 Bebida detectada'
    } else if (/(biscoito|bolacha|pão|macarrão|arroz|feijão|farinha|óleo|azeite|chocolate|sorvete|iogurte|queijo|frios|embutido|presunto|salame|mortadela)/i.test(p)) {
        cat = 'alimento'; tag = '🍫 Alimento detectado'
    } else if (/(detergente|sabão|amaciante|desinfetante|limpador|multiuso|esponja|cloro|alvejante|quiboa)/i.test(p)) {
        cat = 'limpeza'; tag = '🧴 Limpeza detectada'
    } else if (/(carne|frango|peixe|salsicha|linguiça|fruta|legume|verdura|tomate|banana|maçã|alface|cenoura)/i.test(p)) {
        cat = 'hortifruti'; tag = '🥩 Açougue/Horti detectado'
    } else if (/(shampoo|condicionador|sabonete|creme|desodorante|escova|pasta|perfume|hidratante)/i.test(p)) {
        cat = 'higiene'; tag = '🪥 Higiene detectada'
    }

    selected.value.action = cat
    detectedTag.value = tag ? tag + ' — action aplicado automaticamente' : ''
}

const ACTIONS = {
    bebida: (p, persona) =>
        `${persona} grabs the ${p} with a fast confident motion and opens it with a sharp powerful twist — condensation droplets EXPLODE toward camera in extreme slow motion, she lifts it dramatically and takes a long deep sip, eyes close in pure pleasure, head tilts back slightly, camera SPINS around her face in a sweeping arc, then CRASHES into an extreme close-up of the bottle label in razor sharp focus, liquid swirling inside visible through the bottle.`,

    alimento: (p, persona) =>
        `${persona} boldly reaches for the ${p} with excitement, rips it open with a dramatic tearing motion — product contents BURST outward in glorious slow motion beauty shot, she takes a massive first bite captured in extreme close-up with every texture detail visible, eyes go wide with genuine pleasure, camera WHIPS from face to product in a sharp snap cut, then pushes aggressively into the package logo in a powerful crash zoom.`,

    limpeza: (p, persona) =>
        `${persona} grabs the ${p} with determination and sprays it in a powerful dramatic arc — the spray catches the light like liquid diamonds in slow motion, she wipes the surface with one bold confident stroke revealing a GLEAMING clean result, camera swings low in a dramatic low angle capturing the transformation, she turns to camera with a bold satisfied look, product THRUST toward camera in a heroic final frame.`,

    hortifruti: (p, persona) =>
        `${persona} lifts the ${p} in a bold heroic presentation toward camera — camera starts at extreme low angle rotating upward around the product like a cinematic trophy reveal, dramatic golden light EXPLODES on the product surface emphasizing absolute freshness and perfection, slow motion water droplets or texture detail in extreme macro close-up, camera pulls back with a sweeping crane-like movement revealing the full scene, final frame holds on product glowing under perfect light.`,

    higiene: (p, persona) =>
        `${persona} applies the ${p} with a sweeping confident motion, camera follows in extreme close-up capturing every texture and foam detail, face transforms with a sensation of pure freshness — eyes slowly close, head tilts back in pleasure, hair moves in slow motion, camera PULLS BACK dramatically revealing the full confident glowing result, ${persona} opens eyes and looks directly into camera with intensity, product held toward lens in a bold final frame.`,

    geral: (p, persona) =>
        `${persona} presents the ${p} directly to camera with a powerful bold gesture — product FLOATS dramatically into frame from below in cinematic slow motion, camera CRASHES aggressively toward the product in an intense push-in, ${persona} reacts with explosive excitement and energy matching the product, camera circles the product in a fast orbital move, final frame is a bold extreme close-up of the product with perfect cinematic rim lighting and lens flare.`,
}

function gerar() {
    if (!produto.value.trim() || !valor.value.trim()) return

    const p       = produto.value.trim()
    const v       = valor.value.trim()
    const persona = getPersonagemPrompt()
    const mood    = getMoodPrompt()
    const action  = selected.value.action

    const imagem =
`${p} held in hand by ${persona},
commercial advertising photography, product in perfect sharp focus,
${mood},
price tag clearly showing ${v},
shot on ARRI Alexa cinema camera, f/1.2 ultra shallow depth of field,
dramatic bokeh background, hyperdetailed product texture and label,
ultra realistic, 8K, advertising quality,
Brazilian retail commercial campaign --ar 9:16 --style raw --stylize 820 --v 6.1`

    const actionText = (ACTIONS[action] ?? ACTIONS.geral)(p, persona)
    const video =
`${actionText}
${mood}, cinematic color grade, anamorphic lens flare,
dramatic motivated camera movement, motion blur on background,
slow motion impact moments at 120fps, product always sharp and centered,
broadcast commercial quality, 6 seconds, seamless continuous shot.`

    output.value = { imagem, video }
    copied.value = null

    setTimeout(() => {
        document.querySelector('.out-box')?.scrollIntoView({ behavior: 'smooth' })
    }, 50)
}

async function copiar(key) {
    await navigator.clipboard.writeText(output.value[key])
    copied.value = key
    setTimeout(() => { if (copied.value === key) copied.value = null }, 2000)
}
</script>

<style scoped>
@reference "tailwindcss";

.section { @apply mb-6; }

.field-label {
    display: block;
    font-size: 9px;
    letter-spacing: 3px;
    text-transform: uppercase;
    color: var(--text-muted);
    margin-bottom: 10px;
    font-weight: 600;
}

.field-input {
    width: 100%;
    padding: 12px 14px;
    background: rgba(255,255,255,0.04);
    border: 1px solid var(--border-subtle);
    border-radius: 10px;
    color: #f0f0f0;
    font-size: 14px;
    outline: none;
    transition: border-color 0.2s;
}
.field-input:focus { border-color: var(--brand); }
.field-input::placeholder { color: #3a3a4a; }

.chip {
    padding: 11px 10px;
    background: rgba(255,255,255,0.04);
    border: 1px solid var(--border-subtle);
    border-radius: 10px;
    cursor: pointer;
    transition: all 0.18s;
    text-align: center;
}
.chip:hover { border-color: color-mix(in srgb, var(--brand) 40%, transparent); }
.chip.active {
    border-color: var(--brand);
    background: color-mix(in srgb, var(--brand) 12%, transparent);
}

.chip-title {
    font-size: 12px;
    font-weight: 700;
    color: #e0e0e0;
    margin-bottom: 2px;
}
.chip.active .chip-title { color: var(--brand); }

.chip-sub {
    font-size: 10px;
    color: var(--text-muted);
}
.chip.active .chip-sub { color: color-mix(in srgb, var(--brand) 60%, #fff); }

.divider {
    height: 1px;
    background: var(--border-subtle);
    margin: 8px 0 4px;
}

.out-box {
    background: rgba(255,255,255,0.025);
    border: 1px solid var(--border-subtle);
    border-radius: 12px;
    padding: 18px;
}

.out-label {
    font-size: 9px;
    letter-spacing: 3px;
    text-transform: uppercase;
    color: var(--brand);
    margin-bottom: 12px;
    font-weight: 700;
}

.out-text {
    font-size: 11px;
    line-height: 1.9;
    color: #8899aa;
    font-family: monospace;
    white-space: pre-wrap;
    word-break: break-word;
}

.copy-btn {
    width: 100%;
    padding: 9px;
    background: transparent;
    color: var(--brand);
    border: 1px solid color-mix(in srgb, var(--brand) 30%, transparent);
    border-radius: 8px;
    font-size: 9px;
    letter-spacing: 2px;
    text-transform: uppercase;
    cursor: pointer;
    margin-top: 12px;
    transition: all 0.2s;
    font-weight: 700;
}
.copy-btn:hover { background: color-mix(in srgb, var(--brand) 10%, transparent); }
</style>
