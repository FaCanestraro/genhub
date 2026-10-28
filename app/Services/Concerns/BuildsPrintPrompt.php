<?php

namespace App\Services\Concerns;

use App\Models\Action;

/**
 * Prompt dos formatos de impressão (cartaz e tabloide). Fica num trait porque os três
 * providers de imagem (Gemini, MuAPI e ComfyUI) precisam exatamente do mesmo texto — só a
 * forma de chamar o modelo muda. Diferente da geração de imagem normal, aqui o texto na
 * arte é o ponto central (preço em destaque), então as regras de "sem texto na imagem" da
 * foto publicitária não valem.
 */
trait BuildsPrintPrompt
{
    /** Tamanhos padrão a 300 dpi, usados quando a ação não traz uma resolução válida. */
    private const PRINT_DEFAULTS = [
        'cartaz'   => '2480x3508', // A4 retrato
        'tabloide' => '3508x4961', // A3 retrato
    ];

    protected function printResolution(Action $action, string $format): string
    {
        $resolution = (string) ($action->resolution ?? '');

        return preg_match('/^\d+x\d+$/', $resolution)
            ? $resolution
            : (self::PRINT_DEFAULTS[$format] ?? self::PRINT_DEFAULTS['cartaz']);
    }

    protected function printLayoutPrompt(Action $action, string $productContext, ?string $extraPrompt, string $format): string
    {
        $resolution = $this->printResolution($action, $format);
        [$width, $height] = explode('x', $resolution);

        $formatRules = $format === 'tabloide'
            ? <<<RULES
            FORMAT: Brazilian supermarket/retail TABLOID offer page (encarte de ofertas), A3 portrait proportion.
            - Grid layout with 4 to 8 offer blocks, each block with one product and its own price
            - The price is the hero of every block: very large, bold, maximum contrast
            - Header band for the campaign headline, footer band for the offer validity line
            RULES
            : <<<RULES
            FORMAT: Brazilian retail single-product POSTER (cartaz de oferta), A4 portrait proportion.
            - One product as the hero, centered and large
            - One price as the dominant element of the whole layout: very large, bold, maximum contrast
            - Short headline above or beside the product, small validity line at the bottom
            RULES;

        return trim(<<<PROMPT
        Create a print-ready retail offer layout for a Brazilian store.
        Output resolution: {$resolution} px (width: {$width}px × height: {$height}px), portrait print layout at 300 dpi.

        {$formatRules}

        PRODUCTS TO FEATURE:
        {$productContext}

        CREATIVE BRIEF (campaign, offer, mood):
        {$action->brief}

        {$extraPrompt}

        MANDATORY REQUIREMENTS:
        - Reproduce the product packaging/label text exactly as-is — never alter, invent, or redesign it
        - This is a graphic layout, NOT a photograph: flat composition, clear reading order, generous margins
        - ALL text must be in Brazilian Portuguese, correctly spelled and typographically clean
        - Prices in Brazilian format (R$ 12,99), large enough to read from a distance
        - Product photos cleanly cut out over solid or gradient backgrounds
        - High contrast between text and background; never put text over a busy area of a photo
        - No watermarks, no lorem ipsum, no placeholder text, no fake barcodes
        - Keep the whole composition inside the canvas: nothing important cropped at the edges
        PROMPT);
    }
}
