@extends('stream-pulse::layouts.app')

@section('content')
    <div>
        <!-- Page Header -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
            <div>
                <h1 class="text-2xl font-semibold text-white mb-1 flex items-center">
                    <i class="fa-solid fa-stream text-redis-red mr-3"></i>
                    Topic: {{ $topic }}
                </h1>
                <p class="text-gray-400 text-sm">Manage and monitor the events in this stream</p>
            </div>
            <div class="mt-4 md:mt-0 flex space-x-3">
                <a href="{{ route('stream-pulse.dashboard') }}"
                    class="inline-flex items-center px-4 py-2 border border-gray-700 rounded-md text-sm font-medium text-gray-300 bg-gray-800 hover:bg-gray-700 transition-colors">
                    <i class="fa-solid fa-arrow-left mr-2"></i>
                    Back to Dashboard
                </a>
                <button
                    class="inline-flex items-center px-4 py-2 border border-transparent rounded-md text-sm font-medium text-white bg-redis-red hover:bg-redis-dark transition-colors">
                    <i class="fa-solid fa-rotate mr-2"></i>
                    Refresh
                </button>
            </div>
        </div>

        <!-- Topic Overview -->
        <div class="bg-gray-900 border border-gray-800 rounded-lg p-4 mb-6">
            <div class="flex flex-col md:flex-row md:items-center justify-between">
                <div class="flex items-center mb-3 md:mb-0">
                    <div class="w-4 h-4 rounded-full bg-green-500 mr-2"></div>
                    <span class="text-gray-300 text-sm">Stream active</span>
                </div>
                <div class="flex items-center space-x-4">
                    <div class="flex items-center text-gray-400 text-sm">
                        <i class="fa-solid fa-keyboard mr-1"></i>
                        <span>{{ $topic }}</span>
                    </div>
                    <div class="flex items-center text-gray-400 text-sm">
                        <i class="fa-solid fa-clock mr-1"></i>
                        <span>Last updated: {{ now()->format('H:i:s') }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Topic Stats -->
        <div class="grid grid-cols-1 md:grid-cols-3 gap-6 mb-8">
            <div
                class="bg-gray-900 border border-gray-800 rounded-lg overflow-hidden shadow-md transition-all hover:shadow-lg hover:scale-[1.02]">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 rounded-md p-3 bg-blue-500 bg-opacity-10">
                            <i class="fa-solid fa-bolt-lightning text-blue-400 text-xl"></i>
                        </div>
                        <div class="ml-5">
                            <p class="text-sm font-medium text-gray-400">Event Count</p>
                            <div class="flex items-baseline">
                                <p class="text-2xl font-semibold text-white">{{ count($events) }}+</p>
                                <span class="ml-2 text-xs text-blue-400">total</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-800 px-4 py-2">
                    <div class="h-1 w-full bg-gray-700 rounded-full overflow-hidden">
                        <div class="bg-blue-500 h-1 rounded-full" style="width: 75%"></div>
                    </div>
                </div>
            </div>

            <div
                class="bg-gray-900 border border-gray-800 rounded-lg overflow-hidden shadow-md transition-all hover:shadow-lg hover:scale-[1.02]">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 rounded-md p-3 bg-green-500 bg-opacity-10">
                            <i class="fa-solid fa-gauge text-green-400 text-xl"></i>
                        </div>
                        <div class="ml-5">
                            <p class="text-sm font-medium text-gray-400">Consumer Lag</p>
                            <div class="flex items-baseline">
                                <p class="text-2xl font-semibold text-white">0</p>
                                <span class="ml-2 text-xs text-green-400">events</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-800 px-4 py-2">
                    <div class="h-1 w-full bg-gray-700 rounded-full overflow-hidden">
                        <div class="bg-green-500 h-1 rounded-full" style="width: 5%"></div>
                    </div>
                </div>
            </div>

            <div
                class="bg-gray-900 border border-gray-800 rounded-lg overflow-hidden shadow-md transition-all hover:shadow-lg hover:scale-[1.02]">
                <div class="p-5">
                    <div class="flex items-center">
                        <div class="flex-shrink-0 rounded-md p-3 bg-yellow-500 bg-opacity-10">
                            <i class="fa-solid fa-clock text-yellow-400 text-xl"></i>
                        </div>
                        <div class="ml-5">
                            <p class="text-sm font-medium text-gray-400">Last Event</p>
                            <div class="flex items-baseline">
                                <p class="text-2xl font-semibold text-white">
                                    @if (count($events) > 0)
                                        {{ date('H:i:s', $events[0]['timestamp'] / 1000) }}
                                    @else
                                        -
                                    @endif
                                </p>
                                @if (count($events) > 0)
                                    <span
                                        class="ml-2 text-xs text-yellow-400">{{ date('Y-m-d', $events[0]['timestamp'] / 1000) }}</span>
                                @endif
                            </div>
                        </div>
                    </div>
                </div>
                <div class="bg-gray-800 px-4 py-2">
                    <div class="flex justify-between items-center text-xs text-gray-500">
                        <span>Frequency</span>
                        <span>8 events/min</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Controls Row -->
        <div class="flex flex-col md:flex-row gap-4 mb-6">
            <div class="w-full md:w-1/2 bg-gray-900 border border-gray-800 rounded-lg p-4">
                <div class="flex justify-between items-center mb-3">
                    <h3 class="text-sm font-medium text-white">Event Filters</h3>
                    <button class="text-xs text-gray-400 hover:text-white transition-colors">
                        <i class="fa-solid fa-sliders mr-1"></i> Advanced
                    </button>
                </div>
                <div class="flex space-x-2">
                    <div class="relative flex-1">
                        <input type="text" placeholder="Search events..."
                            class="w-full bg-gray-800 border border-gray-700 rounded-md py-2 px-3 text-sm text-gray-300 placeholder-gray-500 focus:outline-none focus:ring-1 focus:ring-redis-red">
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                            <i class="fa-solid fa-search text-gray-500"></i>
                        </div>
                    </div>
                    <button
                        class="bg-redis-red hover:bg-redis-dark text-white py-2 px-4 rounded-md text-sm transition-colors">
                        Filter
                    </button>
                </div>
            </div>
            <div class="w-full md:w-1/2 bg-gray-900 border border-gray-800 rounded-lg p-4">
                <div class="flex justify-between items-center mb-3">
                    <h3 class="text-sm font-medium text-white">Event Operations</h3>
                    <button class="text-xs text-gray-400 hover:text-white transition-colors">
                        <i class="fa-solid fa-cog mr-1"></i> Settings
                    </button>
                </div>
                <div class="flex space-x-2">
                    <button
                        class="bg-gray-800 hover:bg-gray-700 text-gray-300 border border-gray-700 py-2 px-3 rounded-md text-sm flex-1 transition-colors">
                        <i class="fa-solid fa-plus mr-1"></i> Add Event
                    </button>
                    <button
                        class="bg-gray-800 hover:bg-gray-700 text-gray-300 border border-gray-700 py-2 px-3 rounded-md text-sm flex-1 transition-colors">
                        <i class="fa-solid fa-trash-can mr-1"></i> Purge Stream
                    </button>
                    <button
                        class="bg-gray-800 hover:bg-gray-700 text-gray-300 border border-gray-700 py-2 px-3 rounded-md text-sm flex-1 transition-colors">
                        <i class="fa-solid fa-file-export mr-1"></i> Export
                    </button>
                </div>
            </div>
        </div>

        <!-- Events List -->
        <div class="bg-gray-900 border border-gray-800 rounded-lg overflow-hidden">
            <div class="px-4 py-5 border-b border-gray-800 sm:px-6 flex justify-between items-center">
                <h3 class="text-lg font-medium text-white flex items-center">
                    <i class="fa-solid fa-list mr-2 text-redis-red"></i>
                    Events
                </h3>
                <span
                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-blue-900 text-blue-300 border border-blue-700">
                    {{ count($events) }} loaded
                </span>
            </div>

            @if (count($events) > 0)
                <div class="overflow-x-auto">
                    <table class="min-w-full">
                        <thead class="bg-gray-800">
                            <tr>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                                    Event ID
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                                    Timestamp
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                                    Status
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                                    Payload Preview
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-right text-xs font-medium text-gray-400 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-800">
                            @foreach ($events as $event)
                                <tr class="hover:bg-gray-800 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-mono text-gray-300 truncate max-w-[200px]">
                                            {{ $event['event_id'] }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                                        {{ date('Y-m-d H:i:s', $event['timestamp'] / 1000) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        @if (isset($event['is_failed']) && $event['is_failed'])
                                            <span
                                                class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-900 text-red-300 border border-red-700">
                                                Failed
                                            </span>
                                        @else
                                            <span
                                                class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-green-900 text-green-300 border border-green-700">
                                                Processed
                                            </span>
                                        @endif
                                    </td>
                                    <td class="px-6 py-4 text-sm text-gray-300">
                                        <div class="truncate max-w-[200px]">
                                            {{ json_encode($event['payload']) }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <a href="{{ route('stream-pulse.event', [$topic, $event['event_id']]) }}"
                                            class="text-redis-red hover:text-redis-dark transition-colors">
                                            View Details
                                        </a>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="bg-gray-800 px-4 py-3 border-t border-gray-700 flex items-center justify-between">
                    <div class="text-sm text-gray-400">
                        Showing <span class="font-medium">{{ count($events) }}</span> events
                    </div>
                    <div class="flex space-x-2">
                        @if ($offset > 0)
                            <a href="{{ route('stream-pulse.topic', [$topic, 'offset' => max(0, $offset - $limit)]) }}"
                                class="inline-flex items-center px-4 py-2 border border-gray-700 rounded-md text-sm font-medium text-gray-300 bg-gray-800 hover:bg-gray-700 transition-colors">
                                <i class="fa-solid fa-chevron-left mr-2"></i>
                                Previous
                            </a>
                        @else
                            <button disabled
                                class="inline-flex items-center px-4 py-2 border border-gray-700 rounded-md text-sm font-medium text-gray-400 bg-gray-800 cursor-not-allowed">
                                <i class="fa-solid fa-chevron-left mr-2"></i>
                                Previous
                            </button>
                        @endif

                        @if (count($events) >= $limit)
                            <a href="{{ route('stream-pulse.topic', [$topic, 'offset' => $offset + $limit]) }}"
                                class="inline-flex items-center px-4 py-2 border border-transparent rounded-md text-sm font-medium text-white bg-redis-red hover:bg-redis-dark transition-colors">
                                Next
                                <i class="fa-solid fa-chevron-right ml-2"></i>
                            </a>
                        @else
                            <button disabled
                                class="inline-flex items-center px-4 py-2 border border-gray-700 rounded-md text-sm font-medium text-gray-400 bg-gray-800 cursor-not-allowed">
                                Next
                                <i class="fa-solid fa-chevron-right ml-2"></i>
                            </button>
                        @endif
                    </div>
                </div>
            @else
                <div class="py-16 flex flex-col items-center justify-center text-gray-400">
                    <div class="rounded-full bg-gray-800 p-5 mb-4">
                        <i class="fa-solid fa-inbox text-5xl text-gray-600"></i>
                    </div>
                    <p class="text-xl font-medium text-white">No events found</p>
                    <p class="mt-2 text-sm">This topic doesn't have any events yet</p>
                </div>
            @endif
        </div>
    </div>
@endsection
