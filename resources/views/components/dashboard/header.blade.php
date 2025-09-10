<!-- Dashboard Header Component -->
<div class="flex flex-col md:flex-row md:items-center md:justify-between mb-6">
    <div>
        <h1 class="text-2xl font-semibold text-white mb-1">{{ $title ?? 'Stream Manager Dashboard' }}</h1>
        <p class="text-gray-400 text-sm">{{ $subtitle ?? 'Monitor your Redis streams and events in real-time' }}</p>
    </div>
    <div class="mt-4 md:mt-0 flex space-x-3">
        @if ($showAutoRefresh ?? true)
            <div x-data="{ autoRefresh: false }" class="flex items-center">
                <button @click="autoRefresh = !autoRefresh"
                    class="inline-flex items-center px-3 py-1.5 text-sm font-medium rounded-md"
                    :class="autoRefresh ? 'bg-redis-red text-white' : 'bg-gray-800 text-gray-300 border border-gray-700'">
                    <i class="fa-solid fa-clock mr-1.5" :class="autoRefresh ? 'animate-pulse' : ''"></i>
                    <span x-text="autoRefresh ? 'Auto-refresh: On' : 'Auto-refresh: Off'">Auto-refresh: Off</span>
                </button>
            </div>
        @endif
        <button
            class="inline-flex items-center px-3 py-1.5 text-sm font-medium rounded-md bg-gray-800 hover:bg-gray-700 text-gray-300 border border-gray-700">
            <i class="fa-solid fa-rotate mr-1.5"></i> Refresh Now
        </button>
    </div>
</div>
