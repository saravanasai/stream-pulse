@extends('stream-pulse::layouts.app-new')

@section('content')
    <div>
        <!-- Dashboard Header -->
        @include('stream-pulse::components.dashboard.header', [
            'title' => 'Stream Manager Dashboard',
            'subtitle' => 'Monitor your Redis streams and events in real-time',
            'showAutoRefresh' => true,
        ])

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            @include('stream-pulse::components.cards.stat-card', [
                'color' => 'blue',
                'icon' => 'layer-group',
                'title' => 'Total Topics',
                'value' => count($topics),
                'trend' => '+2 new',
                'footerLink' => '#topics',
                'footerText' => 'View all topics',
            ])

            @include('stream-pulse::components.cards.stat-card', [
                'color' => 'green',
                'icon' => 'bolt-lightning',
                'title' => 'Total Events',
                'value' => number_format($totalEvents ?? 0),
                'trend' => '↑ 24%',
                'footer' => 'Across all streams',
            ])

            @include('stream-pulse::components.cards.stat-card', [
                'color' => 'yellow',
                'icon' => 'gauge',
                'title' => 'Processing Rate',
                'value' => '42.8',
                'footer' => 'events/min',
                'progressValue' => 45,
            ])

            @include('stream-pulse::components.cards.stat-card', [
                'color' => 'red',
                'icon' => 'triangle-exclamation',
                'title' => 'Failed Events',
                'value' => count($failedEvents),
                'trend' => count($failedEvents) > 0 ? 'Needs attention' : 'All clear',
                'trendColor' => count($failedEvents) > 0 ? 'red' : 'green',
                'footerLink' => route('stream-pulse.failed'),
                'footerText' => 'View failed events',
            ])
        </div>

        <!-- Event Activity -->
        <div class="bg-gray-900 border border-gray-800 rounded-lg overflow-hidden mb-8">
            @component('stream-pulse::components.ui.section-header', ['title' => 'Event Activity', 'icon' => 'chart-line'])
                <div class="flex space-x-2 items-center">
                    <button class="text-xs bg-gray-800 hover:bg-gray-700 text-gray-300 py-1 px-2 rounded">
                        Last 1h
                    </button>
                    <button class="text-xs bg-gray-800 hover:bg-gray-700 text-gray-300 py-1 px-2 rounded">
                        Last 24h
                    </button>
                    <button class="text-xs bg-gray-800 hover:bg-gray-700 text-gray-300 py-1 px-2 rounded">
                        Last 7d
                    </button>
                </div>
            @endcomponent

            <div class="p-4">
                <!-- Content from the original view -->
                <div class="text-center text-gray-500 py-6">
                    <i class="fa-solid fa-chart-line text-3xl mb-2 opacity-25"></i>
                    <p>No activity data available for selected timeframe</p>
                </div>
            </div>
        </div>

        <!-- Recent Events -->
        <div class="bg-gray-900 border border-gray-800 rounded-lg overflow-hidden mb-8">
            @component('stream-pulse::components.ui.section-header', ['title' => 'Recent Events', 'icon' => 'bolt'])
                <div class="flex items-center">
                    <select
                        class="bg-gray-800 border-gray-700 text-gray-300 text-xs rounded-md focus:ring-redis-red focus:border-redis-red">
                        <option>All Topics</option>
                        @foreach ($topics as $topic)
                            <option>{{ $topic }}</option>
                        @endforeach
                    </select>
                </div>
            @endcomponent

            <div class="overflow-x-auto">
                <!-- Content from the original view -->
                <table class="min-w-full divide-y divide-gray-800">
                    <thead class="bg-gray-800">
                        <tr>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                                ID
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                                Topic
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                                Timestamp
                            </th>
                            <th scope="col"
                                class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                                Data
                            </th>
                            <th scope="col" class="relative px-6 py-3">
                                <span class="sr-only">Actions</span>
                            </th>
                        </tr>
                    </thead>
                    <tbody class="bg-gray-900 divide-y divide-gray-800">
                        @forelse($events ?? [] as $event)
                            <!-- Event rows from original view -->
                        @empty
                            <tr>
                                <td colspan="5" class="px-6 py-4 text-center text-gray-500">
                                    No events found
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>
    </div>
@endsection
