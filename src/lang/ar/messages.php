<?php

declare(strict_types=1);

return [
    'exception' => [
        'budget_exceeded' => 'تم تجاوز ميزانية الذكاء الاصطناعي للنطاق ":scope":scope_id_display. الحد: :limit، المستخدم: :used',
        'ai_disabled' => 'ميزات الذكاء الاصطناعي معطلة حالياً.',
    ],
    'middleware' => [
        'ai_disabled' => 'الذكاء الاصطناعي معطل',
        'budget_exceeded' => 'تم تجاوز ميزانية الذكاء الاصطناعي',
    ],
    'report' => [
        'title' => '=== تقرير Laravel AI Guard ===',
        'metric' => 'المقياس',
        'value' => 'القيمة',
        'total_cost' => 'التكلفة الإجمالية (USD)',
        'total_input_tokens' => 'إجمالي رموز الإدخال',
        'total_output_tokens' => 'إجمالي رموز الإخراج',
        'total_requests' => 'إجمالي الطلبات',
        'by_model' => 'حسب النموذج:',
        'by_tag' => 'حسب الوسم:',
        'top_users' => 'أعلى المستخدمين من حيث التكلفة:',
        'untagged' => 'بدون وسم',
        'requests_count' => ':count طلبات',
    ],
    'command' => [
        'report_description' => 'عرض ملخص استخدام وتكلفة الذكاء الاصطناعي',
        'reset_description' => 'إعادة تعيين استخدام ميزانية الذكاء الاصطناعي للفترات المنتهية',
        'would_reset' => 'سيتم إعادة التعيين: :scope/:scope_id/:period',
        'dry_run_suffix' => ' (تجريبي)',
        'dry_run_message' => 'تجريبي: سيتم إعادة تعيين :count ميزانية.',
        'reset_success' => 'تم إعادة تعيين :count ميزانية.',
    ],
];
