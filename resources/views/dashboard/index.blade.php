@extends('stream-pulse::layouts.app')

@section('content')
    <div>
        <!-- Dashboard Header -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
            <div>
                <h1 class="text-2xl font-semibold text-white mb-1">Stream Manager Dashboard</h1>
                <p class="text-gray-400 text-sm">Monitor your Redis streams and events in real-time</p>
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
    </div>
@endsection
