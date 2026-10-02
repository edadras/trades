# همیار — Hamyar

پلتفرم هوشمند حمایت از کسب‌وکارها · Smart business support platform

کسب‌وکار مسئله‌اش را با متن یا صدا ثبت می‌کند، دستیار هوشمند اطلاعات ناقص را تکمیل و دسته/فوریت را تشخیص می‌دهد، راهنمای اولیه فقط از بانک دانش تأییدشده ساخته می‌شود، موارد حساس یا مبهم به بررسی انسانی می‌روند، متخصص مناسب با دلیل تطبیق پیشنهاد می‌شود و همکاری در فضای کاری امن تا ثبت نتیجه پیگیری می‌شود.

A business submits a problem (text or voice); the AI interview completes missing information and suggests category and urgency; initial guidance is composed **only** from approved knowledge; uncertain or sensitive cases go to a human review queue; matched experts are proposed with the reason for the match; collaboration happens in a secure workspace and every case closes with a recorded outcome.

## Stack

| Layer | Choice |
|---|---|
| Backend | Laravel 13, PHP 8.3+ (8.4 recommended), MySQL 8 (SQLite for dev/tests) |
| Frontend | Vue 3 + Inertia.js v3 (with SSR) + Tailwind CSS v4 |
| Auth | Laravel Fortify (password, email verification, 2FA) + email OTP sign-in, Sanctum for the API |
| RBAC | spatie/laravel-permission — 9 roles, 17 permissions, multiple roles per user |
| Queue | Redis + Horizon (`ai` queue for analysis/transcription) |
| Realtime | Laravel Reverb (case chat), automatic polling fallback |
| Search | Laravel Scout (database driver locally, Meilisearch in production) |
| Storage | Private local disk or S3-compatible (`s3_private`, server-side encryption) |
| AI | Provider-agnostic layer (`AIProviderInterface`), OpenAI-compatible provider + offline deterministic engine |

## Quick start

```bash
composer install
npm install
cp .env.example .env        # or keep the generated local .env (SQLite, sync queue, local AI engine)
php artisan key:generate
php artisan migrate --seed  # reference data + a full demo produced by running the real workflow
npm run build               # client + SSR bundles
php artisan serve           # http://localhost:8000 → /fa or /en
php artisan inertia:start-ssr   # optional: server-side rendering for public pages
```

Backing services for staging/production-like runs: `docker compose up -d` (MySQL, Redis, Meilisearch, ClamAV, Mailpit).

### Demo accounts (password `Password123!`)

| Email | Role |
|---|---|
| `business@hamyar.test` | Business with an active energy case (matched, expert working, chat, tasks, meeting), a case in review and a draft |
| `nova@hamyar.test`, `pars@hamyar.test`, `sahel@hamyar.test` | Businesses with closed cases, outcomes and surveys |
| `newbusiness@hamyar.test` | Business still in the onboarding wizard |
| `expert@hamyar.test` (+ 9 more `*.expert@hamyar.test`) | Verified supporters |
| `applicant@hamyar.test` | Expert application waiting for verification |
| `reviewer@hamyar.test` | Case expert (review queue) |
| `content@hamyar.test` · `ops@hamyar.test` · `product@hamyar.test` · `legal@hamyar.test` | Content / operations / product / legal-compliance |
| `admin@hamyar.test` · `superadmin@hamyar.test` | Admin / super admin |

## Architecture

```
app/
├── Domain/
│   ├── AI/                 Provider-agnostic AI layer (never called from controllers directly)
│   │   ├── Providers/      AIProviderInterface, OpenAICompatibleProvider, LocalHeuristicProvider
│   │   ├── Intake/         IntakeInterviewer (follow-up questions), IntakeAnalyzer (orchestrator)
│   │   ├── Classification/ CaseClassifier (LLM with taxonomy; keyword engine fallback)
│   │   ├── Knowledge/      KnowledgeRetriever (approved-only), GuidanceComposer (cited guidance)
│   │   ├── Summarization/  CaseSummarizer (summary + measurable facts)
│   │   ├── Safety/         SafetyGuard (sensitive flags → human review), PiiRedactor
│   │   └── Matching/       MatchReasonWriter (bilingual match explanations)
│   ├── Business/  Cases/  Experts/  Matching/  Knowledge/  Messaging/  Analytics/  Identity/
│   │   └── each: Models/, Actions/, Enums/ (+ services such as MatchingEngine, KpiService, CaseTimeline)
├── Http/Controllers/       thin controllers grouped by area (Public, Business, Cases, Expert, Review, Admin, Settings)
├── Http/Presenters/        role-aware payloads (e.g. confidential data only after an expert accepts)
├── Policies/  Jobs/  Events/  Listeners/  Notifications/  Services/Files/
resources/js/
├── Components/ui           Design system: Button, Card, StatCard, Badge, Avatar, Modal, Drawer, Tabs, Accordion,
│                           DataTable, Timeline, Uploader, VoiceRecorder, Stepper, Field, Pagination, Skeleton, EmptyState…
├── Components/domain       CaseCard, ExpertCard, KnowledgeCard, KpiCard, ChatBubble, Status/Urgency/Provenance badges…
├── Components/charts       SVG charts (bar, line, donut, funnel) with hover tooltips and table views
├── Components/case         Case panels: analysis, matches, chat, action plan, documents, notes, outcome, review
├── Layouts/  Pages/  i18n/ (fa + en dictionaries)
```

