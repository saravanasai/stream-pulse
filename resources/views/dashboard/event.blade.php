@extends('stream-pulse::layouts.app')

@section('content')
    <div>
        <!-- Page Header -->
        <div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
            <div>
                <h1 class="text-2xl font-semibold text-white mb-1 flex items-center">
                    <i class="fa-solid fa-file-code text-redis-red mr-3"></i>
                    Event Details
                </h1>
                <p class="text-gray-400 text-sm">Detailed information about this stream event</p>
            </div>
            <div class="mt-4 md:mt-0 flex space-x-3">
                <a href="{{ route('stream-pulse.topic', $topic) }}"
                    class="inline-flex items-center px-4 py-2 border border-gray-700 rounded-md text-sm font-medium text-gray-300 bg-gray-800 hover:bg-gray-700 transition-colors">
                    <i class="fa-solid fa-arrow-left mr-2"></i>
                    Back to Topic
                </a>
                <button
                    class="inline-flex items-center px-4 py-2 border border-transparent rounded-md text-sm font-medium text-white bg-redis-red hover:bg-redis-dark transition-colors"
                    onclick="navigator.clipboard.writeText('{{ json_encode($event['payload']) }}')">
                    <i class="fa-solid fa-code mr-2"></i>
                    Copy as JSON
                </button>
            </div>
        </div>

        <!-- Event Status Indicator -->
        <div class="bg-gray-900 border border-gray-800 rounded-lg p-4 mb-6">
            <div class="flex items-center justify-between">
                <div class="flex items-center">
                    @if ($event['is_failed'])
                        <div class="w-4 h-4 rounded-full bg-red-500 mr-2"></div>
                        <span class="text-gray-300 text-sm">Event has failed processing</span>
                    @else
                        <div class="w-4 h-4 rounded-full bg-green-500 mr-2"></div>
                        <span class="text-gray-300 text-sm">Event processed successfully</span>
                    @endif
                </div>
                <span class="text-gray-400 text-xs">ID: {{ substr($event['event_id'], 0, 16) }}...</span>
            </div>
        </div>

        <!-- Event Details Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <!-- Event Summary Card -->
            <div class="bg-gray-900 border border-gray-800 rounded-lg overflow-hidden shadow-md">
                <div class="px-4 py-4 border-b border-gray-800 flex justify-between items-center">
                    <h3 class="text-lg font-medium text-white flex items-center">
                        <i class="fa-solid fa-circle-info mr-2 text-redis-red"></i>
                        Event Summary
                    </h3>
                    @if ($event['is_failed'])
                        <span
                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-red-900 text-red-300 border border-red-700">
                            <i class="fa-solid fa-triangle-exclamation mr-1"></i>
                            Failed
                        </span>
                    @else
                        <span
                            class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium bg-green-900 text-green-300 border border-green-700">
                            <i class="fa-solid fa-check mr-1"></i>
                            Processed
                        </span>
                    @endif
                </div>
                <div class="p-5">
                    <dl>
                        <div class="flex justify-between py-2 border-b border-gray-800">
                            <dt class="text-sm font-medium text-gray-400 flex items-center">
                                <i class="fa-solid fa-fingerprint mr-2 text-gray-500"></i>
                                Event ID:
                            </dt>
                            <dd class="text-sm font-mono text-white truncate max-w-xs">
                                {{ $event['event_id'] }}
                            </dd>
                        </div>
                        <div class="flex justify-between py-2 border-b border-gray-800">
                            <dt class="text-sm font-medium text-gray-400 flex items-center">
                                <i class="fa-solid fa-stream mr-2 text-gray-500"></i>
                                Topic:
                            </dt>
                            <dd class="text-sm text-white">
                                {{ $event['topic'] }}
                            </dd>
                        </div>
                        <div class="flex justify-between py-2 border-b border-gray-800">
                            <dt class="text-sm font-medium text-gray-400 flex items-center">
                                <i class="fa-solid fa-clock mr-2 text-gray-500"></i>
                                Timestamp:
                            </dt>
                            <dd class="text-sm text-white">
                                {{ date('Y-m-d H:i:s', $event['timestamp'] / 1000) }}
                            </dd>
                        </div>
                        <div class="flex justify-between py-2">
                            <dt class="text-sm font-medium text-gray-400 flex items-center">
                                <i class="fa-solid fa-tag mr-2 text-gray-500"></i>
                                Status:
                            </dt>
                            <dd class="text-sm text-white">
                                @if ($event['is_failed'])
                                    <span class="text-red-400 font-medium">Failed</span>
                                @else
                                    <span class="text-green-400 font-medium">Processed</span>
                                @endif
                            </dd>
                        </div>
                    </dl>
                </div>
            </div>

            <!-- Event Processing Stats -->
            <div class="bg-gray-900 border border-gray-800 rounded-lg overflow-hidden shadow-md">
                <div class="px-4 py-4 border-b border-gray-800">
                    <h3 class="text-lg font-medium text-white flex items-center">
                        <i class="fa-solid fa-chart-line mr-2 text-redis-red"></i>
                        Processing Info
                    </h3>
                </div>
                <div class="p-5">
                    <div class="mb-5">
                        <div class="flex justify-between mb-2">
                            <span class="text-sm font-medium text-gray-400">Processing Time</span>
                            <span class="text-sm text-white">8.2ms</span>
                        </div>
                        <div class="w-full bg-gray-800 rounded-full h-2">
                            <div class="bg-green-500 h-2 rounded-full" style="width: 15%"></div>
                        </div>
                    </div>

                    <div class="mb-5">
                        <div class="flex justify-between mb-2">
                            <span class="text-sm font-medium text-gray-400">Memory Usage</span>
                            <span class="text-sm text-white">1.2MB</span>
                        </div>
                        <div class="w-full bg-gray-800 rounded-full h-2">
                            <div class="bg-blue-500 h-2 rounded-full" style="width: 28%"></div>
                        </div>
                    </div>

                    <div class="mb-5">
                        <div class="flex justify-between mb-2">
                            <span class="text-sm font-medium text-gray-400">Retry Count</span>
                            <span class="text-sm text-white">0/3</span>
                        </div>
                        <div class="w-full bg-gray-800 rounded-full h-2">
                            <div class="bg-yellow-500 h-2 rounded-full" style="width: 0%"></div>
                        </div>
                    </div>

                    <div class="text-center mt-4">
                        <span class="text-sm text-gray-400">Event Size: {{ strlen(json_encode($event['payload'])) }}
                            bytes</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Payload Details Card -->
        <div class="bg-gray-900 border border-gray-800 rounded-lg overflow-hidden shadow-md">
            <div class="px-4 py-4 border-b border-gray-800 flex justify-between items-center">
                <h3 class="text-lg font-medium text-white flex items-center">
                    <i class="fa-solid fa-code mr-2 text-redis-red"></i>
                    Event Payload
                </h3>
                <div class="flex space-x-2">
                    <button
                        class="text-xs bg-gray-800 hover:bg-gray-700 text-gray-300 px-2 py-1 rounded border border-gray-700">
                        Raw
                    </button>
                    <button class="text-xs bg-redis-red text-white px-2 py-1 rounded">
                        Formatted
                    </button>
                </div>
            </div>
            <div class="p-0">
                <div class="bg-gray-950 rounded-b-lg overflow-hidden">
                    <pre class="json-viewer p-6 overflow-x-auto text-sm font-mono text-gray-300 max-h-96">{{ json_encode($event['payload'], JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES) }}</pre>
                </div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="mt-6 flex flex-col sm:flex-row sm:justify-between gap-3">
            <div class="flex flex-col sm:flex-row gap-3">
                <a href="{{ route('stream-pulse.topic', $topic) }}"
                    class="inline-flex items-center justify-center px-4 py-2 border border-gray-700 rounded-md text-sm font-medium text-gray-300 bg-gray-800 hover:bg-gray-700 transition-colors">
                    <i class="fa-solid fa-arrow-left mr-2"></i>
                    Back to Topic
                </a>
                <a href="{{ route('stream-pulse.dashboard') }}"
                    class="inline-flex items-center justify-center px-4 py-2 border border-gray-700 rounded-md text-sm font-medium text-gray-300 bg-gray-800 hover:bg-gray-700 transition-colors">
                    <i class="fa-solid fa-gauge mr-2"></i>
                    Dashboard
                </a>
            </div>

            @if ($event['is_failed'])
                <button
                    class="inline-flex items-center justify-center px-4 py-2 border border-transparent rounded-md text-sm font-medium text-white bg-redis-red hover:bg-redis-dark transition-colors">
                    <i class="fa-solid fa-rotate mr-2"></i>
                    Retry Processing
                </button>
            @endif
        </div>
    </div>

    <style>
        .json-viewer {
            line-height: 1.5;
        }

        .json-viewer .string {
            color: #a8ff60;
        }

        .json-viewer .number {
            color: #d8fa3c;
        }

        .json-viewer .boolean {
            color: #6897bb;
        }

        .json-viewer .null {
            color: #ff628c;
        }

        .json-viewer .key {
            color: #9cdcfe;
        }
    </style>
@endsection
