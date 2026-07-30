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

    <!-- Tab Header Controls -->
    <div class="flex items-center gap-1.5 p-1 bg-slate-100 dark:bg-slate-800 rounded-xl border border-slate-200 dark:border-slate-700 self-start w-max">
        <button
            wire:click="setTab('overview')"
            class="px-3 py-1.5 text-xs font-semibold rounded-lg transition-all {{ $activeTab === 'overview' ? 'bg-indigo-600 text-white shadow-md shadow-indigo-500/20 dark:bg-indigo-500' : 'bg-transparent text-slate-500 hover:text-slate-900 hover:bg-slate-200/50 dark:text-slate-400 dark:hover:text-slate-100 dark:hover:bg-slate-700/50' }}"
        >
            Overview
        </button>
        <button
            wire:click="setTab('events')"
            class="px-3 py-1.5 text-xs font-semibold rounded-lg transition-all {{ $activeTab === 'events' ? 'bg-indigo-600 text-white shadow-md shadow-indigo-500/20 dark:bg-indigo-500' : 'bg-transparent text-slate-500 hover:text-slate-900 hover:bg-slate-200/50 dark:text-slate-400 dark:hover:text-slate-100 dark:hover:bg-slate-700/50' }}"
        >
            Custom Events
        </button>
    </div>

    @if ($activeTab === 'overview')

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
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
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

            <!-- Device Breakdown -->
            <div class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-sm">
                <h3 class="text-sm font-semibold text-slate-500 dark:text-slate-400 mb-4">Device Types</h3>
                <div class="space-y-3">
                    @forelse ($device_breakdown ?? [] as $dev)
                        <div>
                            <div class="flex justify-between text-xs font-medium mb-1">
                                <span class="truncate font-mono capitalize">{{ $dev['device'] }}</span>
                                <span>{{ number_format($dev['count']) }} ({{ $dev['percentage'] }}%)</span>
                            </div>
                            <div class="w-full bg-slate-100 dark:bg-slate-800 h-1.5 rounded-full overflow-hidden">
                                <div class="bg-amber-500 dark:bg-amber-400 h-1.5 rounded-full" style="width: {{ $dev['percentage'] }}%"></div>
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-slate-400">No device data available.</p>
                    @endforelse
                </div>
            </div>
        </div>

        <!-- Breakdown Grid 2: Top Browsers, Top OS, Top Locations -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <!-- Top Browsers -->
            <div class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-sm">
                <h3 class="text-sm font-semibold text-slate-500 dark:text-slate-400 mb-4">Top Browsers</h3>
                <div class="space-y-3">
                    @forelse ($top_browsers ?? [] as $browser)
                        <div>
                            <div class="flex justify-between text-xs font-medium mb-1">
                                <span class="truncate font-mono">{{ $browser['browser'] }}</span>
                                <span>{{ number_format($browser['count']) }} ({{ $browser['percentage'] }}%)</span>
                            </div>
                            <div class="w-full bg-slate-100 dark:bg-slate-800 h-1.5 rounded-full overflow-hidden">
                                <div class="bg-sky-600 dark:bg-sky-500 h-1.5 rounded-full" style="width: {{ $browser['percentage'] }}%"></div>
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-slate-400">No browser data available.</p>
                    @endforelse
                </div>
            </div>

            <!-- Top OS -->
            <div class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-sm">
                <h3 class="text-sm font-semibold text-slate-500 dark:text-slate-400 mb-4">Top Operating Systems</h3>
                <div class="space-y-3">
                    @forelse ($top_os ?? [] as $osItem)
                        <div>
                            <div class="flex justify-between text-xs font-medium mb-1">
                                <span class="truncate font-mono">{{ $osItem['os'] }}</span>
                                <span>{{ number_format($osItem['count']) }} ({{ $osItem['percentage'] }}%)</span>
                            </div>
                            <div class="w-full bg-slate-100 dark:bg-slate-800 h-1.5 rounded-full overflow-hidden">
                                <div class="bg-purple-600 dark:bg-purple-500 h-1.5 rounded-full" style="width: {{ $osItem['percentage'] }}%"></div>
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-slate-400">No OS data available.</p>
                    @endforelse
                </div>
            </div>

            <!-- Top Locations -->
            <div class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-sm">
                <h3 class="text-sm font-semibold text-slate-500 dark:text-slate-400 mb-4">Top Locations</h3>
                <div class="space-y-3">
                    @forelse ($top_countries ?? [] as $cItem)
                        <div>
                            <div class="flex justify-between text-xs font-medium mb-1">
                                <span class="truncate font-mono">
                                    @if(!empty($cItem['code']))
                                        <span class="text-[10px] font-bold px-1 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-500 uppercase mr-1">{{ $cItem['code'] }}</span>
                                    @endif
                                    {{ $cItem['name'] ?? $cItem['code'] }}
                                </span>
                                <span>{{ number_format($cItem['count']) }} ({{ $cItem['percentage'] }}%)</span>
                            </div>
                            <div class="w-full bg-slate-100 dark:bg-slate-800 h-1.5 rounded-full overflow-hidden">
                                <div class="bg-rose-600 dark:bg-rose-500 h-1.5 rounded-full" style="width: {{ $cItem['percentage'] }}%"></div>
                            </div>
                        </div>
                    @empty
                        <p class="text-xs text-slate-400">No location data available.</p>
                    @endforelse
                </div>
            </div>
        </div>

        @if (isset($goals) && $goals->count() > 0)
            <!-- Goals Performance -->
            <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                @foreach ($goals as $goal)
                    <div class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 shadow-sm flex flex-col h-full">
                        <div class="flex items-center justify-between mb-4">
                            <h3 class="text-sm font-bold text-slate-900 dark:text-slate-100 truncate" title="{{ $goal['name'] }}">{{ $goal['name'] }}</h3>
                            <span class="text-[10px] font-bold px-2 py-0.5 rounded-full bg-slate-100 dark:bg-slate-800 text-slate-500 uppercase">{{ $goal['target_type'] === 'path' ? 'Path' : 'Event' }}</span>
                        </div>

                        <div class="flex items-baseline justify-between mb-6">
                            <div class="text-3xl font-black tracking-tight text-slate-900 dark:text-slate-100">{{ number_format($goal['completions']) }}</div>
                            <div class="text-sm font-bold text-emerald-600 dark:text-emerald-400">{{ $goal['conversion_rate'] }}% CV</div>
                        </div>

                        <div class="mt-auto h-16 flex items-end gap-1 w-full relative">
                            @php
                                $maxTrend = count($goal['trend']) > 0 ? max(array_column($goal['trend'], 'completions')) : 1;
                                $maxTrend = max(1, $maxTrend);
                            @endphp
                            @foreach ($goal['trend'] as $day)
                                @php
                                    $heightPct = max(round(($day['completions'] / $maxTrend) * 100), 2);
                                @endphp
                                <div
                                    class="flex-1 rounded-t-sm bg-indigo-500/80 dark:bg-indigo-400/80 hover:bg-indigo-600 transition-colors min-h-[2px]"
                                    style="height: {{ $heightPct }}%"
                                    title="{{ $day['date'] }}: {{ $day['completions'] }}"
                                ></div>
                            @endforeach
                        </div>
                    </div>
                @endforeach
            </div>
        @endif

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
    @elseif ($activeTab === 'events')
        <!-- Header Controls -->
        <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 rounded-xl p-4 shadow-sm">
            <div class="flex items-center gap-2">
                <svg class="h-4 w-4 text-slate-500 dark:text-slate-400" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                <label for="event-filter" class="text-sm font-semibold text-slate-900 dark:text-slate-100">Filter by event</label>
            </div>
            <div>
                <select
                    id="event-filter"
                    wire:model.live="selectedEvent"
                    wire:change="selectEvent($event.target.value === '' ? null : $event.target.value)"
                    class="rounded-md border-0 py-1.5 pl-3 pr-8 text-xs font-semibold ring-1 ring-inset ring-slate-200 focus:ring-2 focus:ring-indigo-600 dark:bg-slate-900 dark:ring-slate-700 dark:text-slate-100 bg-white text-slate-900 min-w-[200px]"
                >
                    <option value="">All Custom Events</option>
                    @foreach ($custom_events_list ?? [] as $evt)
                        <option value="{{ $evt['name'] }}">{{ $evt['name'] }}</option>
                    @endforeach
                </select>
            </div>
        </div>

        @if (!isset($custom_event_summary) || empty($custom_event_summary['total_custom_events']))
            <div class="rounded-xl border border-dashed border-slate-300 dark:border-slate-700 p-8 text-center bg-slate-50/50 dark:bg-slate-900/50">
                <div class="mx-auto flex h-12 w-12 items-center justify-center rounded-full bg-indigo-50 dark:bg-indigo-950/50 text-indigo-600 dark:text-indigo-400">
                    <svg class="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                </div>
                <h3 class="mt-4 text-base font-semibold">No custom events tracked yet</h3>
                <p class="mt-1 text-sm text-slate-500 dark:text-slate-400 max-w-md mx-auto">
                    Use window.lumina('event_name', { props }) to start tracking custom actions.
                </p>
                <div class="mt-6 font-mono bg-slate-100 dark:bg-slate-950 p-4 rounded-lg border border-slate-200 dark:border-slate-800 text-xs text-left overflow-x-auto max-w-2xl mx-auto">
                    window.lumina('purchase', { plan: 'pro', amount: 29.99 });
                </div>
            </div>
        @else
            <!-- KPI Summary Cards -->
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                <div class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Total Custom Events</span>
                        <span class="inline-flex rounded-lg bg-indigo-50 dark:bg-indigo-950/50 p-2 text-indigo-600 dark:text-indigo-400">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg>
                        </span>
                    </div>
                    <div class="mt-2 text-3xl font-extrabold">{{ number_format($custom_event_summary['total_custom_events']) }}</div>
                </div>

                <div class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Unique Event Types</span>
                        <span class="inline-flex rounded-lg bg-emerald-50 dark:bg-emerald-950/50 p-2 text-emerald-600 dark:text-emerald-400">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-3 7h3m-3 4h3m-6-4h.01M9 16h.01"/></svg>
                        </span>
                    </div>
                    <div class="mt-2 text-3xl font-extrabold">{{ number_format($custom_event_summary['unique_event_names']) }}</div>
                </div>

                <div class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-5 shadow-sm">
                    <div class="flex items-center justify-between">
                        <span class="text-xs font-semibold uppercase tracking-wider text-slate-500 dark:text-slate-400">Most Frequent Event</span>
                        <span class="inline-flex rounded-lg bg-amber-50 dark:bg-amber-950/50 p-2 text-amber-500 dark:text-amber-400">
                            <svg class="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/></svg>
                        </span>
                    </div>
                    <div class="mt-2 text-2xl font-extrabold truncate font-mono">{{ $custom_event_summary['top_event_name'] ?: '-' }}</div>
                </div>
            </div>

            <!-- Custom Event Timeline -->
            <div class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 shadow-sm">
                <h3 class="text-sm font-semibold text-slate-900 dark:text-slate-100 mb-4">Event Frequency Over Time</h3>
                @php
                    $maxEvtDaily = max(collect($custom_event_timeline ?? [])->max('count') ?: 1, 1);
                @endphp
                <div class="flex items-end gap-1 h-36 pt-4">
                    @forelse ($custom_event_timeline ?? [] as $day)
                        @php
                            $heightPercent = round(($day['count'] / $maxEvtDaily) * 100);
                        @endphp
                        <div class="flex-1 flex flex-col items-center group relative h-full justify-end">
                            <div 
                                class="w-full bg-indigo-500 dark:bg-indigo-400 rounded-t transition-all group-hover:bg-indigo-600 min-h-[2px]" 
                                style="height: {{ max($heightPercent, 2) }}%;"
                            ></div>
                            <div class="absolute bottom-full mb-2 hidden group-hover:block z-10 rounded bg-slate-900 px-2 py-1 text-xs text-white shadow-lg whitespace-nowrap">
                                {{ $day['date'] }}: {{ number_format($day['count']) }} occurrences
                            </div>
                        </div>
                    @empty
                        <div class="w-full h-full flex items-center justify-center border-b border-slate-200 dark:border-slate-800">
                            <span class="text-xs text-slate-500">No events in this period</span>
                        </div>
                    @endforelse
                </div>
            </div>

            <!-- Breakdown Grid -->
            <div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
                <!-- Top Events List -->
                <div class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 shadow-sm">
                    <div class="flex items-center justify-between mb-4">
                        <h3 class="text-sm font-bold text-slate-900 dark:text-slate-100">Top Custom Events</h3>
                        <span class="text-xs text-slate-500 dark:text-slate-400">{{ count($custom_events_list ?? []) }} events</span>
                    </div>
                    <div class="space-y-3 max-h-[400px] overflow-y-auto pr-2">
                        @foreach ($custom_events_list ?? [] as $evt)
                            <div
                                wire:click="selectEvent('{{ $evt['name'] }}')"
                                class="space-y-1.5 p-2 rounded-lg cursor-pointer transition-all border {{ $selectedEvent === $evt['name'] ? 'border-indigo-500 bg-indigo-50 dark:bg-indigo-900/20' : 'border-transparent hover:bg-slate-50 dark:hover:bg-slate-800/50' }}"
                            >
                                <div class="flex justify-between items-center text-xs">
                                    <span class="font-mono font-semibold text-indigo-600 dark:text-indigo-400">{{ $evt['name'] }}</span>
                                    <span class="text-slate-500 font-mono">{{ number_format($evt['count']) }} ({{ $evt['percentage'] }}%)</span>
                                </div>
                                <div class="w-full bg-slate-100 dark:bg-slate-800 h-1.5 rounded-full overflow-hidden">
                                    <div class="bg-indigo-600 dark:bg-indigo-500 h-1.5 rounded-full" style="width: {{ $evt['percentage'] }}%"></div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Property Breakdown -->
                <div class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 p-6 shadow-sm">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-slate-100 mb-4">Property Value Breakdown</h3>
                    
                    @if (!$selectedEvent)
                        <p class="text-sm text-slate-500">Select an event from the list to inspect its properties.</p>
                    @elseif (empty($custom_event_property_keys))
                        <p class="text-sm text-slate-500">No metadata properties recorded for <span class="font-mono text-indigo-500">{{ $selectedEvent }}</span>.</p>
                    @else
                        <div class="space-y-6">
                            <div>
                                <label class="text-xs font-semibold text-slate-500 mb-2 block">Select metadata key:</label>
                                <div class="flex flex-wrap gap-2">
                                    @foreach ($custom_event_property_keys as $key)
                                        <button
                                            wire:click="selectPropertyKey('{{ $key }}')"
                                            class="px-2.5 py-1 text-xs font-mono font-semibold rounded-md border transition-all {{ $selectedPropertyKey === $key ? 'bg-sky-600 border-sky-600 text-white dark:bg-sky-500 dark:border-sky-500' : 'bg-white dark:bg-slate-900 border-slate-200 dark:border-slate-700 text-slate-900 dark:text-slate-100 hover:border-sky-500' }}"
                                        >
                                            {{ $key }}
                                        </button>
                                    @endforeach
                                </div>
                            </div>

                            @if ($selectedPropertyKey && !empty($custom_event_property_breakdown))
                                <div class="space-y-3">
                                    @foreach ($custom_event_property_breakdown as $prop)
                                        <div class="space-y-1.5">
                                            <div class="flex justify-between items-center text-xs">
                                                <span class="font-mono text-slate-900 dark:text-slate-100 truncate pr-4 max-w-[200px]">{{ $prop['value'] }}</span>
                                                <span class="text-slate-500 font-mono shrink-0">{{ number_format($prop['count']) }} ({{ $prop['percentage'] }}%)</span>
                                            </div>
                                            <div class="w-full bg-slate-100 dark:bg-slate-800 h-1.5 rounded-full overflow-hidden">
                                                <div class="bg-sky-600 dark:bg-sky-500 h-1.5 rounded-full" style="width: {{ $prop['percentage'] }}%"></div>
                                            </div>
                                        </div>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            <!-- Recent Event Logs -->
            <div class="rounded-xl border border-slate-200 dark:border-slate-800 bg-white dark:bg-slate-900 shadow-sm overflow-hidden flex flex-col">
                <div class="p-6 pb-4 border-b border-slate-200 dark:border-slate-800">
                    <h3 class="text-sm font-bold text-slate-900 dark:text-slate-100">Recent Custom Event Logs</h3>
                </div>
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-sm whitespace-nowrap">
                        <thead class="text-xs uppercase text-slate-500 bg-slate-50 dark:bg-slate-800/50">
                            <tr>
                                <th class="px-6 py-3 font-semibold">Timestamp</th>
                                <th class="px-6 py-3 font-semibold">Event Name</th>
                                <th class="px-6 py-3 font-semibold">Path</th>
                                <th class="px-6 py-3 font-semibold">Visitor</th>
                                <th class="px-6 py-3 font-semibold">Action</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-200 dark:divide-slate-800" x-data="{ expandedLogs: [] }">
                            @forelse ($custom_event_logs ?? [] as $log)
                                <tr class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition-colors">
                                    <td class="px-6 py-3 font-mono text-[11px] text-slate-500">{{ $log['created_at'] }}</td>
                                    <td class="px-6 py-3">
                                        <span class="font-mono text-xs font-semibold text-indigo-600 dark:text-indigo-400">{{ $log['event_name'] }}</span>
                                    </td>
                                    <td class="px-6 py-3 text-xs truncate max-w-[150px]">{{ $log['path'] ?: '—' }}</td>
                                    <td class="px-6 py-3 text-xs font-mono text-slate-500">{{ $log['visitor_hash'] ? substr($log['visitor_hash'], 0, 8) : '—' }}</td>
                                    <td class="px-6 py-3">
                                        <button 
                                            @click="expandedLogs.includes({{ $log['id'] }}) ? expandedLogs = expandedLogs.filter(id => id !== {{ $log['id'] }}) : expandedLogs.push({{ $log['id'] }})" 
                                            class="text-xs text-indigo-600 dark:text-indigo-400 font-semibold hover:underline flex items-center gap-1"
                                        >
                                            View Raw Payload
                                        </button>
                                    </td>
                                </tr>
                                <tr x-show="expandedLogs.includes({{ $log['id'] }})" class="bg-slate-50 dark:bg-slate-800/30">
                                    <td colspan="5" class="px-6 py-4">
                                        <div class="font-mono bg-slate-100 dark:bg-slate-950 p-4 rounded-lg border border-slate-200 dark:border-slate-800 text-xs text-slate-800 dark:text-slate-200 overflow-x-auto">
                                            <pre>{{ json_encode($log['props'], JSON_PRETTY_PRINT) }}</pre>
                                        </div>
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="5" class="px-6 py-8 text-center text-slate-500 text-xs">
                                        No recent events
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        @endif
    @endif
</div>
