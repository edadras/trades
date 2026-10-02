<?php

namespace Database\Seeders;

use App\Domain\Business\Actions\ManageTeam;
use App\Domain\Business\Models\Business;
use App\Domain\Business\Models\Partner;
use App\Domain\Cases\Actions\AnswerIntakeQuestion;
use App\Domain\Cases\Actions\ApplyHumanReview;
use App\Domain\Cases\Actions\CloseCase;
use App\Domain\Cases\Actions\ConfirmOutcome;
use App\Domain\Cases\Actions\CreateCase;
use App\Domain\Cases\Actions\RecordOutcome;
use App\Domain\Cases\Actions\SaveCaseTask;
use App\Domain\Cases\Actions\ScheduleAppointment;
use App\Domain\Cases\Actions\SubmitCase;
use App\Domain\Cases\Actions\SubmitSatisfaction;
use App\Domain\Cases\Enums\CaseStatus;
use App\Domain\Cases\Models\CaseCategory;
use App\Domain\Cases\Models\SupportCase;
use App\Domain\Compliance\Actions\ProcessDataRequest;
use App\Domain\Compliance\Actions\RecordComplaint;
use App\Domain\Experts\Enums\ExpertVerificationStatus;
use App\Domain\Experts\Models\ExpertProfile;
use App\Domain\Identity\Enums\Role;
use App\Domain\Matching\Actions\DecideMatch;
use App\Domain\Matching\Actions\RespondToInvitation;
use App\Domain\Matching\Enums\MatchStatus;
use App\Domain\Messaging\Actions\SendMessage;
use App\Domain\Pilot\Actions\EvaluateEligibility;
use App\Domain\Pilot\Actions\GeneratePilotReport;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Auth;

/**
 * Demo data produced by running the real workflow (no hand-crafted states): businesses submit problems,
 * the AI engine analyses them, reviewers decide, matching proposes experts, experts accept and collaborate,
 * outcomes and satisfaction are recorded. Every account uses the password "Password123!".
 */
class DemoSeeder extends Seeder
{
    public const PASSWORD = 'Password123!';

