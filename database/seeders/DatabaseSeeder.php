<?php

namespace Database\Seeders;

use App\Models\KnowledgeEntry;
use App\Models\PageSection;
use App\Models\PaymentGatewayAccount;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $admin = $this->seedSuperAdmin();
        $this->seedSettings();
        $this->seedPageSections();
        $this->seedKnowledgeBase($admin);
        $this->seedMerchantAccounts();
    }

    /**
     * Sandbox merchant accounts for testing (Stripe test keys +
     * PayPal sandbox credentials). Only one is active at a time —
     * swap them from Admin → Merchants.
     */
    private function seedMerchantAccounts(): void
    {
        $stripe = PaymentGatewayAccount::query()->updateOrCreate(
            ['gateway' => PaymentGatewayAccount::GATEWAY_STRIPE, 'name' => 'Stripe Sandbox'],
            [
                'mode' => PaymentGatewayAccount::MODE_SANDBOX,
                'credentials' => [
                    'publishable_key' => (string) env('STRIPE_PUBLISHABLE_KEY', ''),
                    'secret_key' => (string) env('STRIPE_SECRET_KEY', ''),
                ],
                'is_active' => false,
            ],
        );

        PaymentGatewayAccount::query()->updateOrCreate(
            ['gateway' => PaymentGatewayAccount::GATEWAY_PAYPAL, 'name' => 'PayPal Sandbox'],
            [
                'mode' => PaymentGatewayAccount::MODE_SANDBOX,
                'credentials' => [
                    'client_id' => (string) env('PAYPAL_CLIENT_ID', ''),
                    'client_secret' => (string) env('PAYPAL_CLIENT_SECRET', ''),
                ],
                'is_active' => false,
            ],
        );

        // Make sure exactly one account is active (Stripe sandbox by default).
        if (PaymentGatewayAccount::getActive() === null) {
            $stripe->activate();
        }
    }

    private function seedSuperAdmin(): User
    {
        $adminPassword = env('ADMIN_PASSWORD');

        if (! is_string($adminPassword) || $adminPassword === '') {
            throw new \RuntimeException('Set ADMIN_PASSWORD in .env before seeding.');
        }

        return User::query()->updateOrCreate(
            ['email' => 'admin@arsbot.test'],
            [
                'name' => 'Platform Owner',
                'password' => Hash::make($adminPassword),
                'role' => User::ROLE_SUPER_ADMIN,
                'company' => 'ARS Bot',
                'is_paid' => true,
                'paid_at' => now(),
                'bot_active' => true,
                'api_key' => User::generateApiKey(),
                'bot_name' => 'ARS Bot',
                'bot_color' => '#22C55E',
                'subscription_plan' => 'lifetime',
                'subscription_ends_at' => null,
            ],
        );
    }

    private function seedSettings(): void
    {
        Setting::put('ai_base_url', Setting::get('ai_base_url') ?: 'https://generativelanguage.googleapis.com/v1beta/openai');
        Setting::put('ai_model', Setting::get('ai_model') ?: 'gemini-flash-lite-latest');
        Setting::put('ai_api_key', Setting::get('ai_api_key') ?: (string) env('AI_API_KEY', ''));
        Setting::put('crm_brand_name', 'ARS Bot');
        Setting::put('crm_brand_color', '#22C55E');
    }

    private function seedPageSections(): void
    {
        $sections = [
            // -------- WORK --------
            ['work', 'Meer Official', 'E-commerce / Branding', 'A complete fashion e-commerce experience — brand identity, storefront design and a checkout flow built to convert.', 'page-sections/project-meer-official.png', null, 1],
            ['work', 'My Delivery Partner', 'Web App / Logistics', 'A delivery platform connecting customers with local partners — booking, tracking and partner dashboards.', 'page-sections/project-my-delivery-partner.png', null, 2],
            ['work', 'Kahani Apparel', 'E-commerce / Fashion', 'A storytelling-led apparel brand with a bold visual language and a seamless online shopping experience.', 'page-sections/project-kahani-apparel.png', null, 3],
            ['work', 'GAB Trading', 'Corporate / Web', 'A corporate web presence for a global trading company — clean, credible and built for international clients.', 'page-sections/project-gab-trading.png', null, 4],
            ['work', 'Pick My Drop', 'Web App / Delivery', 'On-demand pickup and delivery — a smooth app-like experience from order to doorstep.', 'page-sections/project-pick-my-drop.png', null, 5],
            ['work', 'My Local Chippy', 'Food & Beverage / Ordering', 'A local food brand with online ordering, mouth-watering visuals and a menu experience that sells.', 'page-sections/project-my-local-chippy.png', null, 6],

            // -------- AGENCY --------
            ['agency', 'Innovative solutions for the digital world', 'Who we are', "At our Creative Digital Agency, we bring your ideas to life by crafting engaging, impactful digital experiences that captivate audiences and drive results.\n\nFrom innovative web design to compelling content and cutting-edge digital strategies — we are the partner behind brands that grow.", 'page-sections/why-choose-ars-bot.jpg', null, 1],
            ['agency', 'Our digital services to grow your brand', 'What we do', "Custom Branding — cohesive visual identities that build memorable brands.\nWebsite Design — fast, beautiful sites that convert visitors into customers.\nDigital Marketing — communities and campaigns that lead to conversions.\nStrategy Consulting — positioning, research and roadmaps that work.\nAnalytics & Reporting — data-driven decisions, measurable results.\nCreative Content Production — visual hooks that cut through the noise.", null, null, 2],
            ['agency', 'Expertise that drives digital success', 'Results', "+60% organic traffic by optimizing websites for search engines.\n+30% rise in revenue as more visitors convert into paying customers.\nSocial Media Management, E-commerce Solutions and everything in between — designed to perform.", null, null, 3],
            ['agency', 'Watch how we work', 'Showreel', 'A quick look at the craft, the process and the people behind our projects.', null, 'storage/page-sections/ars-bot-showreel.mp4', 4],

            // -------- CAREERS --------
            ['careers', 'Join our agency of creative innovators', 'Culture', "Join our creative community to collaborate, innovate, and thrive together. We welcome passionate individuals eager to make an impact.\n\nWe are always looking for designers, developers, marketers and strategists who care about craft and want to grow loud.", null, null, 1],
            ['careers', 'Frontend Developer', 'Full-time / Remote', 'Build fast, animated, beautiful interfaces with modern tooling. You love clean code, smooth motion and shipping work you are proud of.', null, null, 2],
            ['careers', 'Graphic Designer', 'Full-time / Hybrid', 'Craft brand identities, campaign visuals and digital assets that make people stop scrolling and start looking.', null, null, 3],
            ['careers', 'Digital Marketing Specialist', 'Full-time / Remote', 'Plan and run campaigns across social and search, measure everything, and turn data into growth.', null, null, 4],
        ];

        foreach ($sections as [$page, $title, $subtitle, $content, $image, $video, $order]) {
            PageSection::query()->updateOrCreate(
                ['page' => $page, 'title' => $title],
                [
                    'subtitle' => $subtitle,
                    'content' => $content,
                    'image_path' => $image,
                    'video_url' => $video,
                    'sort_order' => $order,
                    'is_active' => true,
                ],
            );
        }
    }

    private function seedKnowledgeBase(User $admin): void
    {
        $entries = [
            ['About ARS Bot', 'text', 'ARS Bot is a creative digital agency. We bring ideas to life by crafting engaging, impactful digital experiences that captivate audiences and drive results. Our services cover branding, website design, digital marketing, strategy consulting, analytics and creative content production.'],
            ['Services we offer', 'service', "Custom Branding & Identity — cohesive visual identities that build memorable brands.\nWebsite Design & Development — fast, beautiful, conversion-focused websites.\nDigital Marketing — campaigns and communities that drive conversions.\nStrategy Consulting — positioning, research and digital roadmaps.\nAnalytics & Reporting — data-driven decisions and measurable results.\nCreative Content Production — visual content that demands attention.\nSocial Media Management — consistent, engaging brand voice across platforms.\nE-commerce Solutions — seamless online shopping experiences."],
            ['Our projects', 'text', 'Recent projects: Meer Official (fashion e-commerce), My Delivery Partner (delivery platform), Kahani Apparel (fashion brand), GAB Trading (corporate web), Pick My Drop (delivery app), My Local Chippy (food ordering). See the Work page for details.'],
            ['Why choose us', 'faq', 'Data-driven approach: we use insights to make informed decisions. Competitive pricing: top-quality services at great value. Ethical business practices: the highest level of professionalism in all dealings.'],
            ['Contact information', 'text', "Email: jaffri.arsalan786@gmail.com\nPhone / WhatsApp: +92 330 3914554\nSocial: LinkedIn (arsalan-jaffri-3b796b370), Facebook, Instagram (@arsalan85890).\nWe reply within 1 business day."],
            ['How to start a project', 'faq', 'Share your name, company, project type (branding / website / marketing), budget range, timeline and a short brief. We reply with next steps and a free quote.'],
            ['Pricing approach', 'faq', 'Every project is quoted individually based on scope. We offer competitive pricing for top-quality work — get a free quote by sharing your requirements. Typical website projects start from a few hundred dollars; branding and full campaigns are quoted per scope.'],
            ['Payment terms', 'policy', 'Projects are typically billed 50% to start and 50% on delivery. Larger projects can be split into milestones. Invoices are sent by email.'],
            ['Order confirmation policy', 'policy', 'When a client confirms they want to proceed with a specific service and budget in chat, that counts as a confirmed order. The team follows up within 1 business day with a formal proposal and invoice.'],
        ];

        foreach ($entries as [$title, $type, $content]) {
            KnowledgeEntry::query()->updateOrCreate(
                ['user_id' => $admin->id, 'title' => $title],
                ['content' => $content, 'type' => $type, 'is_active' => true],
            );
        }
    }
}
