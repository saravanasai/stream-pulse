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
                    All Failed Events- comming soon
                </h3>

            </div>
        </div>
    </div>
@endsection
