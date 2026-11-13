# ARS Bot Platform — Full-Stack SaaS

Ek complete multi-tenant bot platform: **Super Admin CRM + Client CRM + AI bot + Payments + Invoices + API/widget**.
Laravel 13 (PHP 8.5) + SQLite + Tailwind 4 + vanilla JS bot frontend.

---

## Roles

| Role | Access | Payment |
|---|---|---|
| **Super Admin** | `/admin` — users, clients, plans, payments, settings, about, own bot | Nahi chahiye (zaati bot) |
| **Client user** | `/crm` — dashboard, clients, bot branding, knowledge base, API key, billing | Subscription required |
| **Website visitor** | Public bot (`/`, `/bot/{key}`, embed widget) | — |

## Core flow

1. **Super Admin** logs in at `/login` → `/admin` dashboard
2. Super Admin **user banata hai** → user ko email jaata hai: **login credentials + payment link**
3. User **login karta hai bina payment ke** → "Please pay first" page + payment link
4. User **pay karta hai** (`/pay/{reference}`) → subscription activate, bot enable, CRM unlock
5. User apne CRM se **API key** uthata hai aur apni website pe widget lagata hai
6. Website visitors bot se chat karte hain → AI (knowledge base ke saath) handle karta hai
7. Bot **client details + summary + order confirmation** CRM mein save karta hai
8. Order confirm hone par **PDF invoice email** jaata hai + CRM notification + counter
9. Subscription **expire** hoti hai (weekly/monthly) → bot auto-disable, status "not paid"

---

## Setup

```bash
composer install
npm install && npm run build
php artisan migrate --seed
php artisan storage:link
php artisan serve
```

**Database (MySQL / XAMPP):** `.env` mein set karein:

```env
DB_CONNECTION=mysql
DB_HOST=127.0.0.1
DB_PORT=3306
DB_DATABASE=test
DB_USERNAME=root
DB_PASSWORD=
```

Phir `php artisan migrate --seed` chalayein.

**Seeded account:** set `ADMIN_PASSWORD` in `.env` before running `php artisan migrate --seed`; the admin email is `admin@arsbot.test`.

**AI brain (Google AI Studio / Gemini):** Admin → Settings mein set hota hai:

| Setting | Value |
|---|---|
| API key | `AQ.Ab8RN6...` (aapki Google AI Studio key) |
| Base URL | `https://generativelanguage.googleapis.com/v1beta/openai` |
| Model | `gemini-flash-lite-latest` (stable, fast) |

Koi bhi OpenAI-compatible provider chalega: OpenAI, Groq, DeepSeek, OpenRouter, Google Gemini, vLLM…
Key na ho toh bot ek built-in fallback responder use karta hai — demo bhi chalta rehta hai.

**Email (Gmail SMTP):** `.env` mein set hai — live emails jaati hain:

```env
MAIL_MAILER=smtp
MAIL_SCHEME=smtp
MAIL_HOST=smtp.gmail.com
MAIL_PORT=587
MAIL_USERNAME="${MAIL_USERNAME}"
MAIL_PASSWORD="your-gmail-app-password"
MAIL_FROM_ADDRESS="${MAIL_FROM_ADDRESS}"
```

Ye emails bhejta hai: user credentials + payment link, order invoice (PDF attach), new client notifications, payment reminders.

**Scheduler (production):** `php artisan schedule:work` — subscriptions har ghante expire check karta hai.

---

## Features

### Super Admin (`/admin`)
- Dashboard: monthly/weekly/total revenue, active bots, users, clients, 30-day revenue chart, top buyers
- **Users CRUD** — create (email with credentials + payment link), edit, delete, **bot activate/deactivate toggle** (sirf paid users ke liye enable)
- Mark as paid (manual/off-platform), resend credentials
- **Clients tab** — saare bots ke captured clients, **delete-only**, unread badge + "mark all read"
- **Plans CRUD** — name, price, duration (weekly/monthly/custom), features
- Payments list — collected/pending totals
- Settings — AI key/model/base URL, CRM branding (name/color/logo)
- Own bot — name, avatar upload, color, theme, global knowledge base, API key + regenerate
- **About content CRUD** — title, text, image upload, video URL, ordering