    public function run(): void
    {
        config(['queue.default' => 'sync', 'broadcasting.default' => 'log', 'mail.default' => 'log', 'ai.default' => 'local']);
        app()->setLocale('fa');

        $staff = $this->staff();
        $experts = $this->experts();
        $businesses = $this->businesses();
        $reviewer = $staff['reviewer'];

        // 1) Full journey: energy problem → confident AI → matched → expert accepted → active collaboration.
        $energy = $this->submitCase($businesses['aria'], 'هزینه انرژی کارخانه ما طی سه ماه گذشته حدود ۳۰٪ افزایش یافته و نمی‌دانیم مشکل از تجهیزات است یا الگوی مصرف. قبض برق و گاز هر دو بالا رفته و کمپرسورهای هوای فشرده قدیمی هستند.', [
            'از اول تیر ماه شروع شده؛ تقریباً ۳۰ درصد افزایش.',
            'بله، قبض‌های شش ماه اخیر را داریم.',
            'فقط ساعات کاری شیفت شب را کم کردیم که اثری نداشت.',
        ]);
        $this->collaborate($energy, $businesses['aria']->owner, $reviewer);

        // 2) Vague problem → low confidence → waits in the human review queue.
        $this->submitCase($businesses['aria'], 'اوضاع کسب‌وکار این روزها خوب نیست و نمی‌دانیم دقیقاً باید از کجا شروع کنیم.', ['از چند ماه پیش', 'هنوز کاری نکرده‌ایم']);

        // 3) Sensitive legal matter → human review queue regardless of confidence.
        $this->submitCase($businesses['pars'], 'یکی از مشتریان بزرگ قرارداد تأمین را یک‌طرفه فسخ کرده و تهدید به شکایت در دادگاه کرده است. مهلت پاسخ رسمی ما هفته آینده است.', ['هفته آینده مهلت داریم', 'قرارداد و نامه فسخ موجود است']);

        // 4) Completed cases across categories (feed analytics, success stories and expert history).
        $closedScenarios = [
            ['nova', 'نقدینگی شرکت برای پرداخت حقوق پرسنل در دو ماه آینده کافی نیست و مطالبات از مشتریان دیر وصول می‌شود.', 'resolved', 5, 'با پیش‌بینی جریان نقد ۱۳ هفته‌ای و پیگیری مطالبات، کسری نقدینگی برطرف شد.'],
            ['pars', 'میزان ضایعات خط تولید بسته‌بندی در سه ماه اخیر به ۱۲ درصد رسیده و مرجوعی مشتریان زیاد شده است.', 'partially_resolved', 3, 'ضایعات از ۱۲٪ به ۷٪ رسید؛ ادامه کار روی تأمین مواد اولیه.'],
            ['sahel', 'می‌خواهیم صادرات زعفران به امارات را شروع کنیم ولی برای پیدا کردن مشتری خارجی و مجوز صادرات مشکل داریم.', 'effective_action_started', 5, 'سه خریدار بالقوه شناسایی و نمونه ارسال شد.'],
            ['nova', 'Our online sales have dropped by 25% in the last quarter and our social media ads are not converting.', 'resolved', 4, 'Funnel fixed; conversion recovered to previous levels.'],
            ['sahel', 'مصرف برق سردخانه ما در تابستان بسیار بالا رفته و قبض برق دو برابر شده است.', 'resolved', 5, 'با تنظیم دمای سردخانه و تعمیر عایق، مصرف ۲۲٪ کاهش یافت.'],
        ];
        foreach ($closedScenarios as [$biz, $text, $outcome, $rating, $summary]) {
            $case = $this->submitCase($businesses[$biz], $text, ['حدود سه ماه', 'هنوز اقدام خاصی نکرده‌ایم'], $biz === 'nova' && str_starts_with($text, 'Our') ? 'en' : 'fa');
            $this->finish($case, $reviewer, $outcome, $rating, $summary);
        }

        // 5) Outcome recorded by the supporter and waiting for the business to confirm it.
        $pending = $this->submitCase($businesses['sahel'], 'هزینه حمل یخچالی محصولات ما به مشهد و تهران در یک سال اخیر دو برابر شده و نمی‌دانیم چطور مسیرها را بهینه کنیم. مصرف برق سردخانه هم بالاست.', ['حدود یک سال', 'با دو شرکت حمل دیگر تماس گرفتیم']);
        if ($expert = $this->engage($pending, $reviewer)) {
            Auth::login($expert->user);
            app(RecordOutcome::class)->handle($pending->fresh(), $expert->user, ['outcome' => 'effective_action_started', 'reason' => 'برنامه زمان‌بندی جدید حمل و تنظیم دمای سردخانه اجرا شد.', 'result_summary' => 'برنامه کاهش هزینه اجرا شد؛ نتیجه تا پایان ماه سنجیده می‌شود.']);
            Auth::logout();
        }

        // 6) Commercial engagement with a supporter abroad → legal review before confidential data is shared.
        $export = $this->submitCase($businesses['aria'], 'برای ورود محصولات کنسروی به بازار امارات به نماینده فروش و قرارداد توزیع نیاز داریم و شرایط مجوز صادرات را نمی‌دانیم.', ['از ماه گذشته', 'با رایزن بازرگانی صحبت کرده‌ایم'], 'fa', ['engagement_model' => 'commercial', 'engagement_terms' => 'حق‌الزحمه ثابت ماهانه + ۲٪ از فروش اولیه؛ قرارداد سه‌ماهه.'], 'export');
        unset($export);

        // 7) Team member invited to the Aria account.
        $member = User::updateOrCreate(['email' => 'team@hamyar.test'], ['name' => 'نیما همکار', 'password' => self::PASSWORD, 'email_verified_at' => now(), 'locale' => 'fa']);
        if (! $businesses['aria']->hasMember($member)) {
            Auth::login($businesses['aria']->owner);
            $team = app(ManageTeam::class);
            $invitation = $team->invite($businesses['aria'], $businesses['aria']->owner, $member->email, 'member');
            $team->accept($invitation, $member);
            $team->invite($businesses['aria'], $businesses['aria']->owner, 'finance@aria.example', 'admin');
            Auth::logout();
        }

        // 8) Support requests, data requests and the weekly pilot report.
        app(RecordComplaint::class)->handle($businesses['nova']->owner, ['category' => 'technical', 'subject' => 'Notification emails arrive late', 'body' => 'Meeting reminder emails reached us after the meeting had started.']);
        app(ProcessDataRequest::class)->open($businesses['nova']->owner, 'export', 'Annual compliance review');
        app(GeneratePilotReport::class)->handle(now()->toImmutable()->subWeek(), $staff['lead'], false);

        // 9) A draft the business has not submitted yet.
        Auth::login($businesses['aria']->owner);
        app(CreateCase::class)->handle($businesses['aria']->owner, $businesses['aria'], 'می‌خواهیم برای توسعه خط تولید جدید سرمایه‌گذار پیدا کنیم.', null, [], 'fa');
        Auth::logout();
    }