### Case workflow

`Draft → Submitted → AI Processing → Human Review → Ready → Matching → Expert Proposed → Accepted → In Progress → Waiting → Resolved → Closed`

* All transitions go through `TransitionCaseStatus` (state machine, history, timeline event, notifications).
* Confidence below `AI_CONFIDENCE_THRESHOLD` (0.75), a sensitive category or any safety flag → `AiHumanReview` in the review queue.
* Reviewer decisions record AI-vs-human agreement (`category_agreed`, `urgency_agreed`) for the AI accuracy KPI.
* Provenance is stored and shown everywhere: `ai_suggested` / `expert_verified` / `human_approved`.
* Closing without an outcome is rejected; "effective action started" is a separate outcome from "resolved".

### AI

* Set `AI_API_KEY` (and optionally `AI_BASE_URL`, `AI_MODEL`) to use any OpenAI-compatible endpoint. Without a key the deterministic engine is used: keyword taxonomy classification (editable by admins), knowledge-base composition and category-specific intake questions. Both paths produce the same structured output.
* The model is only given approved, published, non-expired knowledge items and must cite their ids; uncited items are dropped.
* Emails, phone numbers and IDs are redacted before any text leaves the platform (`AI_REDACT_PII`).
* Voice notes are transcribed by the provider's `/audio/transcriptions` endpoint when available.

### Security & privacy

Encrypted-at-rest sensitive fields (Eloquent `encrypted` casts), private file storage with random names, 10-minute signed download URLs that also re-check authorisation, audit log for every sensitive view/download/login/role change, consent log, per-field privacy levels chosen by the business, anonymised invitations for experts, RBAC policies, admin 2FA enforcement, rate limiting (login, OTP, AI, uploads, messages), session/device management, ClamAV scanning (`FILE_SCANNER=clamav`), security headers/HSTS, soft deletes, `platform:backup` and `platform:prune-data` (retention policy in `config/platform.php`).

### KPIs

KPIs are rows in `kpis` bound to a metric in `MetricCalculator`, with dated targets in `kpi_targets` and daily `kpi_snapshots` (`kpi:snapshot`). Seeded with the pilot targets: 30 businesses, 15 verified supporters, 50 real cases, initial review ≤ 48h, AI agreement ≥ 80%, clear next action ≥ 70%, match acceptance ≥ 50%, plus satisfaction, effective-action and resolution rates.

## Operations

* Scheduler (`* * * * * php artisan schedule:run`): reminders every 15 min (task deadlines, meetings), KPI snapshot, nightly backup, weekly retention pruning, Horizon snapshots.
* Long-running processes: `deploy/supervisor.conf` (Horizon, Reverb, Inertia SSR). Nginx sample: `deploy/nginx.conf`. Deploy script: `deploy/deploy.sh`.
* Notifications: in-app + email; SMS/WhatsApp channels exist behind drivers (`SMS_DRIVER`, `WHATSAPP_DRIVER`) — the shipped `log` driver records messages; plug a gateway into `SmsChannel`/`WhatsAppChannel`.
* Token API (Sanctum): `GET /api/v1/me`, `/api/v1/cases`, `/api/v1/cases/{number}`, public `/api/v1/knowledge`.

## Tests

```bash
php artisan test      # 36 tests: intake → AI → review → matching → workspace → outcome, RBAC, signed files, OTP, KPIs, i18n/SEO
./vendor/bin/pint --test
```
