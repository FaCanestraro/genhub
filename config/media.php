<?php

// Configuração do binário ffmpeg, usado pelo StoryboardVideoService para concatenar
// os clipes gerados em vídeos multi-corte.
return [
    'ffmpeg_binary' => env('FFMPEG_BINARY', 'ffmpeg'),
    'ffmpeg_timeout' => (int) env('FFMPEG_TIMEOUT', 120),

    // Servidor local do LM Studio (API compatível com OpenAI) usado como primeira opção pro
    // planejamento de storyboard, antes de cair pro Gemini — evita gastar cota da API pra uma
    // decisão barata (tem corte múltiplo ou não). Se não estiver rodando, o código detecta e
    // cai pro Gemini automaticamente.
    'local_llm_url' => env('LOCAL_LLM_URL', 'http://localhost:1234/v1'),
];
