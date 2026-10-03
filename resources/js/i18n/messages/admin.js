const metricsFa = { active_businesses: 'کسب‌وکارهای فعال', verified_supporters: 'پشتیبانان تأییدشده', real_cases: 'پرونده‌های واقعی', initial_review_hours: 'میانگین ساعت بررسی اولیه', ai_agreement_rate: 'نرخ توافق AI و کارشناس', clear_next_action_rate: 'نرخ اقدام بعدی روشن', match_acceptance_rate: 'نرخ پذیرش پیشنهاد متخصص', satisfaction_avg: 'میانگین رضایت', resolution_rate: 'نرخ حل', effective_action_rate: 'نرخ اقدام مؤثر', expert_response_hours: 'میانگین زمان پاسخ متخصص', verified_supporters_domestic: 'پشتیبانان تأییدشده داخل کشور', verified_supporters_abroad: 'پشتیبانان تأییدشده خارج از کشور', initial_review_sla_rate: 'نرخ بررسی اولیه در مهلت مقرر', dissatisfaction_rate: 'نرخ نارضایتی', outcome_confirmation_rate: 'نرخ تأیید نتیجه توسط کسب‌وکار' };
const metricsEn = { active_businesses: 'Active businesses', verified_supporters: 'Verified supporters', real_cases: 'Real cases', initial_review_hours: 'Avg. hours to initial review', ai_agreement_rate: 'AI–expert agreement rate', clear_next_action_rate: 'Clear next-action rate', match_acceptance_rate: 'Match acceptance rate', satisfaction_avg: 'Average satisfaction', resolution_rate: 'Resolution rate', effective_action_rate: 'Effective action rate', expert_response_hours: 'Avg. expert response hours', verified_supporters_domestic: 'Verified supporters in Iran', verified_supporters_abroad: 'Verified supporters abroad', initial_review_sla_rate: 'Initial reviews within SLA', dissatisfaction_rate: 'Dissatisfaction rate', outcome_confirmation_rate: 'Outcome confirmation rate' };

