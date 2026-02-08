<?php

declare(strict_types=1);

return [
    'exception' => [
        'budget_exceeded' => 'स्कोप ":scope":scope_id_display के लिए AI बजट पार हो गया। सीमा: :limit, उपयोग: :used',
        'ai_disabled' => 'AI सुविधाएँ वर्तमान में अक्षम हैं।',
    ],
    'middleware' => [
        'ai_disabled' => 'AI अक्षम है',
        'budget_exceeded' => 'AI बजट पार हो गया',
    ],
    'report' => [
        'title' => '=== Laravel AI Guard रिपोर्ट ===',
        'metric' => 'माप',
        'value' => 'मान',
        'total_cost' => 'कुल लागत (USD)',
        'total_input_tokens' => 'कुल इनपुट टोकन',
        'total_output_tokens' => 'कुल आउटपुट टोकन',
        'total_requests' => 'कुल अनुरोध',
        'by_model' => 'मॉडल के अनुसार:',
        'by_tag' => 'टैग के अनुसार:',
        'top_users' => 'लागत के अनुसार शीर्ष उपयोगकर्ता:',
        'untagged' => 'बिना टैग',
        'requests_count' => ':count अनुरोध',
    ],
    'command' => [
        'report_description' => 'AI उपयोग और लागत सारांश दिखाएं',
        'reset_description' => 'समाप्त अवधियों के लिए AI बजट उपयोग रीसेट करें',
        'would_reset' => 'रीसेट होगा: :scope/:scope_id/:period',
        'dry_run_suffix' => ' (ड्राई रन)',
        'dry_run_message' => 'ड्राई रन: :count बजट रीसेट होंगे।',
        'reset_success' => ':count बजट रीसेट हो गए।',
    ],
];
