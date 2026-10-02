<?php

/*
| Starter knowledge base: short, practical items, each tied to a specific problem type.
| checklist.{causes,actions,documents,warnings} are the structured facts the guidance engine composes from.
*/

return [
    [
        'slug' => 'diagnose-rising-energy-costs', 'type' => 'guide', 'category' => 'energy', 'problems' => ['energy', 'energy-high-consumption'],
        'industries' => ['manufacturing', 'food', 'textile'], 'minutes' => 6, 'featured' => true, 'cover' => '/images/knowledge/energy.svg',
        'fa' => [
            'title' => 'افزایش ناگهانی هزینه انرژی: از کجا شروع کنیم؟',
            'summary' => 'روشی گام‌به‌گام برای تشخیص اینکه رشد هزینه انرژی از تعرفه، تجهیزات یا الگوی مصرف است.',
            'body' => "## چرا مهم است؟\nدر بسیاری از واحدهای تولیدی، انرژی دومین هزینه متغیر بعد از مواد اولیه است. افزایش ۲۰ تا ۳۰ درصدی معمولاً یکی از سه علت را دارد: تغییر تعرفه، افت راندمان تجهیزات یا تغییر الگوی مصرف.\n\n## گام ۱: هزینه را از مصرف جدا کنید\nقبض‌های ۱۲ ماه اخیر را کنار هم بگذارید و **کیلووات‌ساعت** و **مبلغ** را جدا ثبت کنید. اگر مصرف ثابت مانده و مبلغ بالا رفته، موضوع تعرفه یا دیماند است.\n\n## گام ۲: مصرف را به تولید نسبت دهید\nشاخص «انرژی به ازای هر واحد محصول» را محاسبه کنید. افزایش این شاخص یعنی راندمان افت کرده است.\n\n## گام ۳: مصرف‌کننده‌های بزرگ را بررسی کنید\nکمپرسورهای هوا، دیگ بخار، چیلرها و الکتروموتورهای بزرگ معمولاً بیش از ۷۰٪ مصرف را دارند.\n\n## گام ۴: ساعات اوج را مدیریت کنید\nجابه‌جایی بارهای قابل‌انعطاف به ساعات کم‌باری می‌تواند بدون هزینه سرمایه‌ای صرفه‌جویی ایجاد کند.",
            'checklist' => [
                'causes' => ['افزایش تعرفه یا عبور از سقف دیماند قراردادی', 'نشتی هوای فشرده یا بخار', 'افت راندمان کمپرسورها، دیگ‌ها یا موتورها', 'تغییر شیفت کاری و مصرف در ساعات اوج'],
                'actions' => ['قبض‌های ۱۲ ماه اخیر را کنار هم بگذارید و مصرف (kWh) و مبلغ را جدا کنید', 'شاخص انرژی به ازای هر واحد محصول را ماهانه محاسبه کنید', 'یک بازدید نشت‌یابی از سیستم هوای فشرده انجام دهید', 'بارهای قابل جابه‌جایی را به ساعات کم‌باری منتقل کنید'],
                'documents' => ['قبوض برق و گاز ۱۲ ماه اخیر', 'گزارش تولید ماهانه', 'فهرست تجهیزات پرمصرف و توان نامی آن‌ها'],
                'warnings' => ['پیش از تغییر تنظیمات تجهیزات، الزامات ایمنی و نظر سازنده را بررسی کنید'],
            ],
        ],
        'en' => [
            'title' => 'Energy costs jumped — where do you start?',
            'summary' => 'A step-by-step method to tell whether rising energy costs come from tariffs, equipment or usage patterns.',
            'body' => "## Why it matters\nFor many manufacturers energy is the second-largest variable cost after raw materials. A 20–30% jump usually has one of three causes: a tariff change, falling equipment efficiency or a changed usage pattern.\n\n## Step 1: Separate cost from consumption\nPut the last 12 months of bills side by side and record **kWh** and **amount** separately. If consumption is flat but cost rose, look at tariffs or demand charges.\n\n## Step 2: Relate consumption to output\nCompute *energy per unit produced*. If it rises, efficiency has dropped.\n\n## Step 3: Check the big consumers\nAir compressors, boilers, chillers and large motors typically use more than 70% of the energy.\n\n## Step 4: Manage peak hours\nShifting flexible loads to off-peak hours can save money without capital spending.",
            'checklist' => [
                'causes' => ['Tariff increase or exceeding the contracted demand', 'Compressed-air or steam leaks', 'Lower efficiency of compressors, boilers or motors', 'Shift changes moving consumption into peak hours'],
                'actions' => ['Line up the last 12 months of bills and separate kWh from cost', 'Track energy per unit produced every month', 'Run a leak survey on the compressed-air system', 'Move flexible loads to off-peak hours'],
                'documents' => ['Electricity and gas bills for the last 12 months', 'Monthly production report', 'List of major equipment and rated power'],
                'warnings' => ['Check safety requirements and manufacturer guidance before changing equipment settings'],
            ],
        ],
    ],
    [
        'slug' => 'compressed-air-leak-checklist', 'type' => 'checklist', 'category' => 'energy', 'problems' => ['energy-high-consumption'],
        'industries' => ['manufacturing', 'automotive', 'food'], 'minutes' => 3, 'cover' => '/images/knowledge/air.svg',
        'fa' => [
            'title' => 'چک‌لیست نشت‌یابی هوای فشرده',
            'summary' => 'نشتی هوای فشرده می‌تواند تا ۳۰٪ خروجی کمپرسور را هدر دهد. این چک‌لیست را در یک شیفت تعطیل اجرا کنید.',
            'body' => "## پیش از شروع\nخط تولید را متوقف کنید اما کمپرسور را روشن نگه دارید.\n\n## آزمون افت فشار\nزمان افت فشار مخزن از فشار کاری تا نصف آن را اندازه بگیرید. افت سریع یعنی نشت قابل توجه.\n\n## نقاط پرتکرار نشت\n- اتصالات سریع و شیلنگ‌ها\n- تله‌های آب (درین‌ها)\n- فیلتر-رگلاتورها\n- ابزارهای پنوماتیک فرسوده",
            'checklist' => [
                'causes' => ['نشتی در اتصالات، شیلنگ‌ها و درین‌ها'],
                'actions' => ['آزمون افت فشار را در زمان تعطیلی خط انجام دهید', 'نقاط نشت را برچسب‌گذاری و فهرست کنید', 'فشار کاری کمپرسور را به حداقل نیاز واقعی برسانید'],
                'documents' => ['مشخصات کمپرسور و ساعت کارکرد'],
                'warnings' => ['هرگز با دست نشتی هوای پرفشار را لمس نکنید'],
            ],
        ],
        'en' => [
            'title' => 'Compressed-air leak checklist',
            'summary' => 'Leaks can waste up to 30% of compressor output. Run this checklist during a non-production shift.',
            'body' => "## Before you start\nStop production but keep the compressor running.\n\n## Pressure-decay test\nTime how long the receiver takes to drop from working pressure to half. A fast drop means significant leakage.\n\n## Usual suspects\n- Quick couplings and hoses\n- Condensate drains\n- Filter-regulators\n- Worn pneumatic tools",
            'checklist' => [
                'causes' => ['Leaks at fittings, hoses and drains'],
                'actions' => ['Run a pressure-decay test while the line is stopped', 'Tag and list every leak found', 'Lower compressor set pressure to the real minimum required'],
                'documents' => ['Compressor specification and running hours'],
                'warnings' => ['Never touch a high-pressure air leak with your hand'],
            ],
        ],
    ],
    [
        'slug' => 'prepare-for-power-outages', 'type' => 'guide', 'category' => 'energy', 'problems' => ['energy-outage'],
        'industries' => ['manufacturing', 'food', 'healthcare'], 'minutes' => 5, 'cover' => '/images/knowledge/outage.svg',
        'fa' => [
            'title' => 'آمادگی کسب‌وکار برای قطعی برق',
            'summary' => 'برنامه‌ریزی تولید، حفاظت از تجهیزات و تأمین برق اضطراری در دوره‌های خاموشی.',
            'body' => "## برنامه زمان‌بندی\nجدول خاموشی منطقه را دریافت کنید و برنامه تولید را بر اساس آن تنظیم کنید.\n\n## حفاظت از تجهیزات\nبرای تجهیزات حساس از UPS و محافظ نوسان استفاده کنید.\n\n## برق اضطراری\nظرفیت ژنراتور را بر اساس بارهای حیاتی (نه کل کارخانه) محاسبه کنید.",
            'checklist' => [
                'causes' => ['محدودیت شبکه در ساعات اوج', 'نوسان ولتاژ و حفاظت ناکافی تجهیزات'],
                'actions' => ['فهرست بارهای حیاتی را تهیه کنید', 'برنامه تولید را با جدول خاموشی هماهنگ کنید', 'برای تجهیزات کنترلی UPS در نظر بگیرید'],
                'documents' => ['جدول خاموشی اعلامی', 'فهرست تجهیزات حیاتی'],
                'warnings' => ['اتصال ژنراتور بدون کلید تبدیل استاندارد خطرناک است'],
            ],
        ],
        'en' => [
            'title' => 'Preparing your business for power outages',
            'summary' => 'Production planning, equipment protection and backup power during scheduled outages.',
            'body' => "## Scheduling\nGet the published outage schedule and plan production around it.\n\n## Protect equipment\nUse UPS units and surge protection for sensitive controls.\n\n## Backup power\nSize generators for critical loads, not the whole plant.",
            'checklist' => [
                'causes' => ['Grid constraints at peak hours', 'Voltage swings and insufficient equipment protection'],
                'actions' => ['List your critical loads', 'Align production with the outage schedule', 'Add UPS units for control equipment'],
                'documents' => ['Published outage schedule', 'List of critical equipment'],
                'warnings' => ['Connecting a generator without a proper transfer switch is dangerous'],
            ],
        ],
    ],
    [
        'slug' => '13-week-cash-flow-forecast', 'type' => 'template', 'category' => 'finance', 'problems' => ['finance', 'finance-cash-flow'],
        'industries' => [], 'minutes' => 7, 'featured' => true, 'cover' => '/images/knowledge/cash.svg',
        'fa' => [
            'title' => 'الگوی پیش‌بینی جریان نقد ۱۳ هفته‌ای',
            'summary' => 'ابزاری ساده برای دیدن کمبود نقدینگی پیش از وقوع و تصمیم‌گیری درباره پرداخت‌ها.',
            'body' => "## چرا ۱۳ هفته؟\nیک فصل کامل را پوشش می‌دهد و آن‌قدر کوتاه است که دقیق بماند.\n\n## ساختار\nبرای هر هفته: موجودی ابتدای دوره، دریافتی‌های قطعی، پرداختی‌های قطعی، موجودی پایان دوره.\n\n## نکته کلیدی\nدریافتی‌ها را بر اساس سابقه واقعی وصول مشتریان وارد کنید، نه سررسید فاکتور.",
            'checklist' => [
                'causes' => ['فاصله زمانی بین وصول مطالبات و پرداخت به تأمین‌کنندگان', 'رشد موجودی انبار', 'بازپرداخت همزمان تسهیلات'],
                'actions' => ['پیش‌بینی ۱۳ هفته‌ای جریان نقد را تهیه و هر هفته به‌روز کنید', 'مطالبات معوق را اولویت‌بندی و پیگیری کنید', 'با تأمین‌کنندگان کلیدی درباره زمان پرداخت مذاکره کنید'],
                'documents' => ['گزارش سنی مطالبات', 'فهرست بدهی‌ها و سررسیدها', 'صورت‌حساب‌های بانکی ۳ ماه اخیر'],
                'warnings' => ['صدور چک بدون پشتوانه پیامد حقوقی دارد؛ پیش از تعهد جدید جریان نقد را بررسی کنید'],
            ],
        ],
        'en' => [
            'title' => '13-week cash-flow forecast template',
            'summary' => 'A simple tool to see cash shortfalls before they happen and decide which payments to prioritise.',
            'body' => "## Why 13 weeks?\nIt covers a full quarter while staying short enough to be accurate.\n\n## Structure\nFor each week: opening balance, committed receipts, committed payments, closing balance.\n\n## Key tip\nEnter receipts based on customers' real payment history, not invoice due dates.",
            'checklist' => [
                'causes' => ['Gap between collecting receivables and paying suppliers', 'Growing inventory', 'Several loan repayments falling at once'],
                'actions' => ['Build a 13-week cash-flow forecast and update it weekly', 'Prioritise and chase overdue receivables', 'Negotiate payment timing with key suppliers'],
                'documents' => ['Receivables ageing report', 'List of debts and due dates', 'Bank statements for the last 3 months'],
                'warnings' => ['Issuing uncovered cheques has legal consequences; check cash flow before new commitments'],
            ],
        ],
    ],
    [
        'slug' => 'preparing-a-bank-financing-request', 'type' => 'guide', 'category' => 'finance', 'problems' => ['finance-financing', 'investment-fundraising'],
        'industries' => [], 'minutes' => 6, 'cover' => '/images/knowledge/bank.svg',
        'fa' => [
            'title' => 'آماده‌سازی پرونده درخواست تسهیلات بانکی',
            'summary' => 'آنچه بانک‌ها و صندوق‌ها پیش از تصویب تسهیلات بررسی می‌کنند و چگونه آماده شوید.',
            'body' => "## بانک به چه نگاه می‌کند؟\nتوان بازپرداخت، وثیقه، سابقه اعتباری و منطق مصرف تسهیلات.\n\n## مدارک پایه\nصورت‌های مالی حسابرسی‌شده، اظهارنامه مالیاتی، گردش حساب و طرح توجیهی مصرف وجه.",
            'checklist' => [
                'causes' => ['نبود صورت‌های مالی قابل اتکا', 'ناهماهنگی مبلغ درخواستی با توان بازپرداخت'],
                'actions' => ['جدول بازپرداخت را با جریان نقد پیش‌بینی‌شده مقایسه کنید', 'گزارش اعتباری شرکت را پیش از درخواست بررسی کنید', 'طرح توجیهی کوتاه برای مصرف وجه آماده کنید'],
                'documents' => ['صورت‌های مالی دو سال اخیر', 'اظهارنامه مالیاتی', 'گردش حساب ۶ ماه اخیر'],
                'warnings' => [],
            ],
        ],
        'en' => [
            'title' => 'Preparing a bank financing request',
            'summary' => 'What banks and funds check before approving financing, and how to prepare.',
            'body' => "## What does the bank look at?\nRepayment capacity, collateral, credit history and the logic of how funds will be used.\n\n## Core documents\nAudited financial statements, tax returns, bank statements and a short use-of-funds plan.",
            'checklist' => [
                'causes' => ['No reliable financial statements', 'Requested amount does not match repayment capacity'],
                'actions' => ['Compare the repayment schedule with your forecast cash flow', 'Check the company credit report before applying', 'Prepare a short use-of-funds plan'],
                'documents' => ['Financial statements for the last two years', 'Tax returns', 'Bank statements for the last 6 months'],
                'warnings' => [],
            ],
        ],
    ],
    [
        'slug' => 'choosing-your-first-export-market', 'type' => 'guide', 'category' => 'export', 'problems' => ['export', 'export-market-entry'],
        'industries' => ['food', 'handicrafts', 'textile', 'manufacturing'], 'minutes' => 6, 'featured' => true, 'cover' => '/images/knowledge/export.svg',
        'fa' => [
            'title' => 'انتخاب اولین بازار صادراتی',
            'summary' => 'چارچوبی برای مقایسه بازارهای هدف بر اساس تقاضا، رقابت، دسترسی و ریسک پرداخت.',
            'body' => "## سه بازار، نه سی بازار\nابتدا ۳ بازار نامزد را انتخاب و با معیارهای یکسان مقایسه کنید.\n\n## معیارها\nاندازه تقاضا، تعرفه و الزامات استاندارد، هزینه حمل، روش‌های امن دریافت وجه و شبکه توزیع.",
            'checklist' => [
                'causes' => ['انتخاب بازار بدون داده تقاضا', 'نادیده گرفتن الزامات استاندارد و برچسب‌گذاری کشور مقصد'],
                'actions' => ['سه بازار نامزد را با جدول امتیازدهی مقایسه کنید', 'الزامات استاندارد و مجوزهای کشور مقصد را استخراج کنید', 'روش امن دریافت وجه را پیش از ارسال اول مشخص کنید'],
                'documents' => ['کاتالوگ و مشخصات فنی محصول', 'قیمت تمام‌شده و ظرفیت تولید ماهانه'],
                'warnings' => ['قوانین تحریم و محدودیت‌های بانکی کشور مقصد را با مشاور متخصص بررسی کنید'],
            ],
        ],
        'en' => [
            'title' => 'Choosing your first export market',
            'summary' => 'A framework to compare target markets on demand, competition, access and payment risk.',
            'body' => "## Three markets, not thirty\nShortlist 3 candidate markets and compare them with the same criteria.\n\n## Criteria\nDemand size, tariffs and standards, freight cost, secure payment methods and distribution network.",
            'checklist' => [
                'causes' => ['Choosing a market without demand data', 'Ignoring destination standards and labelling rules'],
                'actions' => ['Compare three candidate markets with a scoring table', 'List destination standards and permits', 'Agree a secure payment method before the first shipment'],
                'documents' => ['Product catalogue and technical specs', 'Unit cost and monthly capacity'],
                'warnings' => ['Review sanctions and banking restrictions with a specialist adviser'],
            ],
        ],
    ],
    [
        'slug' => 'export-customs-documents', 'type' => 'checklist', 'category' => 'export', 'problems' => ['export-customs'],
        'industries' => [], 'minutes' => 3, 'cover' => '/images/knowledge/customs.svg',
        'fa' => [
            'title' => 'چک‌لیست مدارک گمرکی صادرات',
            'summary' => 'مدارک رایج مورد نیاز برای ترخیص صادراتی و نکات جلوگیری از توقف بار.',
            'body' => "## مدارک پایه\nفاکتور تجاری، لیست بسته‌بندی، گواهی مبدأ، بارنامه و مجوزهای خاص کالا.\n\n## خطاهای رایج\nناهمخوانی وزن و شرح کالا بین اسناد.",
            'checklist' => [
                'causes' => ['ناهمخوانی اطلاعات بین اسناد', 'نبود مجوز یا گواهی خاص کالا'],
                'actions' => ['اطلاعات وزن، تعداد و شرح کالا را در همه اسناد یکسان کنید', 'کد تعرفه (HS) کالا را با کارگزار گمرکی تأیید کنید'],
                'documents' => ['فاکتور تجاری', 'لیست بسته‌بندی', 'گواهی مبدأ', 'بارنامه'],
                'warnings' => [],
            ],
        ],
        'en' => [
            'title' => 'Export customs documents checklist',
            'summary' => 'Common documents needed for export clearance and how to avoid shipments being held.',
            'body' => "## Core documents\nCommercial invoice, packing list, certificate of origin, bill of lading and product-specific permits.\n\n## Common mistakes\nWeight and description mismatches between documents.",
            'checklist' => [
                'causes' => ['Mismatched information between documents', 'Missing product-specific permit or certificate'],
                'actions' => ['Make weight, quantity and description identical across documents', 'Confirm the HS code with your customs broker'],
                'documents' => ['Commercial invoice', 'Packing list', 'Certificate of origin', 'Bill of lading'],
                'warnings' => [],
            ],
        ],
    ],
    [
        'slug' => 'reduce-scrap-with-pareto', 'type' => 'guide', 'category' => 'production', 'problems' => ['production', 'production-quality'],
        'industries' => ['manufacturing', 'food', 'textile', 'automotive'], 'minutes' => 5, 'cover' => '/images/knowledge/quality.svg',
        'fa' => [
            'title' => 'کاهش ضایعات با تحلیل پارتو',
            'summary' => 'معمولاً ۸۰٪ ضایعات از ۲۰٪ علت‌ها است. این روش کمک می‌کند همان ۲۰٪ را پیدا کنید.',
            'body' => "## داده جمع کنید\nدو هفته ضایعات را بر اساس نوع عیب، ماشین و شیفت ثبت کنید.\n\n## نمودار پارتو\nعیب‌ها را از بیشترین به کمترین مرتب کنید و روی دو یا سه مورد اول تمرکز کنید.\n\n## ریشه‌یابی\nبرای هر مورد اصلی «۵ چرا» را اجرا کنید.",
            'checklist' => [
                'causes' => ['تنظیمات نادرست ماشین', 'کیفیت متغیر مواد اولیه', 'آموزش ناکافی اپراتورها'],
                'actions' => ['ضایعات را دو هفته بر اساس نوع عیب، ماشین و شیفت ثبت کنید', 'نمودار پارتو بکشید و روی سه علت اول تمرکز کنید', 'برای هر علت اصلی تحلیل ۵ چرا انجام دهید'],
                'documents' => ['گزارش ضایعات و مرجوعی‌ها', 'مشخصات مواد اولیه و تأمین‌کنندگان'],
                'warnings' => [],
            ],
        ],
        'en' => [
            'title' => 'Reducing scrap with Pareto analysis',
            'summary' => 'Usually 80% of scrap comes from 20% of causes. This method helps you find that 20%.',
            'body' => "## Collect data\nLog scrap for two weeks by defect type, machine and shift.\n\n## Pareto chart\nSort defects from most to least frequent and focus on the top two or three.\n\n## Root cause\nRun *5 Whys* for each major defect.",
            'checklist' => [
                'causes' => ['Incorrect machine settings', 'Variable raw-material quality', 'Insufficient operator training'],
                'actions' => ['Log scrap for two weeks by defect, machine and shift', 'Draw a Pareto chart and focus on the top three causes', 'Run a 5 Whys analysis on each top cause'],
                'documents' => ['Scrap and returns report', 'Raw material specs and supplier list'],
                'warnings' => [],
            ],
        ],
    ],
    [
        'slug' => 'preventive-maintenance-starter-plan', 'type' => 'checklist', 'category' => 'production', 'problems' => ['production-maintenance'],
        'industries' => ['manufacturing', 'food', 'automotive'], 'minutes' => 4, 'cover' => '/images/knowledge/maintenance.svg',
        'fa' => [
            'title' => 'برنامه شروع نگهداری پیشگیرانه',
            'summary' => 'از تعمیرات اضطراری به برنامه‌ریزی‌شده: یک برنامه ساده برای ۱۰ ماشین حیاتی.',
            'body' => "## ماشین‌های حیاتی را انتخاب کنید\nماشین‌هایی که توقفشان کل خط را متوقف می‌کند.\n\n## بازرسی‌های روزانه و هفتگی\nروغن‌کاری، دما، لرزش و صدای غیرعادی.",
            'checklist' => [
                'causes' => ['نبود برنامه بازرسی دوره‌ای', 'کمبود قطعات یدکی حیاتی'],
                'actions' => ['۱۰ ماشین حیاتی را فهرست و برای هر کدام چک‌لیست روزانه تعریف کنید', 'زمان توقفات را ثبت کنید تا شاخص MTBF محاسبه شود', 'فهرست قطعات یدکی حیاتی را تهیه کنید'],
                'documents' => ['سوابق توقفات و تعمیرات', 'دفترچه سازنده تجهیزات'],
                'warnings' => ['تعمیرات فقط پس از قفل و برچسب‌گذاری (LOTO) انجام شود'],
            ],
        ],
        'en' => [
            'title' => 'Preventive maintenance starter plan',
            'summary' => 'From firefighting to planned maintenance: a simple plan for your 10 critical machines.',
            'body' => "## Pick critical machines\nThose whose stoppage halts the whole line.\n\n## Daily and weekly checks\nLubrication, temperature, vibration and unusual noise.",
            'checklist' => [
                'causes' => ['No periodic inspection plan', 'Missing critical spare parts'],
                'actions' => ['List 10 critical machines and define a daily checklist for each', 'Log downtime so MTBF can be calculated', 'Build a list of critical spare parts'],
                'documents' => ['Downtime and repair history', 'Equipment manuals'],
                'warnings' => ['Only perform repairs after lock-out/tag-out (LOTO)'],
            ],
        ],
    ],
    [
        'slug' => 'retaining-skilled-workers', 'type' => 'article', 'category' => 'hr', 'problems' => ['hr', 'hr-retention', 'hr-hiring'],
        'industries' => [], 'minutes' => 5, 'cover' => '/images/knowledge/people.svg',
        'fa' => [
            'title' => 'نگهداشت نیروهای ماهر در کسب‌وکارهای کوچک',
            'summary' => 'وقتی نمی‌توانید با حقوق شرکت‌های بزرگ رقابت کنید، چه اهرم‌هایی دارید؟',
            'body' => "## علت ترک کار را بسنجید\nمصاحبه خروج ساده‌ترین منبع داده است.\n\n## اهرم‌های غیرمالی\nمسیر رشد روشن، انعطاف شیفت، آموزش و قدردانی منظم.",
            'checklist' => [
                'causes' => ['نبود مسیر رشد شغلی', 'اختلاف حقوق با بازار', 'سبک مدیریت سرپرستان'],
                'actions' => ['برای هر ترک کار مصاحبه خروج انجام و ثبت کنید', 'برای نقش‌های کلیدی مسیر رشد و آموزش تعریف کنید', 'حقوق نقش‌های کلیدی را با بازار مقایسه کنید'],
                'documents' => ['آمار ترک کار ۱۲ ماه اخیر', 'ساختار حقوق و مزایا'],
                'warnings' => [],
            ],
        ],
        'en' => [
            'title' => 'Retaining skilled workers in small businesses',
            'summary' => 'When you cannot match big-company salaries, what levers do you have?',
            'body' => "## Measure why people leave\nExit interviews are the easiest data source.\n\n## Non-financial levers\nA clear growth path, shift flexibility, training and regular recognition.",
            'checklist' => [
                'causes' => ['No career path', 'Pay gap versus market', 'Supervisors\' management style'],
                'actions' => ['Run and record an exit interview for every leaver', 'Define growth paths and training for key roles', 'Benchmark key-role pay against the market'],
                'documents' => ['Turnover figures for the last 12 months', 'Pay and benefits structure'],
                'warnings' => [],
            ],
        ],
    ],
    [
        'slug' => 'diagnosing-falling-sales', 'type' => 'guide', 'category' => 'marketing', 'problems' => ['marketing', 'marketing-sales-drop'],
        'industries' => ['retail', 'food', 'wholesale'], 'minutes' => 5, 'cover' => '/images/knowledge/sales.svg',
        'fa' => [
            'title' => 'افت فروش: تشخیص پیش از اقدام',
            'summary' => 'قبل از افزایش بودجه تبلیغات، بفهمید فروش دقیقاً در کجا افت کرده است.',
            'body' => "## فروش را تجزیه کنید\nفروش = تعداد مشتری × تعداد خرید × مبلغ هر خرید. کدام جزء افت کرده است؟\n\n## بخش‌بندی\nافت را بر اساس محصول، کانال و منطقه ببینید.",
            'checklist' => [
                'causes' => ['از دست دادن مشتریان کلیدی', 'تغییر قیمت رقبا', 'مشکل در کانال توزیع'],
                'actions' => ['فروش را به تعداد مشتری، دفعات خرید و مبلغ هر خرید تجزیه کنید', 'با ۱۰ مشتری از دست رفته تماس بگیرید و علت را بپرسید', 'قیمت و پیشنهاد سه رقیب اصلی را مقایسه کنید'],
                'documents' => ['گزارش فروش ماهانه به تفکیک محصول و کانال', 'فهرست مشتریان کلیدی'],
                'warnings' => [],
            ],
        ],
        'en' => [
            'title' => 'Falling sales: diagnose before you act',
            'summary' => 'Before raising the ad budget, find out exactly where sales dropped.',
            'body' => "## Decompose sales\nSales = customers × purchase frequency × basket size. Which one fell?\n\n## Segment\nLook at the drop by product, channel and region.",
            'checklist' => [
                'causes' => ['Loss of key customers', 'Competitor price changes', 'Distribution channel problems'],
                'actions' => ['Break sales down into customers, frequency and basket size', 'Call 10 lost customers and ask why', 'Compare price and offer with three main competitors'],
                'documents' => ['Monthly sales by product and channel', 'Key customer list'],
                'warnings' => [],
            ],
        ],
    ],
    [
        'slug' => 'basic-cybersecurity-for-smes', 'type' => 'checklist', 'category' => 'technology', 'problems' => ['technology', 'technology-security'],
        'industries' => [], 'minutes' => 4, 'cover' => '/images/knowledge/security.svg',
        'fa' => [
            'title' => 'امنیت سایبری پایه برای کسب‌وکارهای کوچک',
            'summary' => 'ده اقدام کم‌هزینه که بیشتر حملات رایج را متوقف می‌کند.',
            'body' => "## اقدامات پایه\nپشتیبان‌گیری آفلاین، ورود دومرحله‌ای، به‌روزرسانی سیستم‌ها و آموزش کارکنان درباره فیشینگ.",
            'checklist' => [
                'causes' => ['رمزهای عبور ضعیف یا تکراری', 'نبود پشتیبان آفلاین', 'نرم‌افزارهای به‌روزنشده'],
                'actions' => ['برای ایمیل و سیستم‌های مالی ورود دومرحله‌ای فعال کنید', 'پشتیبان‌گیری آفلاین هفتگی راه‌اندازی و بازیابی آن را آزمایش کنید', 'کارکنان را درباره ایمیل‌های فیشینگ آموزش دهید'],
                'documents' => ['فهرست سیستم‌ها و حساب‌های کلیدی'],
                'warnings' => ['در صورت حمله باج‌افزار، سیستم آلوده را از شبکه جدا کنید و شواهد را پاک نکنید'],
            ],
        ],
        'en' => [
            'title' => 'Basic cybersecurity for SMEs',
            'summary' => 'Ten low-cost steps that stop most common attacks.',
            'body' => "## Basics\nOffline backups, two-factor sign-in, system updates and phishing awareness for staff.",
            'checklist' => [
                'causes' => ['Weak or reused passwords', 'No offline backup', 'Unpatched software'],
                'actions' => ['Enable two-factor sign-in for email and finance systems', 'Set up weekly offline backups and test restoring them', 'Train staff to recognise phishing emails'],
                'documents' => ['List of key systems and accounts'],
                'warnings' => ['In a ransomware attack, disconnect infected systems and do not delete evidence'],
            ],
        ],
    ],
    [
        'slug' => 'contract-dispute-first-steps', 'type' => 'guide', 'category' => 'legal', 'problems' => ['legal', 'legal-contracts'],
        'industries' => [], 'minutes' => 4, 'cover' => '/images/knowledge/legal.svg',
        'fa' => [
            'title' => 'اختلاف قراردادی: اولین گام‌ها',
            'summary' => 'پیش از هر اقدام حقوقی، این موارد را مستند و بررسی کنید. این راهنما جایگزین مشاوره حقوقی نیست.',
            'body' => "## مستندسازی\nقرارداد، الحاقیه‌ها، مکاتبات و صورت‌جلسات را جمع‌آوری کنید.\n\n## مهلت‌ها\nمهلت‌های قانونی و قراردادی (مثل مهلت اعتراض) را فوراً مشخص کنید.",
            'checklist' => [
                'causes' => ['ابهام در شرایط قرارداد', 'عدم ثبت تغییرات توافق‌شده'],
                'actions' => ['همه اسناد و مکاتبات مرتبط را در یک پرونده جمع کنید', 'مهلت‌های قانونی و قراردادی را مشخص کنید', 'پیش از مکاتبه رسمی با وکیل مشورت کنید'],
                'documents' => ['قرارداد و الحاقیه‌ها', 'مکاتبات و صورت‌جلسات'],
                'warnings' => ['این محتوا مشاوره حقوقی نیست؛ برای تصمیم نهایی با وکیل متخصص مشورت کنید'],
            ],
        ],
        'en' => [
            'title' => 'Contract disputes: first steps',
            'summary' => 'Document and check these points before any legal action. This guide does not replace legal advice.',
            'body' => "## Document everything\nCollect the contract, amendments, correspondence and minutes.\n\n## Deadlines\nIdentify legal and contractual deadlines (e.g. objection periods) immediately.",
            'checklist' => [
                'causes' => ['Ambiguous contract terms', 'Agreed changes not recorded'],
                'actions' => ['Gather all related documents and correspondence in one file', 'Identify legal and contractual deadlines', 'Consult a lawyer before formal correspondence'],
                'documents' => ['Contract and amendments', 'Correspondence and meeting minutes'],
                'warnings' => ['This is not legal advice; consult a qualified lawyer before deciding'],
            ],
        ],
    ],
    [
        'slug' => 'case-study-food-plant-energy', 'type' => 'case_study', 'category' => 'energy', 'problems' => ['energy-high-consumption'],
        'industries' => ['food'], 'minutes' => 4, 'cover' => '/images/knowledge/case-study.svg',
        'fa' => [
            'title' => 'تجربه: کاهش ۱۸٪ مصرف انرژی در یک واحد صنایع غذایی',
            'summary' => 'چگونه یک کارخانه ۶۰ نفره با نشت‌یابی و جابه‌جایی بار، قبض برق را در سه ماه کاهش داد.',
            'body' => "## وضعیت اولیه\nافزایش ۲۵٪ قبض برق در شش ماه.\n\n## اقدامات\nنشت‌یابی هوای فشرده، کاهش فشار کاری از ۸ به ۶٫۵ بار و انتقال شست‌وشوی شبانه به ساعات کم‌باری.\n\n## نتیجه\nکاهش ۱۸٪ مصرف و بازگشت سرمایه کمتر از چهار ماه.",
            'checklist' => [
                'causes' => ['نشتی گسترده هوای فشرده', 'فشار کاری بیش از نیاز'],
                'actions' => ['فشار کاری کمپرسور را مرحله‌به‌مرحله کاهش دهید و اثر آن را بر تولید پایش کنید'],
                'documents' => [],
                'warnings' => [],
            ],
        ],
        'en' => [
            'title' => 'Case study: an 18% energy cut at a food plant',
            'summary' => 'How a 60-person plant reduced its electricity bill in three months with leak repairs and load shifting.',
            'body' => "## Starting point\nElectricity bill up 25% in six months.\n\n## Actions\nCompressed-air leak repairs, set pressure lowered from 8 to 6.5 bar and night cleaning moved off-peak.\n\n## Result\n18% lower consumption and payback under four months.",
            'checklist' => [
                'causes' => ['Widespread compressed-air leaks', 'Set pressure higher than needed'],
                'actions' => ['Lower compressor set pressure step by step and monitor the effect on production'],
                'documents' => [],
                'warnings' => [],
            ],
        ],
    ],
];