export default {
    fa: {
        admin: {
            businesses: 'کسب‌وکارها', cases: 'کل پرونده‌ها', open_cases: 'پرونده‌های باز', experts: 'متخصصان تأییدشده', pending_experts: ':n در انتظار تأیید', review_queue: 'صف بررسی',
            ai_accuracy: 'دقت AI', avg_response: 'زمان بررسی اولیه', match_acceptance: 'پذیرش پیشنهاد', resolution_rate: 'نرخ حل', satisfaction: 'رضایت',
            kpi_title: 'شاخص‌های کلیدی پایلوت', manage_targets: 'مدیریت اهداف',
            chart_over_time: 'روند پرونده‌ها', chart_weeks: '۱۲ هفته اخیر', chart_funnel: 'قیف پرونده‌ها', chart_category: 'پرونده‌ها بر اساس دسته', chart_region: 'پرونده‌ها بر اساس منطقه', chart_industry: 'پرونده‌ها بر اساس صنعت',
            chart_ai_human: 'هوش مصنوعی در برابر کارشناس', chart_ai_human_hint: 'نتیجه بررسی‌های انسانی', ai_agreed: 'تأیید پیشنهاد AI', ai_category_changed: 'اصلاح دسته', ai_urgency_changed: 'اصلاح فوریت',
            submitted: 'ثبت‌شده', resolved: 'حل‌شده', expert_performance: 'عملکرد متخصصان', onboarded: 'فعال', onboarding: 'در حال ثبت‌نام', view_audited: 'مشاهده این صفحه در گزارش ممیزی ثبت می‌شود.',
            active_cases_n: ':n پرونده فعال', verification: 'تأیید صلاحیت', verification_notes: 'یادداشت برای متخصص', verify: 'تأیید', reject: 'نیاز به اصلاح', mark_in_review: 'در حال بررسی', suspend: 'تعلیق',
        },
        kpi: {
            subtitle: 'شاخص‌ها داده هستند، نه کد: هدف، بازه و معیار هر KPI قابل تنظیم است.', achieved: 'محقق شد', in_progress: 'در مسیر', no_data: 'بدون داده', target: 'هدف', hours: 'ساعت',
            add: 'KPI جدید', edit: 'ویرایش KPI', snapshot: 'ثبت وضعیت امروز', definitions: 'تعریف شاخص‌ها', inactive: 'غیرفعال', active: 'فعال', name_fa: 'نام (فارسی)', name_en: 'نام (انگلیسی)',
            key: 'کلید', metric: 'معیار', unit: 'واحد', comparator: 'مقایسه', order: 'ترتیب', period_start: 'شروع بازه', period_end: 'پایان بازه', description_fa: 'توضیح (فارسی)', description_en: 'توضیح (انگلیسی)',
        },
        metrics: metricsFa,
        categories: {
            subtitle: 'دسته‌بندی مسائل و کلیدواژه‌هایی که موتور تشخیص از آن‌ها استفاده می‌کند.', add: 'دسته جدید', add_sub: 'زیردسته', parent: 'دسته والد', root: '— دسته اصلی —', default_urgency: 'فوریت پیش‌فرض',
            icon: 'آیکن', keywords_fa: 'کلیدواژه‌ها (فارسی)', keywords_en: 'کلیدواژه‌ها (انگلیسی)', keywords_hint: 'با کاما جدا کنید.', sensitive: 'موضوع حساس', sensitive_hint: 'پرونده‌های این دسته همیشه بررسی انسانی می‌شوند.',
        },
        knowledge_admin: {
            subtitle: 'فقط محتوای «تأییدشده» توسط هوش مصنوعی و در سایت عمومی استفاده می‌شود.', new: 'محتوای جدید', expired: 'منقضی', preview: 'پیش‌نمایش', summary: 'خلاصه', body: 'متن (Markdown)',
            markdown_hint: 'از ## برای تیتر و - برای فهرست استفاده کنید.', structured: 'داده‌های ساختاریافته برای راهنمای هوشمند', structured_hint: 'هر خط یک مورد. موتور راهنما فقط از این موارد استفاده می‌کند.', one_per_line: 'هر خط یک مورد',
            seo: 'سئو', seo_title: 'عنوان سئو', seo_description: 'توضیح سئو', verification: 'وضعیت تأیید', ai_rule: 'هر ویرایش در محتوای تأییدشده، آن را به وضعیت «در حال بررسی» برمی‌گرداند تا AI از متن بررسی‌نشده استفاده نکند.',
            send_review: 'ارسال برای بررسی', send_back: 'بازگرداندن برای اصلاح', approve: 'تأیید و انتشار', archive: 'بایگانی', meta: 'مشخصات', minutes: 'زمان مطالعه (دقیقه)', valid_until: 'اعتبار تا', cover: 'تصویر کاور', video: 'لینک ویدیو', tags: 'برچسب‌ها', featured: 'محتوای ویژه',
            problem_types: 'مسائل مرتبط', problem_types_hint: 'هر محتوا به یک یا چند مسئله مشخص متصل می‌شود.',
        },
        content_status: { draft: 'پیش‌نویس', in_review: 'در حال بررسی', approved: 'تأییدشده', archived: 'بایگانی' },
        audit: { subtitle: 'ثبت مشاهده‌ها، دریافت مدارک، ورودها و تغییرات حساس.', filter: 'فیلتر رویداد (مثلاً file. یا auth.)', system: 'سامانه' },
        users: { roles: 'نقش‌ها' },
    },
    en: {
        admin: {
            businesses: 'Businesses', cases: 'Total cases', open_cases: 'Open cases', experts: 'Verified experts', pending_experts: ':n awaiting verification', review_queue: 'Review queue',
            ai_accuracy: 'AI accuracy', avg_response: 'Time to initial review', match_acceptance: 'Match acceptance', resolution_rate: 'Resolution rate', satisfaction: 'Satisfaction',
            kpi_title: 'Pilot KPIs', manage_targets: 'Manage targets',
            chart_over_time: 'Cases over time', chart_weeks: 'Last 12 weeks', chart_funnel: 'Case funnel', chart_category: 'Cases by category', chart_region: 'Cases by region', chart_industry: 'Cases by industry',
            chart_ai_human: 'AI vs human', chart_ai_human_hint: 'Outcome of human reviews', ai_agreed: 'AI confirmed', ai_category_changed: 'Category corrected', ai_urgency_changed: 'Urgency corrected',
            submitted: 'Submitted', resolved: 'Resolved', expert_performance: 'Expert performance', onboarded: 'Active', onboarding: 'Onboarding', view_audited: 'Viewing this page is recorded in the audit log.',
            active_cases_n: ':n active cases', verification: 'Verification', verification_notes: 'Note to the expert', verify: 'Verify', reject: 'Needs changes', mark_in_review: 'In review', suspend: 'Suspend',
        },
        kpi: {
            subtitle: 'KPIs are data, not code: each KPI’s target, period and metric are configurable.', achieved: 'Achieved', in_progress: 'On track', no_data: 'No data', target: 'Target', hours: 'hours',
            add: 'New KPI', edit: 'Edit KPI', snapshot: 'Snapshot today', definitions: 'KPI definitions', inactive: 'Inactive', active: 'Active', name_fa: 'Name (Persian)', name_en: 'Name (English)',
            key: 'Key', metric: 'Metric', unit: 'Unit', comparator: 'Comparator', order: 'Order', period_start: 'Period start', period_end: 'Period end', description_fa: 'Description (Persian)', description_en: 'Description (English)',
        },
        metrics: metricsEn,
        categories: {
            subtitle: 'Problem taxonomy and the keywords the classifier uses.', add: 'New category', add_sub: 'Subcategory', parent: 'Parent', root: '— Top level —', default_urgency: 'Default urgency',
            icon: 'Icon', keywords_fa: 'Keywords (Persian)', keywords_en: 'Keywords (English)', keywords_hint: 'Separate with commas.', sensitive: 'Sensitive topic', sensitive_hint: 'Cases in this category always get human review.',
        },
        knowledge_admin: {
            subtitle: 'Only “approved” content is used by the AI and on the public site.', new: 'New content', expired: 'Expired', preview: 'Preview', summary: 'Summary', body: 'Body (Markdown)',
            markdown_hint: 'Use ## for headings and - for lists.', structured: 'Structured data for AI guidance', structured_hint: 'One item per line. The guidance engine only uses these items.', one_per_line: 'One per line',
            seo: 'SEO', seo_title: 'SEO title', seo_description: 'SEO description', verification: 'Verification', ai_rule: 'Editing approved content sends it back to “in review”, so the AI never uses unreviewed text.',
            send_review: 'Send for review', send_back: 'Send back for changes', approve: 'Approve & publish', archive: 'Archive', meta: 'Details', minutes: 'Reading time (min)', valid_until: 'Valid until', cover: 'Cover image', video: 'Video link', tags: 'Tags', featured: 'Featured',
            problem_types: 'Linked problems', problem_types_hint: 'Each item is linked to one or more specific problems.',
        },
        content_status: { draft: 'Draft', in_review: 'In review', approved: 'Approved', archived: 'Archived' },
        audit: { subtitle: 'Records of views, document downloads, sign-ins and sensitive changes.', filter: 'Filter by event (e.g. file. or auth.)', system: 'System' },
        users: { roles: 'Roles' },
    },
};
