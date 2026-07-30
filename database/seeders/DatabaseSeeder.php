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

        // 4. Seed Realistic Events across the last 30 days
        if (Event::where('site_id', $site->id)->count() < 100) {
            $paths = ['/', '/pricing', '/features', '/docs', '/blog/getting-started', '/signup'];
            $browsers = ['Chrome', 'Safari', 'Firefox', 'Edge'];
            $operatingSystems = ['macOS', 'Windows', 'iOS', 'Android', 'Linux'];
            $countries = [
                ['code' => 'US', 'name' => 'United States'],
                ['code' => 'GB', 'name' => 'United Kingdom'],
                ['code' => 'DE', 'name' => 'Germany'],
                ['code' => 'ID', 'name' => 'Indonesia'],
                ['code' => 'JP', 'name' => 'Japan'],
            ];
            $referrers = ['https://google.com', 'https://github.com', 'https://twitter.com', 'https://news.ycombinator.com', 'Direct'];
            $customEvents = ['signup_completed', 'checkout_started', 'button_clicked', 'video_watched'];

            for ($i = 0; $i < 300; $i++) {
                $daysAgo = rand(0, 29);
                $createdAt = now()->subDays($daysAgo)->subHours(rand(0, 23))->subMinutes(rand(0, 59));
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
                            'source' => ['nav', 'hero', 'footer'][rand(0, 2)],
                        ],
                    ];
                }

                DB::table('events')->insert([
                    'site_id' => $site->id,
                    'path' => $path,
                    'referrer' => $referrers[array_rand($referrers)],
                    'visitor_hash' => hash('sha256', 'visitor_' . rand(1, 40) . '_' . $createdAt->format('Y-m-d')),
                    'browser' => $browsers[array_rand($browsers)],
                    'os' => $operatingSystems[array_rand($operatingSystems)],
                    'country_code' => $country['code'],
                    'country_name' => $country['name'],
                    'device_type' => ['desktop', 'mobile', 'tablet'][rand(0, 2)],
                    'metadata' => $metadata ? json_encode($metadata) : null,
                    'created_at' => $createdAt,
                ]);
            }
        }
    }
}
