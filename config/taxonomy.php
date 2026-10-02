<?php

/*
| Seed taxonomy for problem categories. Keywords drive the offline classifier and are editable
| from the admin panel afterwards. `questions` are category-specific intake questions.
*/

return [
    [
        'slug' => 'energy', 'icon' => 'bolt', 'default_urgency' => 'medium',
        'name' => ['fa' => 'انرژی', 'en' => 'Energy'],
        'keywords' => [
            'fa' => ['انرژی', 'برق', 'گاز', 'سوخت', 'مصرف', 'قبض', 'دیماند', 'کنتور', 'خاموشی', 'دیزل ژنراتور', 'بخار', 'کمپرسور'],
            'en' => ['energy', 'electricity', 'power', 'gas', 'fuel', 'consumption', 'utility bill', 'kwh', 'outage', 'generator', 'compressor'],
        ],
        'questions' => [
            'fa' => ['افزایش مصرف یا هزینه مربوط به چه بازه‌ای است و حدوداً چند درصد بوده است؟', 'آیا قبض یا گزارش مصرف ماه‌های اخیر را در اختیار دارید؟ در صورت امکان ضمیمه کنید.'],
            'en' => ['Over what period did consumption or cost increase, and by roughly what percentage?', 'Do you have recent utility bills or consumption reports? Please attach them if possible.'],
        ],
        'children' => [
            ['slug' => 'energy-high-consumption', 'name' => ['fa' => 'مصرف بالای انرژی', 'en' => 'High energy consumption'], 'keywords' => ['fa' => ['افزایش مصرف', 'مصرف بالا', 'هزینه انرژی', 'قبض برق', 'قبض گاز'], 'en' => ['high consumption', 'energy cost', 'bill increase', 'consumption increased']]],
            ['slug' => 'energy-outage', 'name' => ['fa' => 'قطعی و ناپایداری برق', 'en' => 'Outages & supply instability'], 'default_urgency' => 'high', 'keywords' => ['fa' => ['قطعی', 'خاموشی', 'افت ولتاژ', 'قطع برق'], 'en' => ['outage', 'blackout', 'voltage drop', 'power cut']]],
            ['slug' => 'energy-renewables', 'name' => ['fa' => 'انرژی تجدیدپذیر و خورشیدی', 'en' => 'Renewables & solar'], 'default_urgency' => 'low', 'keywords' => ['fa' => ['خورشیدی', 'پنل', 'تجدیدپذیر'], 'en' => ['solar', 'renewable', 'panel']]],
        ],
    ],
    [
        'slug' => 'finance', 'icon' => 'wallet', 'default_urgency' => 'medium',
        'name' => ['fa' => 'مالی', 'en' => 'Finance'],
        'keywords' => [
            'fa' => ['مالی', 'نقدینگی', 'وام', 'تسهیلات', 'بانک', 'بدهی', 'حسابداری', 'جریان نقد', 'سود', 'زیان', 'مالیات', 'چک'],
            'en' => ['finance', 'cash flow', 'liquidity', 'loan', 'bank', 'debt', 'accounting', 'profit', 'tax', 'working capital', 'invoice'],
        ],
        'questions' => [
            'fa' => ['کمبود نقدینگی یا مشکل مالی از چه زمانی شروع شده و تقریباً چه مبلغی درگیر است؟', 'آیا صورت‌های مالی یا گزارش جریان نقد اخیر را دارید؟'],
            'en' => ['When did the cash or financial issue start, and roughly what amount is involved?', 'Do you have recent financial statements or a cash-flow report?'],
        ],
        'children' => [
            ['slug' => 'finance-cash-flow', 'name' => ['fa' => 'جریان نقد و نقدینگی', 'en' => 'Cash flow & liquidity'], 'default_urgency' => 'high', 'keywords' => ['fa' => ['نقدینگی', 'جریان نقد', 'حقوق پرسنل', 'مطالبات'], 'en' => ['cash flow', 'liquidity', 'payroll', 'receivables']]],
            ['slug' => 'finance-financing', 'name' => ['fa' => 'تأمین مالی و تسهیلات', 'en' => 'Financing & loans'], 'keywords' => ['fa' => ['وام', 'تسهیلات', 'ضمانت نامه', 'اعتبار'], 'en' => ['loan', 'financing', 'credit line', 'guarantee']]],
            ['slug' => 'finance-tax', 'name' => ['fa' => 'مالیات و حسابرسی', 'en' => 'Tax & audit'], 'keywords' => ['fa' => ['مالیات', 'ارزش افزوده', 'حسابرسی', 'اظهارنامه'], 'en' => ['tax', 'vat', 'audit', 'tax return']]],
        ],
    ],
    [
        'slug' => 'export', 'icon' => 'globe', 'default_urgency' => 'medium',
        'name' => ['fa' => 'صادرات و تجارت خارجی', 'en' => 'Export & trade'],
        'keywords' => [
            'fa' => ['صادرات', 'صادراتی', 'بازار خارجی', 'گمرک', 'واردات', 'ترخیص', 'حمل بین المللی', 'مشتری خارجی', 'ارز'],
            'en' => ['export', 'import', 'customs', 'foreign market', 'international', 'shipping', 'incoterms', 'foreign buyer', 'currency'],
        ],
        'questions' => [
            'fa' => ['به کدام کشور یا بازار هدف صادر می‌کنید یا قصد صادرات دارید؟', 'مانع اصلی در کدام مرحله است: یافتن مشتری، مجوز، گمرک، حمل یا بازگشت ارز؟'],
            'en' => ['Which country or target market are you exporting to, or planning to?', 'Where is the main blocker: finding buyers, permits, customs, shipping or payment repatriation?'],
        ],
        'children' => [
            ['slug' => 'export-market-entry', 'name' => ['fa' => 'ورود به بازار جدید', 'en' => 'Entering a new market'], 'keywords' => ['fa' => ['بازار جدید', 'مشتری خارجی', 'نمایشگاه'], 'en' => ['new market', 'foreign buyer', 'trade fair', 'distributor']]],
            ['slug' => 'export-customs', 'name' => ['fa' => 'گمرک و مقررات', 'en' => 'Customs & regulations'], 'keywords' => ['fa' => ['گمرک', 'ترخیص', 'مجوز صادرات', 'استاندارد'], 'en' => ['customs', 'clearance', 'export permit', 'certificate of origin']]],
        ],
    ],
    [
        'slug' => 'production', 'icon' => 'factory', 'default_urgency' => 'medium',
        'name' => ['fa' => 'تولید و عملیات', 'en' => 'Production & operations'],
        'keywords' => [
            'fa' => ['تولید', 'کارخانه', 'خط تولید', 'ماشین آلات', 'تجهیزات', 'ضایعات', 'کیفیت', 'تعمیرات', 'مواد اولیه', 'ظرفیت', 'بهره وری'],
            'en' => ['production', 'factory', 'manufacturing', 'machinery', 'equipment', 'scrap', 'quality', 'maintenance', 'raw material', 'capacity', 'productivity'],
        ],
        'questions' => [
            'fa' => ['مشکل در کدام بخش از فرایند تولید دیده می‌شود و از چه زمانی؟', 'آیا شاخصی مثل نرخ ضایعات، توقفات یا ظرفیت تولید را اندازه‌گیری می‌کنید؟'],
            'en' => ['In which part of the production process does the problem appear, and since when?', 'Do you measure indicators such as scrap rate, downtime or capacity utilisation?'],
        ],
        'children' => [
            ['slug' => 'production-quality', 'name' => ['fa' => 'کیفیت و ضایعات', 'en' => 'Quality & scrap'], 'keywords' => ['fa' => ['ضایعات', 'کیفیت', 'مرجوعی', 'عیب'], 'en' => ['scrap', 'defect', 'quality', 'returns']]],
            ['slug' => 'production-maintenance', 'name' => ['fa' => 'نگهداری و تعمیرات', 'en' => 'Maintenance & downtime'], 'keywords' => ['fa' => ['تعمیرات', 'خرابی', 'توقف خط', 'نگهداری'], 'en' => ['maintenance', 'breakdown', 'downtime', 'repair']]],
            ['slug' => 'production-supply', 'name' => ['fa' => 'تأمین مواد اولیه', 'en' => 'Raw-material supply'], 'default_urgency' => 'high', 'keywords' => ['fa' => ['مواد اولیه', 'تامین کننده', 'کمبود مواد'], 'en' => ['raw material', 'supplier', 'shortage', 'supply chain']]],
        ],
    ],
    [
        'slug' => 'technology', 'icon' => 'cpu', 'default_urgency' => 'medium',
        'name' => ['fa' => 'فناوری و تحول دیجیتال', 'en' => 'Technology & digital'],
        'keywords' => [
            'fa' => ['نرم افزار', 'سایت', 'وبسایت', 'دیجیتال', 'اتوماسیون', 'سرور', 'شبکه', 'امنیت سایبری', 'داده', 'اپلیکیشن', 'erp', 'crm'],
            'en' => ['software', 'website', 'digital', 'automation', 'server', 'network', 'cybersecurity', 'data', 'app', 'erp', 'crm', 'cloud'],
        ],
        'questions' => [
            'fa' => ['در حال حاضر از چه نرم‌افزارها یا سیستم‌هایی استفاده می‌کنید؟', 'هدف اصلی شما از این تغییر فناورانه چیست؟'],
            'en' => ['Which software or systems do you currently use?', 'What is the main goal of this technology change?'],
        ],
        'children' => [
            ['slug' => 'technology-digitalization', 'name' => ['fa' => 'دیجیتالی‌سازی فرایندها', 'en' => 'Process digitalisation'], 'keywords' => ['fa' => ['اتوماسیون', 'erp', 'crm', 'دیجیتال'], 'en' => ['automation', 'erp', 'crm', 'digitalisation']]],
            ['slug' => 'technology-security', 'name' => ['fa' => 'امنیت اطلاعات', 'en' => 'Information security'], 'default_urgency' => 'high', 'keywords' => ['fa' => ['هک', 'امنیت', 'باج افزار', 'ویروس'], 'en' => ['hack', 'security', 'ransomware', 'malware', 'breach']]],
        ],
    ],
    [
        'slug' => 'legal', 'icon' => 'scale', 'default_urgency' => 'high', 'is_sensitive' => true,
        'name' => ['fa' => 'حقوقی و قراردادها', 'en' => 'Legal & contracts'],
        'keywords' => [
            'fa' => ['حقوقی', 'قرارداد', 'شکایت', 'دادگاه', 'مجوز', 'پروانه', 'برند', 'ثبت شرکت', 'علامت تجاری', 'وکیل'],
            'en' => ['legal', 'contract', 'lawsuit', 'court', 'license', 'permit', 'trademark', 'registration', 'compliance', 'lawyer'],
        ],
        'questions' => [
            'fa' => ['آیا موضوع به مرجع قضایی یا اداری ارجاع شده و مهلت قانونی مشخصی دارد؟', 'آیا قرارداد یا مکاتبات مرتبط را در اختیار دارید؟'],
            'en' => ['Has the matter been referred to a court or authority, and is there a legal deadline?', 'Do you have the related contract or correspondence?'],
        ],
        'children' => [
            ['slug' => 'legal-contracts', 'name' => ['fa' => 'قراردادها و اختلافات', 'en' => 'Contracts & disputes'], 'keywords' => ['fa' => ['قرارداد', 'اختلاف', 'فسخ', 'خسارت'], 'en' => ['contract', 'dispute', 'termination', 'damages']]],
            ['slug' => 'legal-licensing', 'name' => ['fa' => 'مجوزها و ثبت', 'en' => 'Licensing & registration'], 'default_urgency' => 'medium', 'keywords' => ['fa' => ['مجوز', 'پروانه', 'ثبت', 'علامت تجاری'], 'en' => ['license', 'permit', 'registration', 'trademark']]],
        ],
    ],
    [
        'slug' => 'hr', 'icon' => 'users', 'default_urgency' => 'medium',
        'name' => ['fa' => 'منابع انسانی', 'en' => 'Human resources'],
        'keywords' => [
            'fa' => ['نیرو', 'کارمند', 'پرسنل', 'استخدام', 'جذب', 'آموزش کارکنان', 'حقوق و دستمزد', 'بیمه', 'ترک کار', 'منابع انسانی'],
            'en' => ['staff', 'employee', 'hiring', 'recruitment', 'training', 'payroll', 'retention', 'turnover', 'hr', 'talent'],
        ],
        'questions' => [
            'fa' => ['مشکل منابع انسانی بیشتر در جذب، نگهداشت یا مهارت نیروهاست؟', 'چه تعداد نیرو و در چه نقش‌هایی درگیر هستند؟'],
            'en' => ['Is the HR issue mainly about hiring, retention or skills?', 'How many people, and in which roles, are affected?'],
        ],
        'children' => [
            ['slug' => 'hr-hiring', 'name' => ['fa' => 'جذب و استخدام', 'en' => 'Hiring'], 'keywords' => ['fa' => ['استخدام', 'جذب', 'نیروی متخصص'], 'en' => ['hiring', 'recruitment', 'skilled workers']]],
            ['slug' => 'hr-retention', 'name' => ['fa' => 'نگهداشت و انگیزش', 'en' => 'Retention & motivation'], 'keywords' => ['fa' => ['ترک کار', 'نگهداشت', 'انگیزه'], 'en' => ['turnover', 'retention', 'motivation']]],
        ],
    ],
    [
        'slug' => 'marketing', 'icon' => 'megaphone', 'default_urgency' => 'low',
        'name' => ['fa' => 'بازاریابی و فروش', 'en' => 'Marketing & sales'],
        'keywords' => [
            'fa' => ['فروش', 'بازاریابی', 'مشتری', 'تبلیغات', 'برندینگ', 'قیمت گذاری', 'رقبا', 'شبکه فروش', 'بازار', 'شبکه اجتماعی'],
            'en' => ['sales', 'marketing', 'customer', 'advertising', 'branding', 'pricing', 'competitor', 'distribution', 'market share', 'social media'],
        ],
        'questions' => [
            'fa' => ['فروش در چه بازه‌ای و به چه میزان تغییر کرده است؟', 'مشتریان اصلی شما چه کسانی هستند و از چه کانال‌هایی می‌فروشید؟'],
            'en' => ['Over what period and by how much have sales changed?', 'Who are your main customers and through which channels do you sell?'],
        ],
        'children' => [
            ['slug' => 'marketing-sales-drop', 'name' => ['fa' => 'کاهش فروش', 'en' => 'Falling sales'], 'default_urgency' => 'medium', 'keywords' => ['fa' => ['کاهش فروش', 'افت فروش', 'فروش کم'], 'en' => ['sales drop', 'falling sales', 'declining sales']]],
            ['slug' => 'marketing-digital', 'name' => ['fa' => 'بازاریابی دیجیتال', 'en' => 'Digital marketing'], 'keywords' => ['fa' => ['شبکه اجتماعی', 'اینستاگرام', 'سئو', 'تبلیغات آنلاین'], 'en' => ['social media', 'instagram', 'seo', 'online ads']]],
        ],
    ],
    [
        'slug' => 'investment', 'icon' => 'trending-up', 'default_urgency' => 'low',
        'name' => ['fa' => 'سرمایه‌گذاری و توسعه', 'en' => 'Investment & growth'],
        'keywords' => [
            'fa' => ['سرمایه گذاری', 'سرمایه گذار', 'توسعه', 'گسترش', 'طرح توجیهی', 'شریک', 'جذب سرمایه', 'ارزش گذاری'],
            'en' => ['investment', 'investor', 'expansion', 'growth', 'feasibility', 'partner', 'fundraising', 'valuation'],
        ],
        'questions' => [
            'fa' => ['برای چه طرحی و حدوداً چه میزان سرمایه نیاز دارید؟', 'آیا طرح توجیهی یا برنامه کسب‌وکار آماده دارید؟'],
            'en' => ['What project is the investment for, and roughly how much is needed?', 'Do you have a feasibility study or business plan ready?'],
        ],
        'children' => [
            ['slug' => 'investment-fundraising', 'name' => ['fa' => 'جذب سرمایه', 'en' => 'Fundraising'], 'keywords' => ['fa' => ['جذب سرمایه', 'سرمایه گذار', 'شریک'], 'en' => ['fundraising', 'investor', 'equity']]],
            ['slug' => 'investment-feasibility', 'name' => ['fa' => 'طرح توجیهی', 'en' => 'Feasibility studies'], 'keywords' => ['fa' => ['طرح توجیهی', 'امکان سنجی'], 'en' => ['feasibility', 'business plan']]],
        ],
    ],
    [
        'slug' => 'general', 'icon' => 'sparkles', 'default_urgency' => 'medium',
        'name' => ['fa' => 'سایر / نیازمند بررسی', 'en' => 'Other / needs review'],
        'keywords' => ['fa' => [], 'en' => []],
        'questions' => [
            'fa' => ['لطفاً کمی بیشتر توضیح دهید: مشکل دقیقاً چه تأثیری روی کسب‌وکار شما گذاشته است؟'],
            'en' => ['Please tell us a bit more: what exactly is the impact on your business?'],
        ],
        'children' => [],
    ],
];
