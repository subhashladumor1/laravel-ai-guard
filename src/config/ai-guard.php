<?php

declare(strict_types=1);

return [

    'ai_disabled' => env('AI_GUARD_DISABLED', false),

    /*
    |--------------------------------------------------------------------------
    | Model pricing (per 1k tokens unless noted)
    | Sources: Official pricing pages (OpenAI, Google, Anthropic, xAI, Mistral, DeepSeek) as of Feb 2026.
    | Optional keys: cached_input, cache_write, input_long, output_long (long context),
    | image_in, image_out, audio_in, audio_out, video_in, video_out,
    | per_image, per_second_video, per_minute_transcription,
    | per_1m_chars_tts, per_1k_web_search, per_1k_embedding.
    |--------------------------------------------------------------------------
    */
    'pricing' => [
        'openai' => [
            // GPT-5 Family (2026 Flagship)
            'gpt-5.2-pro' => ['input' => 0.021, 'output' => 0.168, 'cached_input' => 0.0105], // $21 / $168
            'gpt-5.2' => ['input' => 0.00175, 'output' => 0.014, 'cached_input' => 0.000875], // $1.75 / $14
            'gpt-5-mini' => ['input' => 0.00025, 'output' => 0.002, 'cached_input' => 0.000125], // $0.25 / $2.00
            'gpt-5-nano' => ['input' => 0.00005, 'output' => 0.000005], // $0.05 / $0.005

            // GPT-4o Family
            'gpt-4o' => ['input' => 0.005, 'output' => 0.02, 'cached_input' => 0.0025, 'audio_in' => 0.01, 'audio_out' => 0.04], // $5 / $20
            'gpt-4o-mini' => ['input' => 0.0006, 'output' => 0.0024, 'cached_input' => 0.0003], // $0.60 / $2.40

            // Realtime
            'gpt-realtime' => ['input' => 0.004, 'output' => 0.016, 'audio_in' => 0.02, 'audio_out' => 0.08], // Text $4/$16

            // o-Series (Reasoning)
            'o1' => ['input' => 0.015, 'output' => 0.06, 'cached_input' => 0.0075],
            'o3' => ['input' => 0.002, 'output' => 0.008],
            'o3-mini' => ['input' => 0.0011, 'output' => 0.0044],

            // Legacy / Other
            'gpt-4-turbo' => ['input' => 0.01, 'output' => 0.03],
            'gpt-3.5-turbo' => ['input' => 0.0005, 'output' => 0.0015],

            // Embeddings
            'text-embedding-3-small' => ['per_1k_embedding' => 0.00002],
            'text-embedding-3-large' => ['per_1k_embedding' => 0.00013],

            // Image Generation (DALL-E)
            'dall-e-3' => ['per_image' => 0.040], // Standard

            // Audio (Whisper / TTS)
            'whisper' => ['per_minute_transcription' => 0.006],
            'tts' => ['per_1m_chars_tts' => 15.0],
        ],
        'anthropic' => [
            // Claude 4.5
            'claude-4-5-opus' => ['input' => 0.005, 'output' => 0.025, 'cached_input' => 0.0005],
            'claude-4-5-sonnet' => ['input' => 0.003, 'output' => 0.015, 'cached_input' => 0.0003], // $3 / $15
            'claude-4-5-haiku' => ['input' => 0.001, 'output' => 0.005, 'cached_input' => 0.0001], // $1 / $5

            // Claude 3.5
            'claude-3-5-sonnet' => ['input' => 0.003, 'output' => 0.015, 'cached_input' => 0.000375],
            'claude-3-5-haiku' => ['input' => 0.001, 'output' => 0.005],

            // Claude 3
            'claude-3-opus' => ['input' => 0.015, 'output' => 0.075],
        ],
        'gemini' => [
            // Gemini 3 (2026)
            'gemini-3-pro' => ['input' => 0.002, 'output' => 0.012, 'input_long' => 0.004, 'output_long' => 0.018], // $2/$12 ($4/$18 long)
            'gemini-3-flash' => ['input' => 0.0005, 'output' => 0.003], // $0.50 / $3.00

            // Gemini 2.5
            'gemini-2.5-pro' => ['input' => 0.00125, 'output' => 0.01, 'input_long' => 0.0025, 'output_long' => 0.015], // $1.25 / $10
            'gemini-2.5-flash' => ['input' => 0.00015, 'output' => 0.0006, 'audio_in' => 0.001, 'video_in' => 0.00015], // $0.15 text/video, $1 audio

            // Gemini 1.5
            'gemini-1.5-pro' => ['input' => 0.0035, 'output' => 0.0105],
            'gemini-1.5-flash' => ['input' => 0.000075, 'output' => 0.0003],

            // Imagen / Veo
            'imagen-3' => ['per_image' => 0.04],
            'veo-3.0' => ['per_second_video' => 0.40],
        ],
        'xai' => [
            // Grok
            'grok-4' => ['input' => 0.003, 'output' => 0.015], // $3 / $15
            'grok-4-fast' => ['input' => 0.0002, 'output' => 0.0005], // $0.20 / $0.50
            'grok-3' => ['input' => 0.002, 'output' => 0.010],
            'grok-3-mini' => ['input' => 0.0002, 'output' => 0.0008],
        ],
        'mistral' => [
            'mistral-large-latest' => ['input' => 0.003, 'output' => 0.009], // $3 / $9 (Large 2)
            'mistral-medium' => ['input' => 0.0004, 'output' => 0.002],
            'mistral-small' => ['input' => 0.0002, 'output' => 0.0006],
            'codestral' => ['input' => 0.001, 'output' => 0.003],
        ],
        'deepseek' => [
            'deepseek-chat' => ['input' => 0.00014, 'output' => 0.00028, 'cached_input' => 0.000014], // V3 $0.14 / $0.28
            'deepseek-reasoner' => ['input' => 0.00028, 'output' => 0.00042, 'cached_input' => 0.000028], // R1
        ],
    ],

    'default_model' => 'gpt-5.2',

    'default_provider' => 'openai',

    'budgets' => [
        'global' => [
            'limit' => env('AI_GUARD_GLOBAL_LIMIT', 100.0),
            'period' => 'monthly',
        ],
        'user' => [
            'limit' => env('AI_GUARD_USER_LIMIT', 10.0),
            'period' => 'monthly',
        ],
        'tenant' => [
            'limit' => env('AI_GUARD_TENANT_LIMIT', 50.0),
            'period' => 'monthly',
        ],
    ],

    'periods' => [
        'daily' => [
            'interval' => 'P1D',
            'resets_at' => 'midnight',
        ],
        'monthly' => [
            'interval' => 'P1M',
            'resets_at' => 'first_of_month',
        ],
    ],

    'estimation' => [
        'chars_per_token' => 4,
        'default_input_multiplier' => 1.0,
        'default_output_multiplier' => 0.5,
    ],

    'middleware' => [
        'alias' => 'ai.guard',
    ],

];