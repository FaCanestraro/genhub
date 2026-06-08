<?php

namespace Database\Seeders;

use App\Models\Template;
use App\Models\User;
use Illuminate\Database\Seeder;

class TemplateSeeder extends Seeder
{
    public function run(): void
    {
        $users = User::all();

        if ($users->isEmpty()) {
            $this->command->warn('Nenhum usuário encontrado. Execute primeiro o DatabaseSeeder.');
            return;
        }

        foreach ($users as $user) {
            $existing = $user->templates()->count();
            if ($existing > 0) {
                $this->command->info("Usuário {$user->email} já possui {$existing} template(s). Pulando.");
                continue;
            }

            foreach ($this->templates() as $t) {
                $user->templates()->create($t);
            }

            $this->command->info("✓ {$this->count()} templates criados para {$user->email}");
        }
    }

    private function count(): int
    {
        return count($this->templates());
    }

    private function templates(): array
    {
        // ─── Persona prompts ──────────────────────────────────────────
        $mulher  = 'a beautiful young Brazilian woman, confident and natural smile';
        $homem   = 'a handsome young Brazilian man, energetic and expressive';
        $casal   = 'a happy Brazilian couple, warm and relaxed';
        $crianca = 'a joyful Brazilian child, curious and excited';

        // ─── Mood prompts ─────────────────────────────────────────────
        $quente  = 'warm golden hour lighting, vibrant and energetic, saturated rich colors, sun-kissed atmosphere';
        $cine    = 'dark cinematic lighting, premium and dramatic, deep contrast shadows, rich desaturated tones, film noir quality';
        $clean   = 'bright clean studio lighting, fresh and modern, high key, white and blue tones, minimal background';
        $familiar = 'cozy warm indoor lighting, familiar and comforting, natural earth tones, soft bokeh home environment';

        // ─── Image base suffix ────────────────────────────────────────
        $imgSuffix = "Hyperdetailed product packaging and label clearly visible, fully legible brand name on package.\nShot on ARRI Alexa cinema camera, f/1.4 ultra shallow depth of field, dramatic bokeh background.\nUltra realistic commercial photography, 8K, Brazilian retail advertising quality.";

        // ─── Video base suffix ────────────────────────────────────────
        $vidSuffix = "Cinematic color grade, anamorphic lens flare, dramatic motivated camera movements.\nProduct always sharp and prominently centered in frame throughout.\nBroadcast commercial quality, 6 seconds, seamless continuous shot.";

        return [

            // ═══════════════════════════════════════════════════════════
            //  BEBIDAS — Imagens
            // ═══════════════════════════════════════════════════════════
            [
                'title' => '🥤 Bebida · Mulher · Quente e Vibrante',
                'type'  => 'image',
                'prompt' => "{$mulher} holds the advertised beverage product raised toward camera, condensation droplets gleaming on the cold surface.
Commercial advertising photography, product in perfect sharp focus, center frame.
{$quente}.
{$imgSuffix}",
            ],
            [
                'title' => '🥤 Bebida · Homem · Cinematográfico',
                'type'  => 'image',
                'prompt' => "{$homem} grips the advertised beverage with one hand, product tilted dramatically toward camera against deep shadows.
Commercial advertising photography, product in perfect sharp focus, center frame.
{$cine}.
{$imgSuffix}",
            ],
            [
                'title' => '🥤 Bebida · Casal · Familiar',
                'type'  => 'image',
                'prompt' => "{$casal} sharing and enjoying the advertised beverage together, both smiling warmly at camera, product between them.
Lifestyle advertising photography, product in sharp focus.
{$familiar}.
{$imgSuffix}",
            ],

            // ─── Bebidas — Vídeos ─────────────────────────────────────
            [
                'title' => '🥤 Bebida · Mulher · Quente (Vídeo)',
                'type'  => 'video',
                'prompt' => "{$mulher} grabs the advertised beverage with a fast confident motion and opens it with a sharp powerful twist — condensation droplets EXPLODE toward camera in extreme slow motion, she lifts it dramatically and takes a long deep sip, eyes close in pure pleasure, head tilts back slightly, camera SPINS around her face in a sweeping arc, then CRASHES into an extreme close-up of the bottle label in razor sharp focus, liquid swirling inside visible through the package.
{$quente}. {$vidSuffix}",
            ],
            [
                'title' => '🥤 Bebida · Homem · Cinematográfico (Vídeo)',
                'type'  => 'video',
                'prompt' => "{$homem} reaches for the advertised beverage in slow deliberate motion, opens it with authority — the sound of the cap is emphasized, he raises it toward camera and drinks deeply, camera pushes in on product label in extreme close-up with cinematic lens flare across the surface.
{$cine}. {$vidSuffix}",
            ],

            // ═══════════════════════════════════════════════════════════
            //  ALIMENTOS — Imagens
            // ═══════════════════════════════════════════════════════════
            [
                'title' => '🍫 Alimento · Mulher · Quente e Vibrante',
                'type'  => 'image',
                'prompt' => "{$mulher} holding the advertised food product with both hands raised toward camera, eyes bright with genuine excitement.
Commercial advertising photography, product in perfect sharp focus, center frame.
{$quente}.
{$imgSuffix}",
            ],
            [
                'title' => '🍫 Alimento · Casal · Familiar',
                'type'  => 'image',
                'prompt' => "{$casal} in a cozy kitchen setting, one of them presenting the advertised food product to camera while the other smiles approvingly behind.
Lifestyle advertising photography, product sharp and prominent.
{$familiar}.
{$imgSuffix}",
            ],
            [
                'title' => '🍫 Alimento · Criança · Familiar',
                'type'  => 'image',
                'prompt' => "{$crianca} holding the advertised food product up toward camera with both hands, enormous excited smile, eyes wide with joy.
Lifestyle advertising photography, product in sharp focus, center frame.
{$familiar}.
{$imgSuffix}",
            ],
            [
                'title' => '🍫 Alimento · Homem · Cinematográfico',
                'type'  => 'image',
                'prompt' => "{$homem} presenting the advertised food product on palm toward camera, dramatic under-lighting illuminating the packaging from below.
Commercial advertising photography, product in perfect sharp focus.
{$cine}.
{$imgSuffix}",
            ],

            // ─── Alimentos — Vídeos ───────────────────────────────────
            [
                'title' => '🍫 Alimento · Mulher · Quente (Vídeo)',
                'type'  => 'video',
                'prompt' => "{$mulher} boldly reaches for the advertised food product with excitement, rips it open with a dramatic tearing motion — product contents BURST outward in glorious slow motion beauty shot, she takes a massive first bite captured in extreme close-up with every texture detail visible, eyes go wide with genuine pleasure, camera WHIPS from face to product in a sharp snap cut, then pushes aggressively into the package logo in a powerful crash zoom.
{$quente}. {$vidSuffix}",
            ],
            [
                'title' => '🍫 Alimento · Criança · Familiar (Vídeo)',
                'type'  => 'video',
                'prompt' => "{$crianca} spots the advertised food product and grabs it with both hands excitedly — tears it open, takes an enormous first bite, face transforms into pure joy, camera captures the reaction in extreme close-up, then pulls back to reveal the full delighted expression, product held up triumphantly toward camera in the final frame.
{$familiar}. {$vidSuffix}",
            ],

            // ═══════════════════════════════════════════════════════════
            //  LIMPEZA — Imagens
            // ═══════════════════════════════════════════════════════════
            [
                'title' => '🧴 Limpeza · Mulher · Clean e Fresco',
                'type'  => 'image',
                'prompt' => "{$mulher} holding the advertised cleaning product raised confidently toward camera, sparkling clean background behind her.
Commercial advertising photography, product in perfect sharp focus, center frame.
{$clean}.
{$imgSuffix}",
            ],
            [
                'title' => '🧴 Limpeza · Mulher · Familiar',
                'type'  => 'image',
                'prompt' => "{$mulher} in a bright home environment, holding the advertised cleaning product and gesturing toward a gleaming clean surface, satisfied expression.
Lifestyle advertising photography, product sharp and prominent.
{$familiar}.
{$imgSuffix}",
            ],

            // ─── Limpeza — Vídeos ─────────────────────────────────────
            [
                'title' => '🧴 Limpeza · Mulher · Clean (Vídeo)',
                'type'  => 'video',
                'prompt' => "{$mulher} grabs the advertised cleaning product with determination and sprays it in a powerful dramatic arc — the spray catches the light like liquid diamonds in slow motion, she wipes the surface with one bold confident stroke revealing a GLEAMING clean result, camera swings low in a dramatic low angle capturing the transformation, she turns to camera with a bold satisfied look, product THRUST toward camera in a heroic final frame.
{$clean}. {$vidSuffix}",
            ],
            [
                'title' => '🧴 Limpeza · Mulher · Familiar (Vídeo)',
                'type'  => 'video',
                'prompt' => "{$mulher} in a cozy kitchen sprays the advertised cleaning product on a dirty surface, wipes it clean in one stroke — camera captures the before and after in a split dramatic reveal, she smiles warmly toward camera holding the product, natural home lighting glows warmly around her.
{$familiar}. {$vidSuffix}",
            ],

            // ═══════════════════════════════════════════════════════════
            //  AÇOUGUE / HORTIFRUTI — Imagens
            // ═══════════════════════════════════════════════════════════
            [
                'title' => '🥩 Açougue & Horti · Mulher · Quente e Vibrante',
                'type'  => 'image',
                'prompt' => "{$mulher} lifting the advertised fresh product in a bold heroic presentation toward camera, golden dramatic light emphasizing absolute freshness.
Commercial advertising photography, product in perfect sharp focus, center frame.
{$quente}.
Natural moisture and texture details in extreme macro. {$imgSuffix}",
            ],
            [
                'title' => '🥩 Açougue & Horti · Casal · Familiar',
                'type'  => 'image',
                'prompt' => "{$casal} in a bright market or kitchen setting, both holding and admiring the advertised fresh product, natural healthy atmosphere.
Lifestyle advertising photography, product sharp and prominent.
{$familiar}.
{$imgSuffix}",
            ],

            // ─── Açougue — Vídeos ─────────────────────────────────────
            [
                'title' => '🥩 Açougue & Horti · Mulher · Quente (Vídeo)',
                'type'  => 'video',
                'prompt' => "{$mulher} lifts the advertised fresh product in a bold heroic presentation toward camera — camera starts at extreme low angle rotating upward around the product like a cinematic trophy reveal, dramatic golden light EXPLODES on the product surface emphasizing absolute freshness and perfection, slow motion water droplets or texture detail in extreme macro close-up, camera pulls back with a sweeping crane-like movement revealing the full scene, final frame holds on product glowing under perfect light.
{$quente}. {$vidSuffix}",
            ],
            [
                'title' => '🥩 Açougue & Horti · Família · Familiar (Vídeo)',
                'type'  => 'video',
                'prompt' => "{$casal} at a bright market, one partner picks up the advertised fresh product and holds it toward the other with a warm smile — both react with approval, camera captures the freshness details in close-up, pulls back to show the warm family moment, product framed prominently against natural warm light.
{$familiar}. {$vidSuffix}",
            ],

            // ═══════════════════════════════════════════════════════════
            //  HIGIENE — Imagens
            // ═══════════════════════════════════════════════════════════
            [
                'title' => '🪥 Higiene · Mulher · Clean e Fresco',
                'type'  => 'image',
                'prompt' => "{$mulher} applying the advertised hygiene product with a sweeping elegant motion, glowing fresh skin, product held gracefully toward camera.
Commercial advertising photography, product in perfect sharp focus, center frame.
{$clean}.
{$imgSuffix}",
            ],
            [
                'title' => '🪥 Higiene · Homem · Cinematográfico',
                'type'  => 'image',
                'prompt' => "{$homem} holding the advertised hygiene product in a confident grip angled toward camera, dramatic studio lighting highlighting the packaging.
Commercial advertising photography, product in perfect sharp focus.
{$cine}.
{$imgSuffix}",
            ],
            [
                'title' => '🪥 Higiene · Mulher · Familiar',
                'type'  => 'image',
                'prompt' => "{$mulher} in a bright bathroom setting, smiling warmly while holding the advertised hygiene product, radiating cleanliness and well-being.
Lifestyle advertising photography, product sharp and prominent.
{$familiar}.
{$imgSuffix}",
            ],

            // ─── Higiene — Vídeos ─────────────────────────────────────
            [
                'title' => '🪥 Higiene · Mulher · Clean (Vídeo)',
                'type'  => 'video',
                'prompt' => "{$mulher} applies the advertised hygiene product with a sweeping confident motion, camera follows in extreme close-up capturing every texture and foam detail, face transforms with a sensation of pure freshness — eyes slowly close, head tilts back in pleasure, hair moves in slow motion, camera PULLS BACK dramatically revealing the full confident glowing result, she opens eyes and looks directly into camera with intensity, product held toward lens in a bold final frame.
{$clean}. {$vidSuffix}",
            ],
            [
                'title' => '🪥 Higiene · Homem · Cinematográfico (Vídeo)',
                'type'  => 'video',
                'prompt' => "{$homem} applies the advertised hygiene product in slow deliberate motion, camera captures the product details in dramatic close-up, he straightens up and looks directly into camera with a confident intense stare, product slowly revealed in final hero frame under cinematic rim lighting.
{$cine}. {$vidSuffix}",
            ],

            // ═══════════════════════════════════════════════════════════
            //  GERAL — Imagens
            // ═══════════════════════════════════════════════════════════
            [
                'title' => '📦 Produto Geral · Mulher · Quente e Vibrante',
                'type'  => 'image',
                'prompt' => "{$mulher} presents the advertised product directly to camera with a powerful bold gesture, product floats dramatically at center frame.
Commercial advertising photography, product in perfect sharp focus, center frame.
{$quente}.
{$imgSuffix}",
            ],
            [
                'title' => '📦 Produto Geral · Homem · Cinematográfico',
                'type'  => 'image',
                'prompt' => "{$homem} holds the advertised product with authority, dramatic low-key lighting creating powerful contrast on the packaging.
Commercial advertising photography, product in perfect sharp focus.
{$cine}.
{$imgSuffix}",
            ],
            [
                'title' => '📦 Produto Geral · Casal · Clean e Fresco',
                'type'  => 'image',
                'prompt' => "{$casal} both smiling at camera, one of them presenting the advertised product in a clean minimal setting, modern and fresh atmosphere.
Commercial lifestyle advertising photography, product in sharp focus.
{$clean}.
{$imgSuffix}",
            ],
            [
                'title' => '📦 Produto Geral · Criança · Familiar',
                'type'  => 'image',
                'prompt' => "{$crianca} holding the advertised product up with both hands toward camera, beaming with joy in a warm home environment.
Lifestyle advertising photography, product in sharp focus, center frame.
{$familiar}.
{$imgSuffix}",
            ],

            // ─── Geral — Vídeos ───────────────────────────────────────
            [
                'title' => '📦 Produto Geral · Mulher · Quente (Vídeo)',
                'type'  => 'video',
                'prompt' => "{$mulher} presents the advertised product directly to camera with a powerful bold gesture — product FLOATS dramatically into frame from below in cinematic slow motion, camera CRASHES aggressively toward the product in an intense push-in, she reacts with explosive excitement and energy, camera circles the product in a fast orbital move, final frame is a bold extreme close-up of the product with perfect cinematic rim lighting and lens flare.
{$quente}. {$vidSuffix}",
            ],
            [
                'title' => '📦 Produto Geral · Homem · Cinematográfico (Vídeo)',
                'type'  => 'video',
                'prompt' => "{$homem} slowly lifts the advertised product from shadow into a dramatic spotlight — camera pushes in from wide to extreme close-up as the product label reveals itself, he turns to camera with a confident intense look, product held at chest level in a powerful hero pose, final frame is a dramatic still with cinematic letterbox framing.
{$cine}. {$vidSuffix}",
            ],
            [
                'title' => '📦 Produto Geral · Casal · Familiar (Vídeo)',
                'type'  => 'video',
                'prompt' => "{$casal} in a cozy living room setting, one partner surprises the other with the advertised product — genuine warm reaction, both hold it together toward camera, close-up captures the product label, camera pulls back to reveal the happy family moment, warm golden light wraps the scene.
{$familiar}. {$vidSuffix}",
            ],
        ];
    }
}