    /** @return array<string, User> */
    private function staff(): array
    {
        $make = fn (string $email, string $name, Role $role) => tap(User::updateOrCreate(['email' => $email], [
            'name' => $name, 'password' => self::PASSWORD, 'email_verified_at' => now(), 'locale' => 'fa',
        ]))->syncRoles([$role->value]);

        return [
            'super' => $make('superadmin@hamyar.test', 'مدیر ارشد سامانه', Role::SuperAdmin),
            'admin' => $make('admin@hamyar.test', 'مدیر سامانه', Role::Admin),
            'reviewer' => $make('reviewer@hamyar.test', 'سارا کارشناس', Role::CaseExpert),
            'content' => $make('content@hamyar.test', 'مدیر محتوا', Role::ContentManager),
            'ops' => $make('ops@hamyar.test', 'مدیر عملیات', Role::OperationsManager),
            'pm' => $make('product@hamyar.test', 'مدیر محصول', Role::ProductManager),
            'legal' => $make('legal@hamyar.test', 'کارشناس حقوقی', Role::LegalCompliance),
            'lead' => $make('lead@hamyar.test', 'مدیر برنامه پایلوت', Role::ProgramLead),
            'network' => $make('network@hamyar.test', 'مدیر شبکه پشتیبانان', Role::NetworkManager),
        ];
    }

