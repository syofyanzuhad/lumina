<div class="space-y-6 text-slate-800 dark:text-slate-100">
    <!-- Header & Date Range Controls -->
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="text-2xl font-bold tracking-tight">{{ $site->domain }}</h2>
            <p class="text-sm text-slate-500 dark:text-slate-400">
                Showing analytics from {{ $start->format('M j, Y') }} to {{ $end->format('M j, Y') }}
            </p>
        </div>

        <div class="flex items-center gap-2">
            <button 
                type="button"
                wire:click="setPeriod('7d')"
                class="px-3 py-1.5 text-xs font-semibold rounded-md transition-colors {{ $period === '7d' ? 'bg-indigo-600 text-white dark:bg-indigo-500' : 'bg-slate-100 text-slate-700 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700' }}"
            >
                Last 7 Days
            </button>
            <button 
                type="button"
                wire:click="setPeriod('30d')"
                class="px-3 py-1.5 text-xs font-semibold rounded-md transition-colors {{ $period === '30d' ? 'bg-indigo-600 text-white dark:bg-indigo-500' : 'bg-slate-100 text-slate-700 hover:bg-slate-200 dark:bg-slate-800 dark:text-slate-300 dark:hover:bg-slate-700' }}"
            >
                Last 30 Days
            </button>
        </div>
    </div>

    @if ($total_pageviews === 0)
        <!-- Empty State -->
        <div class="rounded-xl border border-dashed border-slate-300 dark:border-slate-700 p-8 text-center bg-slate-50/50 dark:bg-slate-900/50">
            <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400">
                <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke-width="1.5" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />
                </svg>
            </div>
            <h3 class="mt-4 text-base font-semibold">No data collected yet</h3>
            <p class="mt-1 text-sm text-slate-500 dark:text-slate-400 max-w-md mx-auto">
                Add the tracking snippet to your site's <code class="text-indigo-600 dark:text-indigo-400">&lt;head&gt;</code> to start recording pageviews and visitors.
            </p>
        </div>
    @else
        <!-- Summary KPI Cards -->
        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
            <div class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-slate-500 dark:text-slate-400">Total Pageviews</span>
                    <span class="inline-flex rounded-full bg-indigo-50 dark:bg-indigo-950/50 p-2 text-indigo-600 dark:text-indigo-400">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    </span>
                </div>
                <div class="mt-2 text-3xl font-extrabold">{{ number_format($total_pageviews) }}</div>
            </div>

            <div class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-sm">
                <div class="flex items-center justify-between">
                    <span class="text-sm font-medium text-slate-500 dark:text-slate-400">Unique Visitors</span>
                    <span class="inline-flex rounded-full bg-emerald-50 dark:bg-emerald-950/50 p-2 text-emerald-600 dark:text-emerald-400">
                        <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    </span>
                </div>
                <div class="mt-2 text-3xl font-extrabold">{{ number_format($unique_visitors) }}</div>
            </div>
        </div>

        <!-- Daily Pageviews Bar Chart -->
        <div class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 shadow-sm">
            <h3 class="text-sm font-semibold text-slate-500 dark:text-slate-400 mb-4">Daily Pageviews</h3>
            @php
                $maxDaily = max($daily_pageviews->max('pageviews') ?: 1, 1);
            @endphp
            <div class="flex items-end gap-1 h-36 pt-4">
                @foreach ($daily_pageviews as $day)
                    @php
                        $heightPercent = round(($day['pageviews'] / $maxDaily) * 100);
                    @endphp
                    <div class="flex-1 flex flex-col items-center group relative h-full justify-end">
                        <div 
                            class="w-full bg-indigo-500 dark:bg-indigo-400 rounded-t transition-all group-hover:bg-indigo-600 min-h-[2px]" 
                            style="height: {{ max($heightPercent, 2) }}%;"
                        ></div>
                        <!-- Tooltip -->
                        <div class="absolute bottom-full mb-2 hidden group-hover:block z-10 rounded bg-slate-900 px-2 py-1 text-xs text-white shadow-lg whitespace-nowrap">
                            {{ $day['date'] }}: {{ number_format($day['pageviews']) }} views ({{ number_format($day['visitors']) }} visitors)
                        </div>
                    </div>
                @endforeach
            </div>
        </div>

        <!-- Tables Grid: Top Pages & Top Referrers -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <!-- Top Pages -->
            <div class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-sm">
                <h3 class="text-sm font-semibold text-slate-500 dark:text-slate-400 mb-4">Top Pages</h3>
                <div class="space-y-3">
                    @forelse ($top_pages as $page)
                        <div>
                            <div class="flex justify-between text-xs font-medium mb-1">
                                <span class="truncate font-mono">{{ $page['path'] }}</span>
                                <span>{{ number_format($page['count']) }} ({{ $page['percentage'] }}%)</span>
                            </div>
                            <div class="w-full bg-slate-100 dark:bg-slate-800 h-1.5 rounded-full overflow-hidden">
                                <div class="bg-indigo-600 dark:bg-indigo-500 h-1.5 rounded-full" style="width: {{ $page['percentage'] }}%"></div>
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-slate-400">No pageviews recorded.</p>
                    @endforelse
                </div>
            </div>

            <!-- Top Referrers -->
            <div class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-sm">
                <h3 class="text-sm font-semibold text-slate-500 dark:text-slate-400 mb-4">Top Referrers</h3>
                <div class="space-y-3">
                    @forelse ($top_referrers as $ref)
                        <div>
                            <div class="flex justify-between text-xs font-medium mb-1">
                                <span class="truncate font-mono">{{ $ref['referrer'] }}</span>
                                <span>{{ number_format($ref['count']) }} ({{ $ref['percentage'] }}%)</span>
                            </div>
                            <div class="w-full bg-slate-100 dark:bg-slate-800 h-1.5 rounded-full overflow-hidden">
                                <div class="bg-emerald-600 dark:bg-emerald-500 h-1.5 rounded-full" style="width: {{ $ref['percentage'] }}%"></div>
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-slate-400">No external referrers.</p>
                    @endforelse
                </div>
            </div>
        </div>

        @if ($custom_events->count() > 0)
            <!-- Custom Events Table -->
            <div class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-sm">
                <h3 class="text-sm font-semibold text-slate-500 dark:text-slate-400 mb-4">Custom Events</h3>
                <div class="space-y-3">
                    @foreach ($custom_events as $event)
                        <div class="flex justify-between items-center text-xs font-medium py-1.5 border-b border-slate-100 dark:border-slate-800 last:border-0">
                            <span class="font-mono bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded text-indigo-600 dark:text-indigo-400">{{ $event['name'] }}</span>
                            <span class="font-bold">{{ number_format($event['count']) }}</span>
                        </div>
                    @endforeach
                </div>
            </div>
        @endif
    @endif
</div>
