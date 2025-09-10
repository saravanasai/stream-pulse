@extends('stream-pulse::layouts.app')

@section('content')
    <div>
        <!-- Dashboard Header -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
            <div>
                <h1 class="text-2xl font-semibold text-white mb-1">Stream Manager Dashboard</h1>
                <p class="text-gray-400 text-sm">Monitor your Redis streams and events in real-time</p>
            </div>
            <div class="mt-4 md:mt-0 flex space-x-3">
                <div x-data="{ autoRefresh: false }" class="flex items-center">
                    <button @click="autoRefresh = !autoRefresh"
                        class="inline-flex items-center px-3 py-1.5 text-sm font-medium rounded-md"
                        :class="autoRefresh ? 'bg-redis-red text-white' : 'bg-gray-800 text-gray-300 border border-gray-700'">
                        <i class="fa-solid fa-clock mr-1.5" :class="autoRefresh ? 'animate-pulse' : ''"></i>
                        <span x-text="autoRefresh ? 'Auto-refresh: On' : 'Auto-refresh: Off'">Auto-refresh: Off</span>
                    </button>
                </div>
                <button
                    class="inline-flex items-center px-3 py-1.5 text-sm font-medium rounded-md bg-gray-800 hover:bg-gray-700 text-gray-300 border border-gray-700">
                    <i class="fa-solid fa-rotate mr-1.5"></i> Refresh Now
                </button>
            </div>
        </div>

        <!-- Status Overview -->
        <div class="bg-gray-900 border border-gray-800 rounded-lg p-4 mb-6">
            <div class="flex items-center mb-3">
                <div class="w-4 h-4 rounded-full bg-green-500 mr-2"></div>
                <span class="text-gray-300 text-sm">All systems operational</span>
                <span class="ml-auto text-gray-400 text-xs">Last checked: {{ now()->format('H:i:s') }}</span>
            </div>
        </div>

        <!-- Stats Cards -->
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6 mb-8">
            <div
                class="bg-gray-900 border border-gray-800 rounded-lg overflow-hidden shadow-md transition-all hover:shadow-lg hover:scale-[1.02]">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 rounded-md p-3 bg-blue-500 bg-opacity-10">
                            <i class="fa-solid fa-layer-group text-blue-400 text-xl"></i>
                        </div>
                        <div class="ml-5">
                            <p class="text-sm font-medium text-gray-400">Total Topics</p>
                            <div class="flex items-baseline">
                                <p class="text-2xl font-semibold text-white">{{ count($topics) }}</p>
                                <span class="ml-2 text-xs text-green-400">+2 new</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-800 px-5 py-3">
                    <a href="#topics" class="text-sm text-blue-400 hover:text-blue-300 flex items-center">
                        <span>View all topics</span>
                        <i class="fa-solid fa-arrow-right ml-1 text-xs"></i>
                    </a>
                </div>
            </div>

            <div
                class="bg-gray-900 border border-gray-800 rounded-lg overflow-hidden shadow-md transition-all hover:shadow-lg hover:scale-[1.02]">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 rounded-md p-3 bg-green-500 bg-opacity-10">
                            <i class="fa-solid fa-bolt-lightning text-green-400 text-xl"></i>
                        </div>
                        <div class="ml-5">
                            <p class="text-sm font-medium text-gray-400">Total Events</p>
                            <div class="flex items-baseline">
                                <p class="text-2xl font-semibold text-white">
                                    <?php
                                    $totalEvents = 0;
                                    foreach ($topics as $topicName) {
                                        $events = app(\StreamPulse\StreamPulse\Contracts\StreamUIInterface::class)->getEventsByTopic($topicName, 1000, 0);
                                        $totalEvents += count($events);
                                    }
                                    echo number_format($totalEvents);
                                    ?>
                                </p>
                                <span class="ml-2 text-xs text-green-400">↑ 24%</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-800 px-5 py-3">
                    <span class="text-sm text-gray-400">Across all streams</span>
                </div>
            </div>

            <div
                class="bg-gray-900 border border-gray-800 rounded-lg overflow-hidden shadow-md transition-all hover:shadow-lg hover:scale-[1.02]">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 rounded-md p-3 bg-yellow-500 bg-opacity-10">
                            <i class="fa-solid fa-gauge text-yellow-400 text-xl"></i>
                        </div>
                        <div class="ml-5">
                            <p class="text-sm font-medium text-gray-400">Processing Rate</p>
                            <div class="flex items-baseline">
                                <p class="text-2xl font-semibold text-white">42.8</p>
                                <span class="ml-2 text-xs text-gray-400">events/min</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-800 px-5 py-3">
                    <div class="w-full bg-gray-700 rounded-full h-1.5">
                        <div class="bg-yellow-500 h-1.5 rounded-full" style="width: 45%"></div>
                    </div>
                </div>
            </div>

            <div
                class="bg-gray-900 border border-gray-800 rounded-lg overflow-hidden shadow-md transition-all hover:shadow-lg hover:scale-[1.02]">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 rounded-md p-3 bg-red-500 bg-opacity-10">
                            <i class="fa-solid fa-triangle-exclamation text-red-400 text-xl"></i>
                        </div>
                        <div class="ml-5">
                            <p class="text-sm font-medium text-gray-400">Failed Events</p>
                            <div class="flex items-baseline">
                                <p class="text-2xl font-semibold text-white">{{ count($failedEvents) }}</p>
                                @if (count($failedEvents) > 0)
                                    <span class="ml-2 text-xs text-red-400">Needs attention</span>
                                @else
                                    <span class="ml-2 text-xs text-green-400">All clear</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-800 px-5 py-3">
                    <a href="{{ route('stream-pulse.failed') }}"
                        class="text-sm text-red-400 hover:text-red-300 flex items-center">
                        <span>View failed events</span>
                        <i class="fa-solid fa-arrow-right ml-1 text-xs"></i>
                    </a>
                </div>
            </div>
        </div>

        <!-- Event Activity -->
        <div class="bg-gray-900 border border-gray-800 rounded-lg overflow-hidden mb-8">
            <div class="px-4 py-5 border-b border-gray-800 sm:px-6 flex justify-between items-center">
                <h3 class="text-lg font-medium text-white flex items-center">
                    <i class="fa-solid fa-chart-line text-redis-red mr-2"></i>
                    Event Activity
                </h3>
                <div class="flex space-x-2">
                    <button
                        class="text-xs bg-gray-800 hover:bg-gray-700 text-gray-400 px-2 py-1 rounded-md border border-gray-700">1H</button>
                    <button class="text-xs bg-redis-red text-white px-2 py-1 rounded-md">24H</button>
                    <button
                        class="text-xs bg-gray-800 hover:bg-gray-700 text-gray-400 px-2 py-1 rounded-md border border-gray-700">7D</button>
                </div>
            </div>
            <div class="p-4">
                <div class="h-48 flex items-center justify-center">
                    <!-- This would be a chart in a real implementation -->
                    <div class="text-center">
                        <div class="text-gray-400 mb-2">
                            <i class="fa-solid fa-chart-bar text-3xl"></i>
                        </div>
                        <p class="text-gray-400 text-sm">Event activity visualization would appear here</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Topics List -->
        <div id="topics" class="bg-gray-900 border border-gray-800 rounded-lg overflow-hidden mb-8">
            <div class="px-4 py-5 border-b border-gray-800 sm:px-6 flex justify-between items-center">
                <h3 class="text-lg font-medium text-white flex items-center">
                    <i class="fa-solid fa-stream text-redis-red mr-2"></i>
                    Topics & Streams
                </h3>
                <span
                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-900 text-blue-300 border border-blue-700">
                    {{ count($topics) }} topics
                </span>
            </div>
            @if (count($topics) > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead class="bg-gray-800">
                            <tr>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                                    Topic Name
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                                    Events Count
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                                    Consumer Lag
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                                    Last Event
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-right text-xs font-medium text-gray-400 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-800">
                            @foreach ($topics as $topic)
                                @php
                                    $events = app(
                                        \StreamPulse\StreamPulse\Contracts\StreamUIInterface::class,
                                    )->getEventsByTopic($topic, 10, 0);
                                    $lastEvent = !empty($events) ? $events[0] : null;
                                @endphp
                                <tr class="hover:bg-gray-800 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <i class="fa-solid fa-stream text-redis-red mr-2 opacity-80"></i>
                                            <div class="text-sm font-medium text-white">
                                                {{ $topic }}
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm text-gray-300">
                                            <span class="font-medium">{{ count($events) }}+</span> events
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span
                                            class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-900 text-green-300 border border-green-700">
                                            No lag
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                                        @if ($lastEvent)
                                            {{ date('Y-m-d H:i:s', $lastEvent['timestamp'] / 1000) }}
                                        @else
                                            -
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <a href="{{ route('stream-pulse.topic', $topic) }}"
                                            class="text-redis-red hover:text-redis-dark transition-colors">
                                            View
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="py-16 flex flex-col items-center justify-center text-gray-400">
                    <i class="fa-solid fa-database text-6xl mb-4 text-gray-700"></i>
                    <p class="text-xl">No topics available</p>
                    <p class="mt-2 text-sm">Start publishing events to see them here</p>
                </div>
            @endif
        </div>

        <!-- Recent Failed Events -->
        <div class="bg-gray-900 border border-gray-800 rounded-lg overflow-hidden">
            <div class="px-4 py-5 border-b border-gray-800 sm:px-6 flex justify-between items-center">
                <h3 class="text-lg font-medium text-white flex items-center">
                    <i class="fa-solid fa-triangle-exclamation text-redis-red mr-2"></i>
                    Recent Failed Events
                </h3>
                <a href="{{ route('stream-pulse.failed') }}"
                    class="text-sm font-medium text-redis-red hover:text-red-300 transition-colors">
                    View All
                </a>
            </div>
            @if (count($failedEvents) > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead class="bg-gray-800">
                            <tr>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                                    Topic
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                                    Event ID
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                                    Timestamp
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-right text-xs font-medium text-gray-400 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-800">
                            @foreach (array_slice($failedEvents, 0, 5) as $event)
                                <tr class="hover:bg-gray-800 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <i class="fa-solid fa-exclamation-circle text-redis-red mr-2"></i>
                                            <div class="text-sm font-medium text-white">
                                                {{ $event['topic'] }}
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-mono text-gray-300">
                                            {{ $event['event_id'] }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                                        {{ date('Y-m-d H:i:s', $event['timestamp'] / 1000) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <a href="{{ route('stream-pulse.event', [$event['topic'], $event['event_id']]) }}"
                                            class="text-redis-red hover:text-red-300 transition-colors">
                                            View Details
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            @else
                <div class="py-10 flex flex-col items-center justify-center text-gray-400">
                    <i class="fa-solid fa-check-circle text-5xl mb-4 text-green-400"></i>
                    <p class="text-xl">No failed events</p>
                    <p class="mt-2 text-sm">All events are processing correctly</p>
                </div>
            @endif
        </div>
    </div>
@endsection
