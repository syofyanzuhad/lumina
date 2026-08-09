<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Lumina\Core\Models\Event;
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

        // 4. Seed Rich & Heavy Demo Analytics Data across the last 90 days
        $existingCount = Event::where('site_id', $site->id)->count();
        if ($existingCount < 5000) {
            $paths = [
                '/', '/pricing', '/features', '/docs', '/docs/installation', '/docs/laravel-setup',
                '/blog/laravel-13-analytics', '/blog/privacy-first-tracking', '/blog/building-fast-dashboards',
                '/signup', '/login', '/dashboard', '/settings', '/integrations', '/changelog',
                '/case-studies/acme-corp', '/case-studies/techstart', '/api-reference'
            ];

            $browsers = [
                'Chrome', 'Chrome', 'Chrome', 'Safari', 'Safari', 'Firefox', 'Edge', 'Brave', 'Opera'
            ];

            $operatingSystems = [
                'macOS', 'macOS', 'Windows', 'Windows', 'iOS', 'Android', 'Linux'
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
            ];

            $referrers = [
                'https://google.com', 'https://google.com', 'https://google.com',
                'https://github.com', 'https://github.com',
                'https://x.com', 'https://x.com',
                'https://news.ycombinator.com', 'https://news.ycombinator.com',
                'https://producthunt.com', 'https://reddit.com/r/laravel',
                'https://dev.to', 'https://medium.com', 'Direct'
            ];

            $customEvents = ['signup_completed', 'checkout_started', 'button_clicked', 'video_watched', 'code_copied', 'demo_requested', 'export_pdf'];

            $utmCampaigns = ['summer_sale', 'product_hunt_launch', 'black_friday', 'newsletter_august', 'twitter_ads'];

            // Insert in chunks of 500 records for maximum performance
            $records = [];
            for ($i = 0; $i < 10000; $i++) {
                $daysAgo = rand(0, 89);
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
                            'source' => ['hero', 'pricing_table', 'modal', 'footer'][rand(0, 3)],
                            'currency' => 'USD',
                        ],
                    ];
                } elseif (rand(1, 4) === 1) {
                    // 25% chance of UTM campaign for pageviews
                    $metadata = [
                        'utm_campaign' => $utmCampaigns[array_rand($utmCampaigns)],
                        'utm_source' => ['newsletter', 'twitter', 'google', 'partner'][rand(0, 3)],
                        'utm_medium' => ['cpc', 'social', 'email'][rand(0, 2)],
                    ];
                }

                $records[] = [
                    'site_id' => $site->id,
                    'path' => $path,
                    'referrer' => $referrers[array_rand($referrers)],
                    'visitor_hash' => hash('sha256', 'visitor_'.rand(1, 450).'_'.$createdAt->format('Y-m-d')),
                    'browser' => $browsers[array_rand($browsers)],
                    'os' => $operatingSystems[array_rand($operatingSystems)],
                    'country_code' => $country['code'],
                    'country_name' => $country['name'],
                    'device_type' => ['desktop', 'desktop', 'mobile', 'mobile', 'tablet'][rand(0, 4)],
                    'metadata' => $metadata ? json_encode($metadata) : null,
                    'created_at' => $createdAt,
                ];

                if (count($records) >= 500) {
                    DB::table('events')->insert($records);
                    $records = [];
                }
            }

            if (!empty($records)) {
                DB::table('events')->insert($records);
            }
        }
    }
}