### Client CRM (`/crm`)
- Dashboard — clients, unread count, orders, order value, 7-day conversations, 30-day chart, recent conversations
- **Clients** — list/search/filter (unread, orders), detail view with **full chat history**, AI summary, needs, order info, status (new/contacted/won/lost), delete
- **Bot & branding** — avatar upload, name, accent color, theme, global knowledge
- **Knowledge base** — CRUD entries (general/faq/product/service/policy) → AI inhi se jawab deta hai
- **API & embed** — API key, copy-paste embed snippet, direct bot page/embed/API links, regenerate key
- **Billing** — current subscription, payment history, plan checkout/extend
- Notifications — naye clients/orders par badge counter

### Public bot
- `/` — platform ka apna bot (super admin ka)
- `/bot/{key}` — customer ka standalone bot page (nav link target)
- `/embed/{key}` — compact iframe UI
- `/widget.js?key=...` — embeddable script: floating launcher + panel **+ optional nav link** (`data-nav-link="true"`)
- API: `POST /api/bot/chat` (X-Api-Key) → `{ reply, conversation_id, order_confirmed, order_number }`
- Bot frontend: ARS Bot-style live chat — typewriter, voice (Web Speech), ambient pad, visualizer, 3D sprite avatar (bot3d.js)

### Backend engine
- `AiService` — OpenAI-compatible chat + structured extraction, settings-driven
- `BotChatService` — conversation lifecycle, system prompt (bot settings + knowledge), message persistence
- `ClientSummaryService` — rolling AI summary per conversation
- `InvoiceService` — order creation, DomPDF invoice, email with attachment
- `SubscriptionService` — payment links, mark paid, expiry, credentials email
- `subscriptions:expire` command — hourly schedule, auto-disable + status reset

---

## Project layout

```
app/
  Console/Commands/ExpireSubscriptions.php
  Http/Controllers/
    Admin/       Dashboard, User, Client, Plan, Payment, Settings, About
    Api/         BotChat, Widget
    Auth/        LoginController
    Crm/         Dashboard, Client, Knowledge, BotSettings, Billing
    BotPageController.php, PaymentController.php
  Http/Middleware/  EnsureSuperAdmin, EnsureSubscriptionActive, AuthenticateBotApiKey
  Mail/        UserCredentialsMail, ClientInvoiceMail, NewClientNotificationMail, PaymentRequiredMail
  Models/      User, Plan, Payment, Client, Conversation, Message, Order, KnowledgeEntry, AboutSection, Setting
  Services/    AiService, BotChatService, ClientSummaryService, InvoiceService, SubscriptionService
public/assets/  bot frontend (scss, css, js, video)
resources/views/
  admin/ crm/ auth/ payment/ bot/ emails/ invoices/ widget/ layouts/ components/
database/migrations/  users+crm fields, plans, payments, clients, conversations, messages, orders, knowledge, about, settings
tests/Feature/  AuthFlowTest, BotApiTest, SubscriptionLifecycleTest, AdminUserManagementTest
```

## Tests

```bash
php artisan test --compact
```

35 tests / 92 assertions — auth flows, payment gating, bot API, subscription lifecycle, admin management, page rendering.

## Notes

- **Payments**: abhi simulated gateway hai (button → markPaid). Production mein `PaymentController::confirm` ko Stripe/Paddle webhook se replace karein — `SubscriptionService::markPaid` same rahega.
- **Mail**: `.env` mein `MAIL_MAILER=log` — emails `storage/logs/laravel.log` mein. Production mein SMTP set karein.
- **DB**: SQLite by default. MySQL ke liye `.env` mein `DB_CONNECTION=mysql` set karein.
- Sab code original hai.
