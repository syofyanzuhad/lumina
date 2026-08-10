<?php

namespace Database\Seeders;

use App\Console\Commands\BackfillDailyVisitorStats;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Lumina\Core\Models\Goal;
use Lumina\Core\Models\Site;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // 1. Create Default Owner
        $user = User::firstOrCreate(
            ['email' => 'admin@lumina.dev'],
            [
                'name' => 'Lumina Admin',
                'password' => bcrypt('password'),
            ]
        );

        // 2. Create Demo Shareable Site
        $site = Site::firstOrCreate(
            ['domain' => 'demo.lumina.dev'],
            [
                'owner_id' => $user->id,
                'is_public' => true,
                'share_token' => 'demo-share-token-analytics',
                'share_password' => null, // Password-free demo
            ]
        );

        // Ensure share settings are set
        $site->update([
            'is_public' => true,
            'share_token' => 'demo-share-token-analytics',
            'share_password' => null,
        ]);

        // 3. Create Goals for Demo Site
        Goal::firstOrCreate(
            ['site_id' => $site->id, 'name' => 'Pricing Page Visit'],
            ['target_type' => 'path', 'target_value' => '/pricing']
        );

        Goal::firstOrCreate(
            ['site_id' => $site->id, 'name' => 'Signup Conversions'],
            ['target_type' => 'custom_event', 'target_value' => 'signup_completed']
        );

        // 4. Seed Super Heavy Demo Analytics Data (150,000 records) across 365 days
        $paths = [
            '/', '/', '/', '/', '/pricing', '/pricing', '/features', '/docs', '/docs/installation',
            '/docs/laravel-setup', '/docs/api-reference', '/docs/webhooks', '/docs/security', '/docs/authentication',
            '/blog/laravel-13-analytics', '/blog/privacy-first-tracking', '/blog/building-fast-dashboards',
            '/blog/scaling-postgres-timescale', '/blog/cookieless-future-2026', '/blog/open-source-analytics',
            '/blog/gdpr-compliance-guide', '/blog/migrating-from-google-analytics', '/blog/event-tracking-best-practices',
            '/signup', '/signup', '/login', '/dashboard', '/settings', '/integrations', '/changelog',
            '/case-studies/acme-corp', '/case-studies/techstart', '/case-studies/global-fintech', '/case-studies/ecommerce-giant',
            '/api-reference', '/downloads', '/careers', '/about', '/contact', '/privacy', '/terms', '/security-overview',
        ];

        $browsers = [
            'Chrome', 'Chrome', 'Chrome', 'Chrome', 'Safari', 'Safari', 'Safari', 'Firefox', 'Edge', 'Brave', 'Opera',
            'Samsung Internet', 'Vivaldi', 'UC Browser', 'Arc', 'DuckDuckGo Browser', 'Yandex Browser', 'Tor Browser',
            'Sogou Explorer', 'QQ Browser', 'Pale Moon', 'Waterfox',
        ];

        $operatingSystems = [
            'macOS', 'macOS', 'Windows', 'Windows', 'Windows', 'iOS', 'iOS', 'Android', 'Android', 'Linux', 'Ubuntu',
            'Debian', 'Fedora', 'Arch Linux', 'Chrome OS', 'FreeBSD', 'Windows Phone', 'CentOS', 'Rocky Linux', 'Alpine Linux',
        ];

        $countries = [
            ['code' => 'US', 'name' => 'United States'],
            ['code' => 'US', 'name' => 'United States'],
            ['code' => 'GB', 'name' => 'United Kingdom'],
            ['code' => 'DE', 'name' => 'Germany'],
            ['code' => 'ID', 'name' => 'Indonesia'],
            ['code' => 'JP', 'name' => 'Japan'],
            ['code' => 'CA', 'name' => 'Canada'],
            ['code' => 'FR', 'name' => 'France'],
            ['code' => 'AU', 'name' => 'Australia'],
            ['code' => 'NL', 'name' => 'Netherlands'],
            ['code' => 'SG', 'name' => 'Singapore'],
            ['code' => 'BR', 'name' => 'Brazil'],
            ['code' => 'IN', 'name' => 'India'],
            ['code' => 'KR', 'name' => 'South Korea'],
            ['code' => 'ES', 'name' => 'Spain'],
            ['code' => 'IT', 'name' => 'Italy'],
            ['code' => 'SE', 'name' => 'Sweden'],
            ['code' => 'CH', 'name' => 'Switzerland'],
            ['code' => 'VN', 'name' => 'Vietnam'],
            ['code' => 'MY', 'name' => 'Malaysia'],
            ['code' => 'PH', 'name' => 'Philippines'],
            ['code' => 'MX', 'name' => 'Mexico'],
            ['code' => 'PL', 'name' => 'Poland'],
            ['code' => 'NO', 'name' => 'Norway'],
            ['code' => 'NZ', 'name' => 'New Zealand'],
        ];

        $referrers = [
            'https://google.com', 'https://google.com', 'https://google.com',
            'https://github.com', 'https://github.com',
            'https://x.com', 'https://x.com',
            'https://news.ycombinator.com', 'https://news.ycombinator.com',
            'https://producthunt.com', 'https://reddit.com/r/laravel', 'https://reddit.com/r/webdev',
            'https://dev.to', 'https://medium.com', 'https://indiehackers.com',
            'https://youtube.com', 'https://linkedin.com', 'https://bing.com', 'https://duckduckgo.com',
            'https://facebook.com', 'https://instagram.com', 'https://tiktok.com', 'https://t.co',
            'https://hashnode.com', 'https://substack.com', 'Direct', 'Direct', 'Direct',
        ];

        $customEvents = [
            'signup_completed', 'checkout_started', 'button_clicked', 'video_watched',
            'code_copied', 'demo_requested', 'export_pdf', 'api_key_created',
            'dark_mode_toggled', 'filter_applied', 'invite_sent', 'webhook_added',
            'billing_plan_upgraded', 'password_reset_requested', 'team_member_added',
            'report_generated', 'search_performed', 'theme_changed', 'feedback_submitted', 'app_downloaded',
        ];

        $utmCampaigns = [
            'summer_sale_2026', 'product_hunt_launch', 'black_friday_deals',
            'newsletter_august', 'twitter_ads_q3', 'google_search_brand',
            'laravel_news_sponsorship', 'github_sponsors_promo', 'devto_banner_ad',
            'youtube_influencer_q3', 'podcasts_sponsorship', 'reddit_promoted_post',
            'linkedin_b2b_outreach', 'hacker_news_show_hn', 'indie_hackers_spotlight',
            'retargeting_campaign_v2', 'influencer_partner_code', 'spring_discount_2026',
            'webinar_registration_q3', 'community_giveaway',
        ];

        // Insert 50,000 additional events in batch chunks of 2,000 for maximum seeding speed
        $records = [];
        for ($i = 0; $i < 50_000; $i++) {
            $daysAgo = rand(0, 364);
            $createdAt = now()->subDays($daysAgo)->subHours(rand(0, 23))->subMinutes(rand(0, 59))->subSeconds(rand(0, 59));
            $country = $countries[array_rand($countries)];
            $path = $paths[array_rand($paths)];
            $isCustomEvent = rand(1, 10) <= 3; // 30% custom events

            $metadata = null;
            if ($isCustomEvent) {
                $eventName = $customEvents[array_rand($customEvents)];
                $metadata = [
                    'name' => $eventName,
                    'props' => [
                        'plan' => ['free', 'pro', 'enterprise'][rand(0, 2)],
                        'source' => ['hero', 'pricing_table', 'modal', 'footer', 'sidebar'][rand(0, 4)],
                        'currency' => 'USD',
                        'value' => rand(10, 500),
                    ],
                ];
            } elseif (rand(1, 4) === 1) {
                // 25% chance of UTM campaign for pageviews
                $metadata = [
                    'utm_campaign' => $utmCampaigns[array_rand($utmCampaigns)],
                    'utm_source' => ['newsletter', 'twitter', 'google', 'partner', 'youtube', 'reddit'][rand(0, 5)],
                    'utm_medium' => ['cpc', 'social', 'email', 'referral', 'banner'][rand(0, 4)],
                ];
            }

            $records[] = [
                'site_id' => $site->id,
                'path' => $path,
                'referrer' => $referrers[array_rand($referrers)],
                'visitor_hash' => hash('sha256', 'visitor_'.rand(1, 4500).'_'.$createdAt->format('Y-m-d')),
                'browser' => $browsers[array_rand($browsers)],
                'os' => $operatingSystems[array_rand($operatingSystems)],
                'country_code' => $country['code'],
                'country_name' => $country['name'],
                'device_type' => ['desktop', 'desktop', 'mobile', 'mobile', 'tablet'][rand(0, 4)],
                'metadata' => $metadata ? json_encode($metadata) : null,
                'created_at' => $createdAt,
            ];

            if (count($records) >= 2000) {
                DB::table('events')->insert($records);
                $records = [];
            }
        }

        if (! empty($records)) {
            DB::table('events')->insert($records);
        }

        // Populate daily_visitor_stats for seeded data
        $this->call(BackfillDailyVisitorStats::class);
    }
}
