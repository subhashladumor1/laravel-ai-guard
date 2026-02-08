<?php

declare(strict_types=1);

return [
    'exception' => [
        'budget_exceeded' => 'স্কোপ ":scope":scope_id_display এর জন্য AI বাজেট অতিক্রম করেছে। সীমা: :limit, ব্যবহার: :used',
        'ai_disabled' => 'AI বৈশিষ্ট্য বর্তমানে নিষ্ক্রিয়।',
    ],
    'middleware' => [
        'ai_disabled' => 'AI নিষ্ক্রিয়',
        'budget_exceeded' => 'AI বাজেট অতিক্রম করেছে',
    ],
    'report' => [
        'title' => '=== Laravel AI Guard রিপোর্ট ===',
        'metric' => 'মেট্রিক',
        'value' => 'মান',
        'total_cost' => 'মোট খরচ (USD)',
        'total_input_tokens' => 'মোট ইনপুট টোকেন',
        'total_output_tokens' => 'মোট আউটপুট টোকেন',
        'total_requests' => 'মোট অনুরোধ',
        'by_model' => 'মডেল অনুযায়ী:',
        'by_tag' => 'ট্যাগ অনুযায়ী:',
        'top_users' => 'খরচ অনুযায়ী শীর্ষ ব্যবহারকারী:',
        'untagged' => 'ট্যাগবিহীন',
        'requests_count' => ':count অনুরোধ',
    ],
    'command' => [
        'report_description' => 'AI ব্যবহার এবং খরচ সারাংশ প্রদর্শন করুন',
        'reset_description' => 'মেয়াদোত্তীর্ণ সময়ের জন্য AI বাজেট ব্যবহার রিসেট করুন',
        'would_reset' => 'রিসেট হবে: :scope/:scope_id/:period',
        'dry_run_suffix' => ' (ড্রাই রান)',
        'dry_run_message' => 'ড্রাই রান: :count বাজেট রিসেট হবে।',
        'reset_success' => ':count বাজেট রিসেট হয়েছে।',
    ],
];
