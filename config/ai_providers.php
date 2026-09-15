<?php

// Catalog of AI providers that can be configured under Configurações > Inteligência Artificial.
// `available` controls whether the provider is actually wired to a service today;
// unavailable providers are shown in the UI as "Em breve".
return [
    'gemini' => [
        'label' => 'Google Gemini',
        'capabilities' => ['text', 'image', 'video', 'carousel'],
        'available' => true,
    ],
    'openai' => [
        'label' => 'OpenAI',
        'capabilities' => ['text', 'image'],
        'available' => false,
    ],
    'muapi' => [
        'label' => 'MuAPI (multi-IA)',
        'capabilities' => ['image', 'video'], // ponytail: texto/carrossel ficam de fora até a MuAPI publicar doc
        'available' => true,
    ],
    'comfyui' => [
        'label' => 'ComfyUI (local)',
        'capabilities' => ['image', 'video'],
        'available' => true,
    ],
];