    /** @return array<string, ExpertProfile> */
    private function experts(): array
    {
        $defs = [
            'expert' => ['expert@hamyar.test', 'مهندس رضا انرژی‌پور', 'مشاور بهینه‌سازی انرژی صنایع تولیدی', ['energy', 'energy-high-consumption', 'energy-outage'], ['manufacturing', 'food'], 'IR', ['fa', 'en'], 18],
            'finance' => ['finance.expert@hamyar.test', 'مریم حسابی', 'مشاور مالی و مدیریت نقدینگی SMEها', ['finance', 'finance-cash-flow', 'finance-financing'], ['food', 'retail', 'manufacturing'], 'IR', ['fa'], 14],
            'export' => ['export.expert@hamyar.test', 'Ali Tajer', 'Export development adviser (GCC & EU)', ['export', 'export-market-entry', 'export-customs'], ['food', 'handicrafts', 'agriculture'], 'AE', ['fa', 'en'], 12],
            'production' => ['production.expert@hamyar.test', 'حمید تولیدی', 'متخصص کیفیت و بهره‌وری خطوط تولید', ['production', 'production-quality', 'production-maintenance'], ['manufacturing', 'food', 'automotive'], 'IR', ['fa'], 20],
            'marketing' => ['marketing.expert@hamyar.test', 'Neda Rahimi', 'Growth & digital marketing consultant', ['marketing', 'marketing-sales-drop', 'marketing-digital'], ['retail', 'it_software', 'food'], 'DE', ['fa', 'en'], 9],
            'legal' => ['legal.expert@hamyar.test', 'دکتر کیان حقوقی', 'وکیل پایه‌یک و مشاور قراردادهای تجاری', ['legal', 'legal-contracts', 'legal-licensing'], ['manufacturing', 'wholesale'], 'IR', ['fa'], 16],
            'tech' => ['tech.expert@hamyar.test', 'Sina Moradi', 'IT & cybersecurity adviser for SMEs', ['technology', 'technology-security', 'technology-digitalization'], ['it_software', 'retail'], 'TR', ['fa', 'en'], 11],
            'hr' => ['hr.expert@hamyar.test', 'لیلا منابع', 'مشاور منابع انسانی و توسعه سازمانی', ['hr', 'hr-hiring', 'hr-retention'], ['manufacturing', 'healthcare'], 'IR', ['fa'], 13],
            'invest' => ['invest.expert@hamyar.test', 'Arash Sarmaye', 'Investment readiness & feasibility studies', ['investment', 'investment-fundraising', 'investment-feasibility', 'finance-financing'], ['manufacturing', 'energy'], 'GB', ['fa', 'en'], 15],
            'energy2' => ['energy2.expert@hamyar.test', 'نرگس خورشیدی', 'کارشناس انرژی‌های تجدیدپذیر', ['energy', 'energy-renewables', 'energy-high-consumption'], ['agriculture', 'food'], 'IR', ['fa'], 7],
        ];

        $profiles = [];
        foreach ($defs as $key => [$email, $name, $headline, $skills, $industries, $country, $langs, $years]) {
            $user = User::updateOrCreate(['email' => $email], ['name' => $name, 'password' => self::PASSWORD, 'email_verified_at' => now(), 'locale' => $langs[0] === 'fa' && $country === 'IR' ? 'fa' : 'en']);
            $user->syncRoles([Role::Supporter->value]);
            $profile = ExpertProfile::updateOrCreate(['user_id' => $user->id], [
                'headline' => $headline,
                'bio' => $headline.'. '.($country === 'IR' ? 'سابقه همکاری با ده‌ها کسب‌وکار کوچک و متوسط.' : 'Worked with dozens of SMEs in Iran and abroad.'),
                'country' => $country, 'timezone' => $country === 'IR' ? 'Asia/Tehran' : 'Europe/Berlin', 'years_experience' => $years,
                'industries' => $industries, 'serves_countries' => ['IR'], 'collaboration_types' => ['consultation', 'problem_review', 'mentoring', 'introduction'],
                'supporter_type' => $key === 'invest' ? 'organization' : 'individual', 'organization_name' => $key === 'invest' ? 'Sarmaye Advisory Partners' : null,
                'support_models' => match (true) {
                    $key === 'invest', $key === 'export' => ['voluntary', 'free', 'commercial'], $country === 'IR' => ['voluntary', 'free', 'subsidized'], default => ['voluntary', 'free']
                },
                'certifications' => [['title' => 'Certified consultant', 'issuer' => 'Chamber of Commerce', 'year' => 2020]],
                'max_active_cases' => 6, 'is_available' => true, 'verification_status' => ExpertVerificationStatus::Verified,
                'verified_at' => now(), 'nda_accepted_at' => now(), 'nda_version' => '2026-10', 'avg_response_minutes' => random_int(60, 600),
            ]);
            $profile->skills()->delete();
            foreach (CaseCategory::whereIn('slug', $skills)->get() as $category) {
                $profile->skills()->create(['case_category_id' => $category->id, 'level' => $category->parent_id ? 5 : 4, 'years' => $years]);
            }
            $profile->languages()->delete();
            foreach ($langs as $lang) {
                $profile->languages()->create(['language' => $lang, 'proficiency' => $lang === 'fa' ? 'native' : 'fluent']);
            }
            $profile->availability()->delete();
            foreach ([0, 1, 2, 3] as $day) {
                $profile->availability()->create(['weekday' => $day, 'starts_at' => '09:00', 'ends_at' => '13:00']);
            }
            $profiles[$key] = $profile;
        }

        // An applicant waiting for verification.
        $applicant = User::updateOrCreate(['email' => 'applicant@hamyar.test'], ['name' => 'متقاضی پشتیبان', 'password' => self::PASSWORD, 'email_verified_at' => now()]);
        $p = ExpertProfile::updateOrCreate(['user_id' => $applicant->id], [
            'headline' => 'مشاور صادرات فرش و صنایع‌دستی', 'bio' => 'ده سال سابقه صادرات به اروپا.', 'country' => 'IR', 'timezone' => 'Asia/Tehran',
            'years_experience' => 10, 'industries' => ['handicrafts'], 'collaboration_types' => ['consultation'], 'max_active_cases' => 3,
            'supporter_type' => 'individual', 'support_models' => ['voluntary'],
            'verification_status' => ExpertVerificationStatus::Submitted, 'nda_accepted_at' => now(), 'nda_version' => '2026-10',
        ]);
        if (! $p->skills()->exists()) {
            $p->skills()->create(['case_category_id' => CaseCategory::where('slug', 'export')->value('id'), 'level' => 4, 'years' => 10]);
            $p->languages()->create(['language' => 'fa', 'proficiency' => 'native']);
            $p->verifications()->create(['status' => 'submitted']);
        }

        return $profiles;
    }

