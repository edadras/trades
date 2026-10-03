<?php

return [
    'name' => env('APP_NAME', 'Hamyar'),

    'locales' => ['fa', 'en'],
    'rtl_locales' => ['fa'],

    // Pilot-friendly operational thresholds.
    'initial_review_sla_hours' => (int) env('PLATFORM_INITIAL_REVIEW_SLA_HOURS', 48),
    'require_admin_2fa' => (bool) env('PLATFORM_REQUIRE_ADMIN_2FA', true),
    'max_matches_per_case' => 3,
    'otp_ttl_minutes' => 10,
    // Days the business has to confirm or dispute an outcome proposed by an expert before it is auto-confirmed.
    'outcome_confirmation_days' => (int) env('PLATFORM_OUTCOME_CONFIRMATION_DAYS', 14),

    'uploads' => [
        'disk' => env('PRIVATE_FILES_DISK', 'private'),
        'max_kb' => (int) env('UPLOAD_MAX_KB', 20480),
        'mimes' => ['pdf', 'doc', 'docx', 'xls', 'xlsx', 'csv', 'ppt', 'pptx', 'txt', 'jpg', 'jpeg', 'png', 'webp', 'zip'],
        'voice_mimes' => ['webm', 'ogg', 'mp3', 'm4a', 'wav', 'mp4', 'weba'],
        'signed_url_minutes' => 10,
    ],

    'scanner' => [
        // clamav | none. With "none" files are marked "skipped" and remain downloadable.
        'driver' => env('FILE_SCANNER', 'none'),
        'clamav_host' => env('CLAMAV_HOST', '127.0.0.1'),
        'clamav_port' => (int) env('CLAMAV_PORT', 3310),
        'clamav_socket' => env('CLAMAV_SOCKET'),
    ],

    'retention' => [
        'closed_case_files_days' => (int) env('RETENTION_CLOSED_CASE_FILES_DAYS', 730),
        'audit_log_days' => (int) env('RETENTION_AUDIT_LOG_DAYS', 1095),
        'ai_messages_days' => (int) env('RETENTION_AI_MESSAGES_DAYS', 365),
        'soft_deleted_days' => (int) env('RETENTION_SOFT_DELETED_DAYS', 90),
        'data_exports_days' => (int) env('RETENTION_DATA_EXPORTS_DAYS', 7),
    ],

    'backup' => [
        'disk' => env('BACKUP_DISK', 'backups'),
        'keep' => (int) env('BACKUP_KEEP', 14),
    ],

    'messaging_channels' => [
        // Architecture is in place for SMS/WhatsApp; the "log" driver just records outgoing messages.
        'sms' => env('SMS_DRIVER', 'log'),
        'whatsapp' => env('WHATSAPP_DRIVER', 'log'),
        // SMS: "kavenegar" (Iranian gateway) or "webhook" (POST {to, text} to any gateway); WhatsApp: "cloud_api" (Meta).
        'kavenegar' => ['api_key' => env('KAVENEGAR_API_KEY'), 'sender' => env('KAVENEGAR_SENDER')],
        'sms_webhook' => ['url' => env('SMS_WEBHOOK_URL'), 'token' => env('SMS_WEBHOOK_TOKEN')],
        'whatsapp_cloud' => ['token' => env('WHATSAPP_TOKEN'), 'phone_number_id' => env('WHATSAPP_PHONE_NUMBER_ID'), 'version' => env('WHATSAPP_API_VERSION', 'v21.0')],
    ],

    'industries' => [
        'manufacturing', 'food', 'textile', 'petrochemical', 'agriculture', 'construction', 'retail', 'wholesale',
        'logistics', 'it_software', 'healthcare', 'education', 'tourism', 'energy', 'mining', 'automotive',
        'handicrafts', 'financial_services', 'creative', 'other',
    ],

    'company_sizes' => ['micro', 'small', 'medium', 'large'],
    'employee_ranges' => ['1-9', '10-49', '50-249', '250-999', '1000+'],
    'main_needs' => ['finance', 'export', 'production', 'energy', 'technology', 'legal', 'hr', 'marketing', 'investment', 'training'],

    'countries' => ['IR', 'AE', 'TR', 'DE', 'GB', 'US', 'CA', 'FR', 'NL', 'SE', 'AU', 'OM', 'QA', 'IQ', 'AF', 'AM', 'AZ', 'CN', 'IN', 'RU'],

    /*
     * Value chains used to scope the pilot (at most `max_groups` of them, two by default).
     * Each chain groups the industries that belong to it.
     */
    'value_chains' => [
        'agri_food' => ['agriculture', 'food'],
        'industrial' => ['manufacturing', 'textile', 'petrochemical', 'automotive', 'mining', 'construction'],
        'trade_logistics' => ['retail', 'wholesale', 'logistics'],
        'digital_creative' => ['it_software', 'creative', 'financial_services'],
        'tourism_handicrafts' => ['tourism', 'handicrafts'],
        'energy' => ['energy'],
        'health_education' => ['healthcare', 'education'],
    ],

    'provinces' => [
        'IR' => ['tehran', 'isfahan', 'khorasan_razavi', 'fars', 'east_azerbaijan', 'khuzestan', 'mazandaran', 'alborz', 'gilan', 'kerman', 'qom', 'yazd', 'markazi', 'hormozgan', 'other'],
    ],
];
