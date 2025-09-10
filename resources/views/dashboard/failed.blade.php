@extends('stream-pulse::layouts.app')

@section('content')
    <div>
        <!-- Page Header -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
            <div>
                <h1 class="text-2xl font-semibold text-white mb-1 flex items-center">
                    <i class="fa-solid fa-triangle-exclamation text-redis-red mr-3"></i>
                    Failed Events
                </h1>
                <p class="text-gray-400 text-sm">Events that encountered errors during processing</p>
            </div>
            <div class="mt-4 md:mt-0 flex space-x-3">
                <a href="{{ route('stream-pulse.dashboard') }}"
                    class="inline-flex items-center px-4 py-2 border border-gray-700 rounded-md text-sm font-medium text-gray-300 bg-gray-800 hover:bg-gray-700 transition-colors">
                    <i class="fa-solid fa-arrow-left mr-2"></i>
                    Back to Dashboard
                </a>
                <button
                    class="inline-flex items-center px-4 py-2 border border-gray-700 rounded-md text-sm font-medium text-gray-300 bg-gray-800 hover:bg-gray-700 transition-colors">
                    <i class="fa-solid fa-filter mr-2"></i>
                    Filter
                </button>
            </div>
        </div>

        <!-- Status Summary -->
        <div class="bg-gray-900 border border-gray-800 rounded-lg p-4 mb-6">
            <div class="flex items-center">
                <div class="w-4 h-4 rounded-full bg-red-500 mr-2"></div>
                <span class="text-gray-300 text-sm">{{ count($failedEvents) }} failed events require attention</span>
                <span class="ml-auto text-gray-400 text-xs">Last checked: {{ now()->format('H:i:s') }}</span>
            </div>
        </div>

        <!-- Failed Events List -->
        <div class="bg-gray-900 border border-gray-800 rounded-lg overflow-hidden">
            <div class="px-4 py-5 border-b border-gray-800 sm:px-6 flex justify-between items-center">
                <h3 class="text-lg font-medium text-white flex items-center">
                    <i class="fa-solid fa-triangle-exclamation text-redis-red mr-2"></i>
                    All Failed Events
                </h3>
                <span
                    class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-900 text-red-300 border border-red-700">
                    {{ count($failedEvents) }} events
                </span>
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
                                    class="px-6 py-3 text-left text-xs font-medium text-gray-400 uppercase tracking-wider">
                                    Status
                                </th>
                                <th scope="col"
                                    class="px-6 py-3 text-right text-xs font-medium text-gray-400 uppercase tracking-wider">
                                    Actions
                                </th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-gray-800">
                            @foreach ($failedEvents as $event)
                                <tr class="hover:bg-gray-800 transition-colors">
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="flex items-center">
                                            <i class="fa-solid fa-stream text-redis-red mr-2 opacity-80"></i>
                                            <div class="text-sm font-medium text-white">
                                                {{ $event['topic'] }}
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <div class="text-sm font-mono text-gray-300 truncate max-w-xs">
                                            {{ $event['event_id'] }}
                                        </div>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-300">
                                        {{ date('Y-m-d H:i:s', $event['timestamp'] / 1000) }}
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap">
                                        <span
                                            class="px-2 inline-flex text-xs leading-5 font-semibold rounded-full bg-red-900 text-red-300 border border-red-700">
                                            Failed
                                        </span>
                                    </td>
                                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                                        <div class="flex items-center justify-end space-x-2">
                                            <button class="text-yellow-500 hover:text-yellow-400 transition-colors">
                                                <i class="fa-solid fa-rotate"></i>
                                            </button>
                                            <a href="{{ route('stream-pulse.event', [$event['topic'], $event['event_id']]) }}"
                                                class="text-redis-red hover:text-redis-dark transition-colors">
                                                View
                                            </a>
                                        </div>
                                    </td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <div class="bg-gray-800 px-4 py-3 border-t border-gray-700 flex items-center justify-between">
                    <div class="text-sm text-gray-400">
                        Showing <span class="font-medium">{{ count($failedEvents) }}</span> events
                    </div>
                    <div class="flex-1 flex justify-end">
                        <button
                            class="px-4 py-2 border border-gray-700 rounded-md text-sm font-medium text-redis-red bg-gray-900 hover:bg-gray-800 ml-3">
                            <i class="fa-solid fa-trash-can mr-1"></i> Clear All Failed
                        </button>
                    </div>
                </div>
            @else
                <div class="py-16 flex flex-col items-center justify-center text-gray-400">
                    <div class="rounded-full bg-green-900 bg-opacity-20 p-5 mb-4">
                        <i class="fa-solid fa-check-circle text-5xl text-green-400"></i>
                    </div>
                    <p class="text-xl font-medium text-white">No failed events</p>
                    <p class="mt-2 text-sm">All events are processing correctly</p>
                    <a href="{{ route('stream-pulse.dashboard') }}"
                        class="mt-6 inline-flex items-center px-4 py-2 border border-gray-700 rounded-md text-sm font-medium text-gray-300 bg-gray-800 hover:bg-gray-700 transition-colors">
                        <i class="fa-solid fa-home mr-2"></i>
                        Return to Dashboard
                    </a>
                </div>
            @endif
        </div>
    </div>
@endsection