    /** @return array<string, Business> */
    private function businesses(): array
    {
        $defs = [
            'aria' => ['business@hamyar.test', 'علی آریایی', 'صنایع غذایی آریا', 'food', 'medium', '50-249', 'tehran', 'تولید کنسرو و غذاهای آماده'],
            'pars' => ['pars@hamyar.test', 'زهرا پارسا', 'بسته‌بندی پارس', 'manufacturing', 'small', '10-49', 'isfahan', 'تولید بسته‌بندی مقوایی و پلاستیکی'],
            'nova' => ['nova@hamyar.test', 'Kaveh Novin', 'Nova Digital Retail', 'retail', 'small', '10-49', 'tehran', 'Online retailer of home appliances'],
            'sahel' => ['sahel@hamyar.test', 'محمد ساحلی', 'زعفران و خشکبار ساحل', 'agriculture', 'small', '10-49', 'khorasan_razavi', 'فرآوری و بسته‌بندی زعفران و خشکبار'],
            'tara' => ['tara@hamyar.test', 'تارا صنعتی', 'نساجی تارا', 'textile', 'medium', '50-249', 'east_azerbaijan', 'تولید پارچه و منسوجات خانگی'],
        ];

        $out = [];
        foreach ($defs as $key => [$email, $name, $trade, $industry, $size, $employees, $province, $desc]) {
            $user = User::updateOrCreate(['email' => $email], ['name' => $name, 'password' => self::PASSWORD, 'email_verified_at' => now(), 'locale' => $key === 'nova' ? 'en' : 'fa']);
            $user->syncRoles([Role::Business->value]);
            $business = Business::updateOrCreate(['owner_id' => $user->id], [
                'trade_name' => $trade, 'legal_name' => 'شرکت '.$trade.' (سهامی خاص)', 'registration_number' => (string) random_int(1000000000, 9999999999),
                'country' => 'IR', 'province' => $province, 'city' => null, 'industry' => $industry, 'size' => $size, 'employees_range' => $employees,
                'founded_year' => random_int(1995, 2018), 'description' => $desc, 'products_services' => $desc, 'preferred_language' => $key === 'nova' ? 'en' : 'fa',
                'contact_name' => $name, 'contact_email' => $email, 'contact_phone' => '0912'.random_int(1000000, 9999999),
                'main_needs' => ['finance', 'energy'], 'onboarding_step' => Business::ONBOARDING_STEPS, 'onboarding_completed_at' => now()->subDays(random_int(10, 60)),
            ]);
            $business->members()->syncWithoutDetaching([$user->id => ['role' => 'owner']]);
            $business->update(['partner_id' => Partner::where('referral_code', $key === 'aria' || $key === 'sahel' ? 'FOODASSN' : 'CHAMBER1')->value('id')]);
            app(EvaluateEligibility::class)->handle($business);
            $out[$key] = $business->fresh('owner');
        }
        // Programme lead admits an out-of-scope business as an exception (recorded with a reason).
        $out['nova']->update(['eligibility_status' => 'eligible', 'eligibility_reason' => 'override: retail partner of an agri-food value chain']);

        // A newly registered business still in the onboarding wizard.
        $newUser = User::updateOrCreate(['email' => 'newbusiness@hamyar.test'], ['name' => 'کسب‌وکار تازه', 'password' => self::PASSWORD, 'email_verified_at' => now()]);
        $newUser->syncRoles([Role::Business->value]);
        $new = Business::updateOrCreate(['owner_id' => $newUser->id], ['trade_name' => 'کارگاه نوپا', 'onboarding_step' => 3]);
        $new->members()->syncWithoutDetaching([$newUser->id => ['role' => 'owner']]);

        return $out;
    }

    /** @param array<string, string> $engagement when set, the case is matched and accepted with these terms */
    private function submitCase(Business $business, string $text, array $answers, string $locale = 'fa', array $engagement = [], ?string $expertKey = null): SupportCase
    {
        $owner = $business->owner;
        Auth::login($owner);
        app()->setLocale($locale);
        $consent = ['ai_processing' => true, 'share_with_foreign_experts' => true, 'anonymized_learning' => $business->trade_name !== 'بسته‌بندی پارس'];
        $case = app(CreateCase::class)->handle($owner, $business, $text, null, [], $locale, $consent);
        $intake = app(AnswerIntakeQuestion::class);
        $question = $intake->next($case, $owner);
        foreach ($answers as $answer) {
            if (! $question) {
                break;
            }
            $question = $intake->answer($case, $owner, $question['key'], $answer);
        }
        app(SubmitCase::class)->handle($case->fresh());
        Auth::logout();
        app()->setLocale('fa');

        if ($engagement) {
            $this->engage($case->fresh(), User::where('email', 'reviewer@hamyar.test')->first(), $engagement, $expertKey);
        }

        return $case->fresh();
    }

