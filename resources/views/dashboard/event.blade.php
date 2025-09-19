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

        <!-- Event Details Grid -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-6 mb-6">
            <!-- Event Summary Card -->
            <div class="bg-gray-900 border border-gray-800 rounded-lg overflow-hidden shadow-md">
                <div class="px-4 py-4 border-b border-gray-800 flex justify-between items-center">
                    <h3 class="text-lg font-medium text-white flex items-center">
                        <i class="fa-solid fa-circle-info mr-2 text-redis-red"></i>
                        Event Summary
                    </h3>
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
                    comming soon
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
