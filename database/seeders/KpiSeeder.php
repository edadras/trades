<?php

namespace Database\Seeders;

use App\Domain\Analytics\Models\Kpi;
use Illuminate\Database\Seeder;

/** Pilot KPIs from the programme document. Targets are data and can be changed by admins. */
class KpiSeeder extends Seeder
{
    public function run(): void
    {
        $kpis = [
            ['active_businesses', 'active_businesses', 'count', '>=', 30, ['fa' => 'کسب‌وکارهای فعال', 'en' => 'Active businesses'], ['fa' => 'کسب‌وکارهایی که ثبت‌نام را کامل کرده‌اند', 'en' => 'Businesses that completed registration']],
            ['verified_supporters', 'verified_supporters', 'count', '>=', 15, ['fa' => 'پشتیبانان تأییدشده', 'en' => 'Verified supporters'], ['fa' => 'متخصصان با وضعیت تأییدشده', 'en' => 'Experts with verified status']],
            ['real_cases', 'real_cases', 'count', '>=', 50, ['fa' => 'پرونده‌های واقعی', 'en' => 'Real cases'], ['fa' => 'پرونده‌های ثبت‌شده (غیر پیش‌نویس)', 'en' => 'Submitted (non-draft) cases']],
            ['initial_review', 'initial_review_hours', 'hours', '<=', 48, ['fa' => 'زمان بررسی اولیه', 'en' => 'Initial review time'], ['fa' => 'میانگین ساعت از ثبت تا اولین تصمیم', 'en' => 'Average hours from submission to first decision']],
            ['ai_agreement', 'ai_agreement_rate', 'percent', '>=', 80, ['fa' => 'توافق هوش مصنوعی و کارشناس', 'en' => 'AI–expert agreement'], ['fa' => 'درصد بررسی‌هایی که دسته پیشنهادی AI تأیید شد', 'en' => 'Share of reviews where the AI category was confirmed']],
            ['clear_next_action', 'clear_next_action_rate', 'percent', '>=', 70, ['fa' => 'اقدام بعدی روشن', 'en' => 'Clear next action'], ['fa' => 'پرونده‌های تحلیل‌شده با اقدام بعدی مشخص', 'en' => 'Analysed cases with a defined next action']],
            ['match_acceptance', 'match_acceptance_rate', 'percent', '>=', 50, ['fa' => 'پذیرش پیشنهاد پشتیبان', 'en' => 'Match acceptance'], ['fa' => 'درصد پیشنهادهای پذیرفته‌شده توسط کسب‌وکار', 'en' => 'Share of expert proposals accepted by businesses']],
            ['satisfaction', 'satisfaction_avg', 'score', '>=', 4, ['fa' => 'رضایت کاربران', 'en' => 'User satisfaction'], ['fa' => 'میانگین امتیاز ۱ تا ۵', 'en' => 'Average rating, 1–5']],
            ['effective_action', 'effective_action_rate', 'percent', '>=', 60, ['fa' => 'اقدام مؤثر آغازشده', 'en' => 'Effective action started'], ['fa' => 'نتایج حل‌شده، نسبی یا اقدام مؤثر', 'en' => 'Resolved, partially resolved or effective action outcomes']],
            ['resolution', 'resolution_rate', 'percent', '>=', 40, ['fa' => 'نرخ حل کامل یا نسبی', 'en' => 'Resolution rate'], ['fa' => 'پرونده‌های حل‌شده کامل یا نسبی', 'en' => 'Fully or partially resolved cases']],
        ];

        foreach ($kpis as $i => [$key, $metric, $unit, $cmp, $target, $name, $description]) {
            $kpi = Kpi::updateOrCreate(['key' => $key], [
                'name' => $name, 'description' => $description, 'metric' => $metric, 'unit' => $unit,
                'comparator' => $cmp, 'is_active' => true, 'sort_order' => $i,
            ]);
            if (! $kpi->targets()->exists()) {
                $kpi->targets()->create(['target_value' => $target, 'period_start' => now()->startOfMonth()->toDateString(), 'period_end' => now()->startOfMonth()->addWeeks(24)->toDateString()]);
            }
        }
    }
}