    /** Reviewer confirms (if needed), business accepts the best match, expert joins. */
    /** @param array<string, string> $engagement */
    private function engage(SupportCase $case, User $reviewer, array $engagement = [], ?string $expertEmailPrefix = null): ?ExpertProfile
    {
        if ($case->status === CaseStatus::HumanReview) {
            Auth::login($reviewer);
            app(ApplyHumanReview::class)->handle($case, $reviewer, ['decision' => 'confirmed', 'run_matching' => true]);
            $case->refresh();
        }
        $match = $case->matches()->where('status', MatchStatus::Proposed->value)
            ->when($expertEmailPrefix, fn ($q) => $q->whereHas('expertProfile.user', fn ($u) => $u->where('email', 'like', $expertEmailPrefix.'.%')))
            ->orderByDesc('score')->first()
            ?? $case->matches()->where('status', MatchStatus::Proposed->value)->orderByDesc('score')->first();
        if (! $match) {
            return null;
        }
        Auth::login($case->business->owner);
        app(DecideMatch::class)->handle($match, $case->business->owner, true);
        $expertUser = $match->expertProfile->user;
        Auth::login($expertUser);
        app(RespondToInvitation::class)->handle($match->fresh(), $expertUser, true, null, $engagement ?: ['engagement_model' => 'voluntary', 'engagement_terms' => null]);
        Auth::logout();

        return $match->expertProfile;
    }

    private function collaborate(SupportCase $case, User $owner, User $reviewer): void
    {
        $expert = $this->engage($case, $reviewer);
        if (! $expert) {
            return;
        }
        $case->refresh();
        $expertUser = $expert->user;
        $send = app(SendMessage::class);
        $conversation = $case->conversation;

        Auth::login($expertUser);
        $send->handle($conversation, $expertUser, 'سلام، پرونده را بررسی کردم. برای شروع، قبض‌های برق و گاز ۱۲ ماه اخیر و فهرست کمپرسورها را لطفاً ارسال کنید.');
        app(SaveCaseTask::class)->create($case, $expertUser, ['title' => 'ارسال گزارش مصرف برق سه‌ماهه', 'owner_role' => 'business', 'is_next_action' => true, 'due_at' => now()->addDays(5)]);
        app(SaveCaseTask::class)->create($case, $expertUser, ['title' => 'بازدید نشت‌یابی از سیستم هوای فشرده', 'owner_role' => 'expert', 'due_at' => now()->addDays(10)]);
        app(ScheduleAppointment::class)->handle($case, $expertUser, ['title' => 'جلسه آنلاین بررسی قبوض', 'starts_at' => now()->addDays(2)->setTime(10, 0), 'ends_at' => now()->addDays(2)->setTime(11, 0), 'meeting_url' => 'https://meet.example.com/hamyar-energy']);

        Auth::login($owner);
        $send->handle($conversation, $owner, 'ممنون. قبض‌ها را تا آخر هفته آپلود می‌کنم. کمپرسورها حدود ۱۲ سال کار کرده‌اند.');
        Auth::logout();
    }

    private function finish(SupportCase $case, User $reviewer, string $outcome, int $rating, string $summary): void
    {
        $expert = $this->engage($case->fresh(), $reviewer);
        $case->refresh();
        $actor = $expert?->user ?? $reviewer;
        Auth::login($actor);
        $recorded = app(RecordOutcome::class)->handle($case, $actor, ['outcome' => $outcome, 'reason' => $summary, 'result_summary' => $summary]);
        Auth::login($case->business->owner);
        if ($recorded->confirmation_status === 'pending') {
            app(ConfirmOutcome::class)->confirm($recorded, $case->business->owner);
        }
        app(CloseCase::class)->handle($case->fresh(), $case->business->owner);
        app(SubmitSatisfaction::class)->handle($case->fresh(), $case->business->owner, [
            'rating' => $rating, 'comment' => $summary, 'problem_solved' => $outcome !== 'effective_action_started', 'would_recommend_expert' => $rating > 3,
            'dissatisfaction_reason' => $rating <= 3 ? 'problem_not_solved' : null,
        ]);
        Auth::logout();

        // Spread history over the past weeks so charts have a timeline.
        $days = random_int(7, 70);
        $case->forceFill([
            'submitted_at' => now()->subDays($days), 'created_at' => now()->subDays($days),
            'ready_at' => now()->subDays($days)->addHours(random_int(2, 30)),
            'resolved_at' => now()->subDays(max(1, $days - random_int(5, 20))), 'closed_at' => now()->subDays(max(1, $days - 21)),
        ])->saveQuietly();
    }
}
