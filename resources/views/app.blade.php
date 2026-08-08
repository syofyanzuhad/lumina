<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}"  @class(['dark' => ($appearance ?? 'system') == 'dark'])>
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">

        {{-- Inline script to detect system dark mode preference and apply it immediately --}}
        <script>
            (function() {
                const appearance = '{{ $appearance ?? "system" }}';

                if (appearance === 'system') {
                    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;

                    if (prefersDark) {
                        document.documentElement.classList.add('dark');
                    }
                }
            })();
        </script>

        {{-- Inline style to set the HTML background color based on our theme in app.css --}}
        <style>
            html {
                background-color: oklch(1 0 0);
            }

            html.dark {
                background-color: oklch(0.145 0 0);
            }
        </style>

        <link rel="icon" href="/favicon.ico" sizes="any">
        <link rel="icon" href="/favicon.svg" type="image/svg+xml">
        <link rel="apple-touch-icon" href="/apple-touch-icon.png">

        @fonts

        @vite(['resources/css/app.css', 'resources/js/app.ts', "resources/js/pages/{$page['component']}.vue"])
        <x-inertia::head>
            <title>Lumina — Lightweight Web Analytics for Laravel</title>
            <meta name="description" content="Self-hosted, cookie-free web analytics for Laravel applications under 2KB script payload. Track pageviews, custom events, and conversion goals with zero infrastructure hassle.">
            <meta name="keywords" content="Laravel analytics, web analytics, self-hosted analytics, cookie-free analytics, privacy friendly analytics, Plausible alternative, GDPR compliant analytics">
            <link rel="canonical" href="https://uselumina.laravel.cloud">

            <!-- Open Graph / Facebook -->
            <meta property="og:type" content="website">
            <meta property="og:title" content="Lumina — Lightweight Web Analytics for Laravel">
            <meta property="og:description" content="Self-hosted web analytics native to Laravel. < 2KB tracker payload, 100% cookie-free GDPR privacy, and conversion goal tracking.">
            <meta property="og:url" content="https://uselumina.laravel.cloud">
            <meta property="og:site_name" content="Lumina Analytics">
            <meta property="og:image" content="https://uselumina.laravel.cloud/og-image.jpg">
            <meta property="og:image:width" content="1200">
            <meta property="og:image:height" content="630">

            <!-- Twitter -->
            <meta name="twitter:card" content="summary_large_image">
            <meta name="twitter:title" content="Lumina — Lightweight Web Analytics for Laravel">
            <meta name="twitter:description" content="Self-hosted web analytics native to Laravel. < 2KB tracker payload, 100% cookie-free GDPR privacy, and conversion goal tracking.">
            <meta name="twitter:image" content="https://uselumina.laravel.cloud/og-image.jpg">
        </x-inertia::head>
    </head>
    <body class="font-sans antialiased">
        <x-inertia::app />
    </body>
</html>
