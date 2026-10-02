<?php

namespace Database\Seeders;

use App\Domain\Cases\Models\CaseCategory;
use App\Domain\Pilot\Models\PilotProgram;
use Illuminate\Database\Seeder;

/** The 24-week pilot: scope, eligibility, priority problems, first-month decisions and decision gates. */
class PilotProgramSeeder extends Seeder
{
    public function run(): void
    {
        if (PilotProgram::exists()) {
            return;
        }

        $program = PilotProgram::create([
            'name' => ['fa' => 'پایلوت ۲۴ هفته‌ای همیار', 'en' => 'Hamyar 24-week pilot'],
            'status' => 'active',
            'starts_on' => now()->subWeeks(3)->startOfWeek()->toDateString(),
            'ends_on' => now()->subWeeks(3)->startOfWeek()->addWeeks(24)->toDateString(),
            'regions' => ['tehran', 'isfahan', 'khorasan_razavi'],
            'value_chains' => ['agri_food', 'industrial'],
            'industries' => [],
            'business_sizes' => ['micro', 'small', 'medium'],
            'max_groups' => 2,
            'max_businesses' => 40,
            'priority_category_ids' => CaseCategory::whereIn('slug', ['energy', 'finance', 'export'])->pluck('id')->all(),
            'eligibility_notes' => [
                'fa' => 'کسب‌وکارهای خرد، کوچک و متوسط فعال در زنجیره‌های کشاورزی-غذایی و صنعتی در سه استان پایلوت.',
                'en' => 'Micro, small and medium businesses in the agri-food and industrial value chains in the three pilot provinces.',
            ],
            'success_definition' => [
                'fa' => '۳۰ کسب‌وکار فعال، ۱۵ پشتیبان تأییدشده، ۵۰ پرونده واقعی، بررسی اولیه ۸۰٪ پرونده‌ها زیر ۴۸ ساعت و اقدام بعدی روشن برای ۷۰٪ پرونده‌های پذیرفته‌شده.',
                'en' => '30 active businesses, 15 verified supporters, 50 real cases, 80% of cases reviewed within 48 hours and a clear next action for 70% of accepted cases.',
            ],
            'support_model_policy' => [
                'fa' => 'در پایلوت حمایت داوطلبانه یا رایگان است؛ مدل یارانه‌ای با تأیید مدیر برنامه و مدل تجاری فقط پس از بررسی حقوقی.',
                'en' => 'Support is voluntary or free during the pilot; subsidised support needs programme lead approval and commercial support needs legal review.',
            ],
            'partner_coordination' => [
                'fa' => 'معرفی کسب‌وکارها از طریق اتاق بازرگانی و انجمن‌های صنفی با کد ارجاع؛ جلسه هماهنگی دوهفته‌ای.',
                'en' => 'Businesses are referred by the chamber of commerce and trade associations with referral codes; coordination meeting every two weeks.',
            ],
        ]);
        $program->ensureGates();
    }
}
