<?php

namespace Database\Seeders;

use App\Domain\Compliance\Models\ServicePath;
use Illuminate\Database\Seeder;

/**
 * Service paths from the programme document. Low-risk collaboration is allowed directly; investment,
 * fund transfer, commercial contracts and sensitive or cross-border data exchange need legal review first.
 */
class ServicePathSeeder extends Seeder
{
    public function run(): void
    {
        $paths = [
            ['education', 'allowed', ['fa' => 'آموزش', 'en' => 'Education'], ['fa' => 'جلسات آموزشی و انتقال دانش عمومی.', 'en' => 'Training sessions and general knowledge transfer.'], []],
            ['mentoring', 'allowed', ['fa' => 'منتورینگ', 'en' => 'Mentoring'], ['fa' => 'همراهی و مشاوره دوره‌ای بدون تعهد مالی.', 'en' => 'Periodic guidance without financial commitments.'], []],
            ['problem_review', 'allowed', ['fa' => 'بررسی مسئله', 'en' => 'Problem review'], ['fa' => 'تحلیل مسئله و پیشنهاد اقدام بعدی.', 'en' => 'Analysing the problem and proposing a next action.'], []],
            ['professional_introduction', 'allowed', ['fa' => 'معرفی حرفه‌ای', 'en' => 'Professional introduction'], ['fa' => 'معرفی به فرد یا سازمان مرتبط بدون انتقال داده محرمانه.', 'en' => 'Introductions to relevant people or organisations without sharing confidential data.'], []],
            ['investment', 'review_required', ['fa' => 'سرمایه‌گذاری', 'en' => 'Investment'], ['fa' => 'هرگونه پیشنهاد یا مذاکره سرمایه‌گذاری.', 'en' => 'Any investment offer or negotiation.'], ['Term sheet', 'KYC of investor']],
            ['fund_transfer', 'review_required', ['fa' => 'انتقال وجه', 'en' => 'Fund transfer'], ['fa' => 'هر نوع پرداخت یا انتقال وجه بین طرفین.', 'en' => 'Any payment or transfer of funds between the parties.'], ['Payment purpose', 'Beneficiary details']],
            ['commercial_contract', 'review_required', ['fa' => 'قرارداد تجاری', 'en' => 'Commercial contract'], ['fa' => 'همکاری با دستمزد یا قرارداد تجاری.', 'en' => 'Paid engagements or commercial contracts.'], ['Draft contract or terms']],
            ['sensitive_data_exchange', 'review_required', ['fa' => 'تبادل داده حساس', 'en' => 'Sensitive data exchange'], ['fa' => 'اشتراک اسناد مالی، قراردادها یا داده‌های شخصی.', 'en' => 'Sharing financial records, contracts or personal data.'], ['List of data to be shared']],
            ['cross_border_data', 'review_required', ['fa' => 'انتقال داده به خارج از کشور', 'en' => 'Cross-border data sharing'], ['fa' => 'دسترسی پشتیبان مقیم خارج به اطلاعات حساس پرونده.', 'en' => 'Access to sensitive case data by a supporter based abroad.'], []],
        ];

        foreach ($paths as $i => [$key, $mode, $name, $description, $docs]) {
            $path = ServicePath::firstOrNew(['key' => $key]);
            $path->fill(['name' => $name, 'description' => $description, 'sort_order' => $i, 'required_documents' => $docs]);
            $path->mode ??= $mode;
            $path->save();
        }
    }
}
