<?php

// Estimated per-unit AI cost used by the admin panel to show how much each client is
// "weighing" on AI usage. These are approximate public list prices (checked Aug/2026 —
// gemini-3.1-flash-image at ~1K output ~US$0.067/image, Veo 3.1 1080p ~US$0.40/s x 8s clips,
// Gemini 2.5 Flash text calls are cheap so we use a small flat estimate). Not real billing —
// adjust freely, and update if the models in GeminiService change.
return [
    'image' => 0.067,
    'video' => 3.20,
    'text'  => 0.01,
];
